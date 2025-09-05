<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NamecheapClient
{
    protected DomainProvider $p;
    protected string $baseUrl;

    public function __construct(DomainProvider $p)
    {
        $this->p = $p;
        $this->baseUrl = ($p->mode === 'test')
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response';
    }

    protected function call(array $params)
    {
        $query = array_merge([
            'ApiUser'  => trim($this->p->username ?? ''),
            'ApiKey'   => trim($this->p->api_key ?? ''),
            'UserName' => trim($this->p->username ?? ''),
            'ClientIp' => trim($this->p->client_ip ?? ''), // يجب أن يكون نفس الـ IP المبيّض في لوحة Namecheap
        ], $params);

        $res = Http::timeout(20)->get($this->baseUrl, $query);
        if (!$res->ok()) {
            return [
                'ok' => false,
                'message' => "HTTP {$res->status()}",
                'error_code' => $res->status(),
                'raw' => $res->body()
            ];
        }

        $xml = @simplexml_load_string($res->body());
        if (!$xml) {
            return [
                'ok' => false,
                'message' => 'Failed to parse XML',
                'error_code' => 'PARSE_ERROR',
                'raw' => $res->body()
            ];
        }

        if (isset($xml->Errors) && $xml->Errors->count() > 0) {
            $msgs = [];
            $codes = [];
            foreach ($xml->Errors->Error as $e) {
                $msgs[] = trim((string)$e);
                $codes[] = (string)$e['Number'];
            }
            return [
                'ok' => false,
                'message' => implode(' | ', $msgs),
                'error_code' => implode(',', $codes),
                'raw' => $xml
            ];
        }

        return ['ok' => true, 'xml' => $xml];
    }

    /** اختبار سريع: جلب الرصيد */
    // App\Services\DomainProviders\NamecheapClient.php
    public function getBalance(): array
    {
        try {
            $endpoint = rtrim($this->endpoint());
            $params   = array_merge($this->baseParams(), [
                'Command' => 'namecheap.users.getBalances',
            ]);

            $resp = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/xml',
                'User-Agent' => 'PalgoalsBot/1.0',
            ])
                // إجبار IPv4 (أحياناً الأجهزة تخرج IPv6 ويصير رفض)
                ->withOptions([
                    'curl' => [
                        \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4,
                    ],
                ])
                ->timeout(20)
                ->get($endpoint, $params);

            $status = $resp->status();
            $ct     = $resp->header('Content-Type');
            $body   = $resp->body();

            if (!$resp->ok()) {
                \Log::warning('Namecheap getBalance non-200', [
                    'status'       => $status,
                    'content_type' => $ct,
                    'snippet'      => mb_substr($body ?? '', 0, 400),
                ]);
                return [
                    'ok'         => false,
                    'reason'     => 'http_error',
                    'http_code'  => $status,
                    'message'    => "فشل HTTP ($status). تحقق من Whitelist IP والـ endpoint.",
                    'balance'    => null,
                    'currency'   => null,
                ];
            }

            if (!is_string($ct) || stripos($ct, 'xml') === false) {
                \Log::warning('Namecheap getBalance non-XML response', [
                    'status'       => $status,
                    'content_type' => $ct,
                    'snippet'      => mb_substr($body ?? '', 0, 400),
                ]);
                return [
                    'ok'       => false,
                    'reason'   => 'non_xml',
                    'message'  => 'لم نتلقَّ XML من المزود (غالبًا رد HTML/Proxy أو IP غير مُصرّح).',
                    'balance'  => null,
                    'currency' => null,
                ];
            }

            $body = preg_replace('/^\xEF\xBB\xBF/', '', (string)$body);
            $xml  = @simplexml_load_string($body, 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
            if ($xml === false) {
                \Log::warning('Namecheap getBalance XML parse failed', [
                    'status'       => $status,
                    'content_type' => $ct,
                    'snippet'      => mb_substr($body ?? '', 0, 400),
                ]);
                return [
                    'ok'       => false,
                    'reason'   => 'xml_parse_error',
                    'message'  => 'رد غير صالح (تعذر تحليل XML).',
                    'balance'  => null,
                    'currency' => null,
                ];
            }

            $xml->registerXPathNamespace('nc', 'http://api.namecheap.com/xml.response');
            $statusAttr = (string)($xml['Status'] ?? '');
            if (strcasecmp($statusAttr, 'OK') !== 0) {
                $errNode = $xml->xpath('//nc:Errors/nc:Error')[0] ?? null;
                $errMsg  = $errNode ? (string)$errNode : 'تعذّر تنفيذ الطلب.';
                return [
                    'ok'       => false,
                    'reason'   => 'provider_error',
                    'message'  => $errMsg,
                    'balance'  => null,
                    'currency' => null,
                ];
            }

            $node = $xml->xpath('//nc:UserGetBalancesResult')[0] ?? null;
            if (!$node) {
                return [
                    'ok'       => false,
                    'reason'   => 'missing_result_node',
                    'message'  => 'لم نجد UserGetBalancesResult في الرد.',
                    'balance'  => null,
                    'currency' => null,
                ];
            }

            $attrs = $node->attributes();
            return [
                'ok'        => true,
                'reason'    => 'ok',
                'message'   => 'تم الاتصال بنجاح.',
                'balance'   => isset($attrs->AccountBalance) ? (float)$attrs->AccountBalance : null,
                'available' => isset($attrs->AvailableBalance) ? (float)$attrs->AvailableBalance : null,
                'currency'  => isset($attrs->Currency) ? (string)$attrs->Currency : null,
            ];
        } catch (\Throwable $e) {
            \Log::error('Namecheap getBalance exception', ['error' => $e->getMessage()]);
            return [
                'ok'      => false,
                'reason'  => 'exception',
                'message' => 'استثناء أثناء طلب الرصيد: ' . $e->getMessage(),
            ];
        }
    }
}
