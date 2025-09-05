<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnomClient
{
    protected function endpointFor(DomainProvider $p): string
    {
        return $p->mode === 'test'
            ? 'https://resellertest.enom.com/interface.asp'
            : 'https://reseller.enom.com/interface.asp';
    }

    /** مصداقية الاعتماد: eNom يقبل أحد هذه الصيغ */
    protected function authParams(DomainProvider $p): array
    {
        // أولوية: ApiToken -> ApiKey -> PW
        if (!empty($p->api_token)) {
            return ['ApiUser' => $p->username, 'ApiToken' => $p->api_token];
        }
        if (!empty($p->api_key)) {
            return ['ApiUser' => $p->username, 'ApiKey' => $p->api_key];
        }
        return ['UID' => $p->username, 'PW' => $p->password];
    }

    protected function baseParams(DomainProvider $p): array
    {
        return array_merge($this->authParams($p), [
            'ResponseType' => 'XML', // XML أسهل للتحليل هنا
        ]);
    }

    /** طلب موحّد يعيد ok/xml أو سبب الفشل */
    protected function request(DomainProvider $p, array $params): array
    {
        $endpoint = $this->endpointFor($p);
        $query    = array_merge($this->baseParams($p), $params);

        $start = microtime(true);

        $resp = \Illuminate\Support\Facades\Http::withHeaders([
            'Accept'     => 'application/xml',
            'User-Agent' => 'PalgoalsBot/1.0',
        ])
            ->withOptions([
                'curl' => [\CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4],
            ])
            ->connectTimeout(5)     // ⏱️ وقت اتصال
            ->timeout(12)           // ⏱️ حد أقصى للإجمالي
            ->retry(1, 200)         // إعادة سريعة لمرة واحدة
            ->get($endpoint, $query);

        $dur = (int) round((microtime(true) - $start) * 1000);

        $status = $resp->status();
        $ct     = $resp->header('Content-Type');
        $body   = (string) $resp->body();

        if (!$resp->ok()) {
            Log::warning('Enom HTTP error', ['status' => $status, 'ct' => $ct, 'ms' => $dur, 'snippet' => mb_substr($body, 0, 300)]);
            return ['ok' => false, 'reason' => 'http_error', 'http_code' => $status, 'message' => "HTTP $status", 'duration_ms' => $dur];
        }

        if (!is_string($ct) || stripos($ct, 'xml') === false) {
            Log::warning('Enom non-XML', ['ct' => $ct, 'ms' => $dur, 'snippet' => mb_substr($body, 0, 300)]);
            return ['ok' => false, 'reason' => 'non_xml', 'message' => 'الاستجابة ليست XML.', 'duration_ms' => $dur];
        }

        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $xml  = @simplexml_load_string($body, 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
        if ($xml === false) {
            Log::warning('Enom XML parse failed', ['ms' => $dur, 'snippet' => mb_substr($body, 0, 300)]);
            return ['ok' => false, 'reason' => 'xml_parse_error', 'message' => 'تعذر تحليل XML.', 'duration_ms' => $dur];
        }

        $errCount = (int)($xml->ErrCount ?? 0);
        if ($errCount > 0) {
            $errors = [];
            if (isset($xml->errors)) foreach ($xml->errors->children() as $err) {
                $errors[] = trim((string)$err);
            }
            if (isset($xml->responses->response->ResponseString)) $errors[] = trim((string)$xml->responses->response->ResponseString);
            $msg = $errors ? implode(' | ', $errors) : 'Unknown eNom API error';
            return ['ok' => false, 'reason' => 'provider_error', 'message' => $msg, 'xml' => $xml, 'duration_ms' => $dur];
        }

        $rrpCode = isset($xml->RRPCode) ? (int)$xml->RRPCode : null;
        $rrpText = isset($xml->RRPText) ? (string)$xml->RRPText : null;

        return ['ok' => true, 'xml' => $xml, 'rrp_code' => $rrpCode, 'rrp_text' => $rrpText, 'duration_ms' => $dur];
    }

    /** جلب الرصيد (يوحّد الإخراج: ok/message/balance/currency) */
    public function getBalance(DomainProvider $p): array
    {
        try {
            $r = $this->request($p, ['command' => 'GetBalance']);
            if (!$r['ok']) {
                return array_merge($r, ['balance' => null, 'currency' => null]);
            }

            /** @var \SimpleXMLElement $xml */
            $xml = $r['xml'];

            $balance  = null;
            $hold     = null;
            $currency = null;

            // شكل 1: <GetBalance>...</GetBalance>
            if (isset($xml->GetBalance)) {
                $gb = $xml->GetBalance;

                // الترتيب: AvailableBalance ثم AccountBalance ثم Balance
                foreach (['AvailableBalance', 'AccountBalance', 'Balance'] as $k) {
                    if (isset($gb->{$k}) && strlen((string)$gb->{$k})) {
                        $balance = (float)$gb->{$k};
                        break;
                    }
                }

                if (isset($gb->HoldBalance) && strlen((string)$gb->HoldBalance)) {
                    $hold = (float)$gb->HoldBalance;
                }

                foreach (['Currency', 'currency'] as $k) {
                    if (isset($gb->{$k}) && strlen((string)$gb->{$k})) {
                        $currency = (string)$gb->{$k};
                        break;
                    }
                }
            }

            // شكل 2: <interface-response><attributes>...</attributes></interface-response>
            if ($balance === null && isset($xml->attributes)) {
                $attrs = $xml->attributes;

                foreach (['balance', 'availablebalance', 'accountbalance', 'Balance', 'AvailableBalance', 'AccountBalance'] as $k) {
                    if (isset($attrs->{$k}) && strlen((string)$attrs->{$k})) {
                        $balance = (float)$attrs->{$k};
                        break;
                    }
                }

                if ($hold === null) {
                    foreach (['holdbalance', 'HoldBalance'] as $k) {
                        if (isset($attrs->{$k}) && strlen((string)$attrs->{$k})) {
                            $hold = (float)$attrs->{$k};
                            break;
                        }
                    }
                }

                if ($currency === null) {
                    foreach (['currency', 'Currency'] as $k) {
                        if (isset($attrs->{$k}) && strlen((string)$attrs->{$k})) {
                            $currency = (string)$attrs->{$k};
                            break;
                        }
                    }
                }
            }

            // شكل 3 (ملاذ أخير): التقاط بالأregex من الـ XML كاملًا
            if ($balance === null) {
                $s = $xml->asXML() ?: '';
                if (preg_match('/<(?:AvailableBalance|AccountBalance|balance)>([0-9]+(?:\.[0-9]+)?)<\/[^>]+>/', $s, $m)) {
                    $balance = (float) $m[1];
                }
                if ($currency === null && preg_match('/<currency>([A-Z]{3})<\/currency>/i', $s, $m2)) {
                    $currency = strtoupper($m2[1]);
                }
            }

            return [
                'ok'       => true,
                'reason'   => 'ok',
                'message'  => 'تم الاتصال بنجاح.',
                'balance'  => $balance,   // ← الكنترولر والواجهة يعتمدون هذا
                'currency' => $currency,
                'meta'     => [
                    'hold'     => $hold,
                    'rrp_code' => $r['rrp_code'] ?? null,
                    'rrp_text' => $r['rrp_text'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            logger()->error('Enom GetBalance exception', ['error' => $e->getMessage()]);
            return [
                'ok'       => false,
                'reason'   => 'exception',
                'message'  => 'استثناء: ' . $e->getMessage(),
                'balance'  => null,
                'currency' => null,
            ];
        }
    }
}
