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

        Log::info('Enom preflight', [
            'endpoint'  => $endpoint,
            'mode'      => $p->mode,
            'username'  => $p->username,
            'auth_used' => implode(',', array_keys($this->authParams($p))), // للتشخيص فقط
        ]);

        $resp = Http::withHeaders([
            'Accept'     => 'application/xml',
            'User-Agent' => 'PalgoalsBot/1.0',
        ])
            ->withOptions([
                'curl' => [
                    \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4, // تجنّب IPv6
                ],
            ])
            ->timeout(20)
            ->get($endpoint, $query);

        $status = $resp->status();
        $ct     = $resp->header('Content-Type');
        $body   = (string) $resp->body();

        if (!$resp->ok()) {
            Log::warning('Enom HTTP error', ['status' => $status, 'ct' => $ct, 'snippet' => mb_substr($body, 0, 400)]);
            return ['ok' => false, 'reason' => 'http_error', 'http_code' => $status, 'message' => "HTTP $status"];
        }

        if (!is_string($ct) || stripos($ct, 'xml') === false) {
            Log::warning('Enom non-XML', ['status' => $status, 'ct' => $ct, 'snippet' => mb_substr($body, 0, 400)]);
            return ['ok' => false, 'reason' => 'non_xml', 'message' => 'الاستجابة ليست XML.'];
        }

        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $xml  = @simplexml_load_string($body, 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
        if ($xml === false) {
            Log::warning('Enom XML parse failed', ['snippet' => mb_substr($body, 0, 400)]);
            return ['ok' => false, 'reason' => 'xml_parse_error', 'message' => 'تعذر تحليل XML.'];
        }

        // أخطاء eNom الكلاسيكية
        $errCount = (int)($xml->ErrCount ?? 0);
        if ($errCount > 0) {
            $errors = [];

            // بعض البيئات ترجع <errors><Err1>..</Err1><Err2>..</Err2>...</errors>
            if (isset($xml->errors)) {
                foreach ($xml->errors->children() as $err) {
                    $errors[] = trim((string)$err);
                }
            }

            // وأحياناً ضمن responses/response/ResponseString
            if (isset($xml->responses->response->ResponseString)) {
                $errors[] = trim((string)$xml->responses->response->ResponseString);
            }

            $msg = $errors ? implode(' | ', $errors) : 'Unknown eNom API error';
            return ['ok' => false, 'reason' => 'provider_error', 'message' => $msg, 'xml' => $xml];
        }

        // RRPCode/ RRPText للمعلومية
        $rrpCode = isset($xml->RRPCode) ? (int)$xml->RRPCode : null;
        $rrpText = isset($xml->RRPText) ? (string)$xml->RRPText : null;

        return ['ok' => true, 'xml' => $xml, 'rrp_code' => $rrpCode, 'rrp_text' => $rrpText];
    }

    /** جلب الرصيد (يوحّد الإخراج: ok/message/balance/currency) */
    public function getBalance(DomainProvider $p): array
    {
        try {
            $r = $this->request($p, ['command' => 'GetBalance']);
            if (!$r['ok']) {
                return array_merge(['balance' => null, 'currency' => null], $r);
            }

            $xml = $r['xml'];

            // eNom له أكثر من شكل؛ نحاول مسارات متعددة
            $available = null;
            $hold = null;
            $currency = null;

            // 1) الشكل الشائع: <GetBalance><AvailableBalance>..</AvailableBalance>...
            if (isset($xml->GetBalance)) {
                if (isset($xml->GetBalance->AvailableBalance)) $available = (float)$xml->GetBalance->AvailableBalance;
                if (isset($xml->GetBalance->HoldBalance))      $hold      = (float)$xml->GetBalance->HoldBalance;
                if (isset($xml->GetBalance->Currency))         $currency  = (string)$xml->GetBalance->Currency;
            }

            // 2) بعض الردود: <interface-response><attributes><balance>..</balance><holdbalance>..</holdbalance></attributes>
            if ($available === null && isset($xml->attributes->balance)) {
                $available = (float)$xml->attributes->balance;
            }
            if ($hold === null && isset($xml->attributes->holdbalance)) {
                $hold = (float)$xml->attributes->holdbalance;
            }
            if ($currency === null && isset($xml->attributes->currency)) {
                $currency = (string)$xml->attributes->currency;
            }

            return [
                'ok'       => true,
                'reason'   => 'ok',
                'message'  => 'تم الاتصال بنجاح.',
                'balance'  => $available,   // الكنترولر يقرأ هذا الحقل
                'currency' => $currency,
                'meta'     => ['hold' => $hold, 'rrp_code' => $r['rrp_code'] ?? null, 'rrp_text' => $r['rrp_text'] ?? null],
            ];
        } catch (\Throwable $e) {
            logger()->error('Enom GetBalance exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'exception', 'message' => 'استثناء: ' . $e->getMessage(), 'balance' => null, 'currency' => null];
        }
    }

    /** فحص توفر دومين (يوحّد الإخراج: ok/message/available) */
    public function checkAvailability(DomainProvider $p, string $sld, string $tld): array
    {
        try {
            $r = $this->request($p, [
                'command' => 'check',
                'SLD'     => $sld,
                'TLD'     => strtoupper($tld),
            ]);
            if (!$r['ok']) return array_merge($r, ['available' => null]);

            $xml = $r['xml'];
            // 210 عادة = متاح، 211 = غير متاح، 200 = نجاح (عام)
            $rrp = $r['rrp_code'] ?? null;
            $available = ($rrp === 210);

            // fallback: بعض الردود قد تحتوي domain أو خانات أخرى؛ لكن RRPCode يكفي غالباً
            return [
                'ok'        => true,
                'reason'    => 'ok',
                'message'   => $r['rrp_text'] ?? 'تم التنفيذ.',
                'available' => $available,
                'meta'      => ['rrp_code' => $rrp, 'rrp_text' => $r['rrp_text'] ?? null],
            ];
        } catch (\Throwable $e) {
            logger()->error('Enom checkAvailability exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'exception', 'message' => 'استثناء: ' . $e->getMessage(), 'available' => null];
        }
    }
}
