<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EnomClient
{
    /** eNom classic interface.asp endpoint (test vs live) */
    protected function endpointFor(DomainProvider $p): string
    {
        return $p->mode === 'test'
            ? 'https://resellertest.enom.com/interface.asp'
            : 'https://reseller.enom.com/interface.asp';
    }

    /** أولوية اعتماد: ApiToken -> ApiKey -> UID/PW */
    protected function authParams(DomainProvider $p): array
    {
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
            'ResponseType' => 'XML',
        ]);
    }

    /** إرسال طلب إلى eNom مع معالجة شاملة للأخطاء */
    protected function request(DomainProvider $p, array $params): array
    {
        $endpoint = $this->endpointFor($p);
        $query    = array_merge($this->baseParams($p), $params);

        // لا نُسجّل أسرار الاعتماد
        $safeQuery = $query;
        unset($safeQuery['PW'], $safeQuery['ApiKey'], $safeQuery['ApiToken']);

        $t0  = microtime(true);
        $cid = (string) Str::uuid();

        try {
            $resp = Http::withHeaders([
                'Accept'     => 'application/xml',
                'User-Agent' => 'PalgoalsBot/1.0',
                'X-Request-ID' => $cid,
            ])
                ->withOptions([
                    'curl' => [\CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4], // eNom IPv4 غالباً أكثر استقرارًا
                ])
                // retry ذكي: جرّب مرة إضافية فقط على أخطاء شبكة/مهلة
                ->retry(2, 300, function ($exception, $request) {
                    return $exception && (
                        str_contains(get_class($exception), 'ConnectionException')
                        || str_contains($exception->getMessage(), 'timed out')
                    );
                })
                ->connectTimeout(6)
                ->timeout(15)
                ->get($endpoint, $query);

            $ms   = (int) round((microtime(true) - $t0) * 1000);
            $ct   = strtolower((string) $resp->header('Content-Type', ''));
            $body = (string) $resp->body();

            if (!$resp->ok()) {
                Log::warning('Enom HTTP error', [
                    'cid' => $cid,
                    'provider_id' => $p->id ?? null,
                    'mode' => $p->mode ?? null,
                    'command' => $params['command'] ?? null,
                    'status' => $resp->status(),
                    'ms' => $ms,
                    'snippet' => mb_substr($body, 0, 300),
                    'query' => $safeQuery,
                ]);
                return [
                    'ok' => false,
                    'reason' => 'http_error',
                    'http_code' => $resp->status(),
                    'message' => "HTTP {$resp->status()} from eNom",
                    'cid' => $cid,
                ];
            }

            // بعض خوادم eNom تعود بـ text/plain مع XML؛ نسمح بأي CT يحتوي "xml"
            if (!str_contains($ct, 'xml')) {
                Log::warning('Enom non-XML content type', [
                    'cid' => $cid,
                    'ct' => $ct,
                    'ms' => $ms,
                    'snippet' => mb_substr($body, 0, 300),
                    'command' => $params['command'] ?? null,
                    'query' => $safeQuery,
                ]);
                return [
                    'ok' => false,
                    'reason' => 'non_xml',
                    'message' => 'الاستجابة ليست XML صالحة.',
                    'cid' => $cid,
                ];
            }

            // إزالة BOM إن وجدت
            $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);

            $xml = @simplexml_load_string(
                $body,
                'SimpleXMLElement',
                \LIBXML_NOCDATA | \LIBXML_NOERROR | \LIBXML_NOWARNING
            );

            if ($xml === false) {
                Log::warning('Enom XML parse failed', [
                    'cid' => $cid,
                    'ms' => $ms,
                    'snippet' => mb_substr($body, 0, 300),
                    'command' => $params['command'] ?? null,
                ]);
                return [
                    'ok' => false,
                    'reason' => 'xml_parse_error',
                    'message' => 'تعذّر تحليل استجابة XML من eNom.',
                    'cid' => $cid,
                ];
            }

            // تجميع رسائل responses (إن وجدت)
            $responseSummaries = [];
            if (isset($xml->responses)) {
                foreach ($xml->responses->children() as $response) {
                    $code = isset($response->ResponseNumber) ? (int) $response->ResponseNumber : null;
                    $msg  = isset($response->ResponseString) ? trim((string) $response->ResponseString) : null;
                    $responseSummaries[] = ['code' => $code, 'message' => $msg];

                    // eNom قد يُرسل أكواد >= 300 كمشاكل منطقية
                    if ($code !== null && $code >= 300) {
                        return [
                            'ok' => false,
                            'reason' => 'provider_response',
                            'message' => $msg ?: 'eNom rejected the request.',
                            'code' => $code,
                            'xml' => $xml,
                            'cid' => $cid,
                        ];
                    }
                }
            }

            // أخطاء مجمّعة ErrCount
            if ((int) ($xml->ErrCount ?? 0) > 0) {
                $errors = [];
                if (isset($xml->errors)) {
                    foreach ($xml->errors->children() as $e) {
                        $s = trim((string) $e);
                        if ($s !== '') $errors[] = $s;
                    }
                }
                if (isset($xml->responses->response->ResponseString)) {
                    $errors[] = trim((string) $xml->responses->response->ResponseString);
                }
                $msg = $errors ? implode(' | ', array_unique($errors)) : 'Unknown eNom API error';
                return [
                    'ok' => false,
                    'reason' => 'provider_error',
                    'message' => $msg,
                    'xml' => $xml,
                    'cid' => $cid,
                ];
            }

            // بعض أوامر eNom ترجع RRPCode/RRPText (على غرار EPP)
            if (isset($xml->RRPCode) && is_numeric((string) $xml->RRPCode)) {
                $rrp = (int) $xml->RRPCode;
                if ($rrp >= 3000) {
                    $rrpMessage = isset($xml->RRPText) ? trim((string) $xml->RRPText) : 'Registrar returned an error.';
                    return [
                        'ok' => false,
                        'reason' => 'rrp_error',
                        'message' => $rrpMessage,
                        'code' => $rrp,
                        'xml' => $xml,
                        'cid' => $cid,
                    ];
                }
            }

            Log::debug('Enom command response', [
                'cid' => $cid,
                'command' => $params['command'] ?? null,
                'ms' => $ms,
                'responses' => $responseSummaries,
                'query' => $safeQuery,
            ]);

            return ['ok' => true, 'xml' => $xml, 'cid' => $cid];
        } catch (Throwable $e) {
            $ms = (int) round((microtime(true) - $t0) * 1000);
            Log::error('Enom request exception', [
                'cid' => $cid,
                'command' => $params['command'] ?? null,
                'ms' => $ms,
                'query' => $safeQuery,
                'ex' => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'reason' => 'exception',
                'message' => $e->getMessage(),
                'cid' => $cid,
            ];
        }
    }

    /** ربط نوع العملية برقم المنتج في eNom */
    protected function productType(string $action): ?int
    {
        return match (strtolower($action)) {
            'register' => 10,
            'renew'    => 16,
            'transfer' => 19,
            default    => null,
        };
    }

    /** Parser موحّد لبلوك <productprice> في ردود التسعير */
    protected function parsePriceXml(\SimpleXMLElement $xml): array
    {
        $s = $xml->asXML() ?: '';
        $price = null;
        $enabled = null;
        $currency = null;

        if (isset($xml->productprice)) {
            $pp = $xml->productprice;
            if (isset($pp->price) && (string) $pp->price !== '') {
                $price = (float) $pp->price;
            }
            if (isset($pp->productenabled)) {
                $enabled = in_array(strtolower((string) $pp->productenabled), ['true', '1', 'yes'], true);
            }
            if (isset($pp->currency) && (string) $pp->currency !== '') {
                $currency = strtoupper((string) $pp->currency);
            }
        } else {
            if (preg_match('/<price>([0-9]+(?:\.[0-9]+)?)<\/price>/i', $s, $m)) $price = (float) $m[1];
            if (preg_match('/<productenabled>([^<]+)<\/productenabled>/i', $s, $m2)) {
                $enabled = in_array(strtolower(trim($m2[1])), ['true', '1', 'yes'], true);
            }
            if (preg_match('/<currency>([A-Za-z]{3})<\/currency>/i', $s, $m3)) $currency = strtoupper($m3[1]);
        }

        return [$price, $enabled, $currency];
    }

    /** PE_GetProductPrice */
    public function getProductPrice(DomainProvider $p, string $tld, string $action, int $years = 1): array
    {
        $pt = $this->productType($action);
        if (!$pt) return ['ok' => false, 'message' => 'Unsupported action', 'price' => null, 'enabled' => null];

        $r = $this->request($p, [
            'command'     => 'PE_GetProductPrice',
            'ProductType' => $pt,
            'tld'         => ltrim(strtolower($tld), '.'),
            'Years'       => $years,
        ]);
        if (!$r['ok']) return array_merge($r, ['price' => null, 'enabled' => null, 'currency' => null, 'source' => 'product']);

        [$price, $enabled, $currency] = $this->parsePriceXml($r['xml']);
        return [
            'ok' => ($price !== null),
            'price' => $price,
            'enabled' => $enabled,
            'currency' => $currency,
            'source' => 'product',
            'raw' => $r['xml'],
            'cid' => $r['cid'] ?? null,
        ];
    }

    /** PE_GetRetailPrice (fallback 1) */
    public function getRetailPrice(DomainProvider $p, string $tld, string $action, int $years = 1): array
    {
        $pt = $this->productType($action);
        if (!$pt) return ['ok' => false, 'message' => 'Unsupported action', 'price' => null, 'enabled' => null];

        $r = $this->request($p, [
            'command'     => 'PE_GetRetailPrice',
            'ProductType' => $pt,
            'tld'         => ltrim(strtolower($tld), '.'),
            'Years'       => $years,
        ]);
        if (!$r['ok']) return array_merge($r, ['price' => null, 'enabled' => null, 'currency' => null, 'source' => 'retail']);

        [$price, $enabled, $currency] = $this->parsePriceXml($r['xml']);
        return [
            'ok' => ($price !== null),
            'price' => $price,
            'enabled' => $enabled,
            'currency' => $currency,
            'source' => 'retail',
            'raw' => $r['xml'],
            'cid' => $r['cid'] ?? null,
        ];
    }

    /** PE_GetResellerPrice (fallback 2) */
    public function getResellerPrice(DomainProvider $p, string $tld, string $action, int $years = 1): array
    {
        $pt = $this->productType($action);
        if (!$pt) return ['ok' => false, 'message' => 'Unsupported action', 'price' => null, 'enabled' => null];

        $r = $this->request($p, [
            'command'     => 'PE_GetResellerPrice',
            'ProductType' => $pt,
            'tld'         => ltrim(strtolower($tld), '.'),
            'Years'       => $years,
        ]);
        if (!$r['ok']) return array_merge($r, ['price' => null, 'enabled' => null, 'currency' => null, 'source' => 'reseller']);

        [$price, $enabled, $currency] = $this->parsePriceXml($r['xml']);
        return [
            'ok' => ($price !== null),
            'price' => $price,
            'enabled' => $enabled,
            'currency' => $currency,
            'source' => 'reseller',
            'raw' => $r['xml'],
            'cid' => $r['cid'] ?? null,
        ];
    }

    /** يجرب التسعير عبر Product ثم Retail ثم Reseller */
    public function getAnyPrice(DomainProvider $p, string $tld, string $action, int $years = 1): array
    {
        $first = $this->getProductPrice($p, $tld, $action, $years);
        if ($first['ok'] && $first['price'] !== null) return $first;

        $second = $this->getRetailPrice($p, $tld, $action, $years);
        if ($second['ok'] && $second['price'] !== null) return $second;

        return $this->getResellerPrice($p, $tld, $action, $years);
    }

    /** تنفيذ شراء */
    public function purchaseDomain(DomainProvider $p, array $params): array
    {
        $payload = array_merge([
            'command' => 'Purchase',
            'UseDNS'  => 'default',
        ], $params);

        return $this->request($p, $payload);
    }

    /** تحديث النيم سيرفرز */
    public function updateNameservers(DomainProvider $p, string $fqdn, array $nameservers): array
    {
        [$sld, $tld] = $this->splitDomainParts($fqdn);

        if (!$sld || !$tld) {
            return [
                'ok' => false,
                'reason' => 'invalid_domain',
                'message' => 'تعذّر تقسيم النطاق إلى SLD/TLD لتحديث النيم سيرفرز.',
            ];
        }

        // eNom يسمح حتى NS12، لكن عمليًا 2–4 تكفي
        $nameservers = array_values(array_filter(array_map('trim', $nameservers)));
        if (count($nameservers) < 2) {
            return [
                'ok' => false,
                'reason' => 'invalid_nameservers',
                'message' => 'يجب تحديد اسمَي نيم سيرفر على الأقل.',
            ];
        }

        $payload = [
            'command' => 'ModifyNS',
            'SLD'     => $sld,
            'TLD'     => $tld,
            'UseDNS'  => 'custom',
        ];

        foreach (array_slice($nameservers, 0, 12) as $i => $ns) {
            // تحقق بسيط للشكل
            if (!str_contains($ns, '.')) {
                return [
                    'ok' => false,
                    'reason' => 'invalid_nameserver',
                    'message' => "اسم النيم سيرفر غير صالح: {$ns}",
                ];
            }
            $payload['NS' . ($i + 1)] = $ns;
        }

        return $this->request($p, $payload);
    }

    /** تقسيم FQDN إلى SLD/TLD مع دعم IDN */
    protected function splitDomainParts(string $fqdn): array
    {
        $fqdn = strtolower(trim($fqdn));
        if (!str_contains($fqdn, '.')) {
            return [null, null];
        }

        // دعم IDN: حوّل إلى ASCII (punycode) لو توفّر intl
        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($fqdn, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) $fqdn = strtolower($ascii);
        }

        // لاحظ: eNom expects SLD and TLD = الجزء الأول وما بعده
        $parts = explode('.', $fqdn, 2);
        $sld = isset($parts[0]) ? Str::of($parts[0])->ascii()->trim()->value() : null;
        $tld = isset($parts[1]) ? Str::of($parts[1])->ascii()->trim()->value() : null;

        return [$sld ?: null, $tld ?: null];
    }
}
