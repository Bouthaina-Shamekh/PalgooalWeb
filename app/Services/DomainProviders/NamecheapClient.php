<?php

namespace App\Services\DomainProviders;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class NamecheapClient
{
    protected DomainProvider $p;

    public function __construct(DomainProvider $p)
    {
        $this->p = $p;
    }

    /** اختيار endpoint (من DB أو وفق وضع التشغيل) */
    protected function endpoint(): string
    {
        if (!empty($this->p->endpoint)) {
            return rtrim($this->p->endpoint);
        }

        $mode = strtolower((string) $this->p->mode);
        if ($mode === 'test') return 'https://api.sandbox.namecheap.com/xml.response';
        if ($mode === 'live') return 'https://api.namecheap.com/xml.response';

        throw new \RuntimeException('الوضع (mode) غير صالح. استخدم test أو live أو حدّد endpoint.');
    }

    /** قراءة مفتاح API (قد يكون مُشفّرًا) */
    protected function readApiKey(): string
    {
        $raw = (string) $this->p->api_key;
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return $raw; // fallback: غير مشفّر
        }
    }

    /** إعداد المعاملات الأساسية + التحقق من IP */
    protected function baseParams(): array
    {
        $ip = $this->p->client_ip;
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \RuntimeException('Client IP غير صالح/غير مُهيّأ.');
        }

        return [
            'ApiUser'  => trim((string) $this->p->username),
            'ApiKey'   => trim((string) $this->readApiKey()),
            'UserName' => trim((string) $this->p->username),
            'ClientIp' => trim((string) $ip),
        ];
    }

    /** طلب HTTP عام: يتحقق من XML ويعيد reason/status/snippet */
    protected function request(array $params): array
    {
        $endpoint = $this->endpoint();
        $query    = array_merge($this->baseParams(), $params);

        // Logging قبل الطلب
        Log::info('Namecheap preflight', [
            'endpoint'  => $endpoint,
            'mode'      => $this->p->mode,
            'username'  => $this->p->username,
            'client_ip' => $this->p->client_ip,
            'api_key_len' => strlen($this->readApiKey() ?? ''),
        ]);

        $resp = Http::withHeaders([
            'Accept'     => 'application/xml',
            'User-Agent' => 'PalgoalsBot/1.0',
        ])
            ->withOptions([
                'curl' => [
                    \CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4, // Force IPv4
                ],
            ])
            ->timeout(20)
            ->get($endpoint, $query);

        $status = $resp->status();
        $ct     = $resp->header('Content-Type');
        $body   = (string) $resp->body();

        if (!$resp->ok()) {
            Log::warning('Namecheap HTTP error', [
                'status' => $status,
                'ct'     => $ct,
                'snippet' => mb_substr($body, 0, 400),
            ]);
            return [
                'ok'        => false,
                'reason'    => 'http_error',
                'http_code' => $status,
                'message'   => "خطأ HTTP ($status). تحقق من Whitelist IP و/أو endpoint.",
            ];
        }

        if (!is_string($ct) || stripos($ct, 'xml') === false) {
            Log::warning('Namecheap non-XML response', [
                'status' => $status,
                'ct'     => $ct,
                'snippet' => mb_substr($body, 0, 400),
            ]);
            return [
                'ok'      => false,
                'reason'  => 'non_xml',
                'message' => 'استجابة غير بصيغة XML (قد تكون HTML/Proxy).',
            ];
        }

        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $xml  = @simplexml_load_string($body, 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
        if ($xml === false) {
            Log::warning('Namecheap XML parse failed', [
                'status'  => $status,
                'ct'      => $ct,
                'snippet' => mb_substr($body, 0, 400),
            ]);
            return [
                'ok'      => false,
                'reason'  => 'xml_parse_error',
                'message' => 'فشل تحليل XML.',
            ];
        }

        // التحقق من Status="OK"
        $xml->registerXPathNamespace('nc', 'http://api.namecheap.com/xml.response');
        $statusAttr = (string)($xml['Status'] ?? '');
        if (strcasecmp($statusAttr, 'OK') !== 0) {
            $errNode = $xml->xpath('//nc:Errors/nc:Error')[0] ?? null;
            $errMsg  = $errNode ? (string)$errNode : 'خطأ من مزوّد Namecheap.';
            return [
                'ok'      => false,
                'reason'  => 'provider_error',
                'message' => $errMsg,
                'xml'     => $xml,
            ];
        }

        return ['ok' => true, 'xml' => $xml];
    }

    /** الرصيد */
    public function getBalance(): array
    {
        try {
            $r = $this->request(['Command' => 'namecheap.users.getBalances']);
            if (!$r['ok']) return array_merge($r, ['balance' => null, 'currency' => null]);

            /** @var \SimpleXMLElement $xml */
            $xml = $r['xml'];
            $node = $xml->xpath('//nc:UserGetBalancesResult')[0] ?? null;
            if (!$node) {
                return [
                    'ok'       => false,
                    'reason'   => 'missing_result_node',
                    'message'  => 'العنصر UserGetBalancesResult غير موجود في الاستجابة.',
                    'balance'  => null,
                    'currency' => null,
                ];
            }

            $attrs = $node->attributes();
            return [
                'ok'        => true,
                'reason'    => 'ok',
                'message'   => 'تم جلب الرصيد بنجاح.',
                'balance'   => isset($attrs->AccountBalance) ? (float)$attrs->AccountBalance : null,
                'available' => isset($attrs->AvailableBalance) ? (float)$attrs->AvailableBalance : null,
                'currency'  => isset($attrs->Currency) ? (string)$attrs->Currency : null,
            ];
        } catch (\Throwable $e) {
            Log::error('Namecheap getBalance exception', ['error' => $e->getMessage()]);
            return [
                'ok'      => false,
                'reason'  => 'exception',
                'message' => 'استثناء أثناء جلب الرصيد: ' . $e->getMessage(),
                'balance' => null,
                'currency' => null,
            ];
        }
    }

    /** استدعاء عام لأوامر Namecheap */
    public function callGeneric(string $command, array $extraParams = []): array
    {
        try {
            $r = $this->request(array_merge(['Command' => $command], $extraParams));
            if (!$r['ok']) return $r;
            return ['ok' => true, 'xml' => $r['xml']];
        } catch (\Throwable $e) {
            Log::error('Namecheap callGeneric exception', ['command' => $command, 'error' => $e->getMessage()]);
            return ['ok' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    public function setCustomNameservers(string $fqdn, array $nameservers): array
    {
        [$sld, $tld] = $this->splitDomainParts($fqdn);

        if (!$sld || !$tld) {
            return [
                'ok' => false,
                'reason' => 'invalid_domain',
                'message' => 'تعذر تقسيم النطاق إلى SLD/TLD لإرسال إعدادات الـ DNS.',
            ];
        }

        $payload = [
            'Command' => 'namecheap.domains.dns.setCustom',
            'SLD' => $sld,
            'TLD' => $tld,
            'Nameservers' => implode(',', array_slice($nameservers, 0, 12)),
        ];

        return $this->request($payload);
    }

    public function getNameservers(string $fqdn): array
    {
        [$sld, $tld] = $this->splitDomainParts($fqdn);

        if (!$sld || !$tld) {
            return [
                'ok' => false,
                'reason' => 'invalid_domain',
                'message' => '�?�?���? �?�?�?�?�? �?�?�?���?�? �?�?�? SLD/TLD �?�?�?�?�?�? �?�?�?�?�?�?�? �?�?�? DNS.',
            ];
        }

        $response = $this->request([
            'Command' => 'namecheap.domains.dns.getList',
            'SLD' => $sld,
            'TLD' => $tld,
        ]);

        if (($response['ok'] ?? false) === false) {
            return $response;
        }

        $xml = $response['xml'];

        $nameservers = [];
        $isUsingDefault = null;

        if (isset($xml->CommandResponse->DomainDNSGetListResult)) {
            $resultNode = $xml->CommandResponse->DomainDNSGetListResult;
            $attr = $resultNode->attributes();
            if ($attr && isset($attr['IsUsingOurDNS'])) {
                $isUsingDefault = strtolower((string) $attr['IsUsingOurDNS']) === 'true';
            }

            foreach ($resultNode->Nameserver ?? [] as $node) {
                $value = trim((string) $node);
                if ($value !== '') {
                    $nameservers[] = strtolower($value);
                }
            }
        }

        if (empty($nameservers)) {
            $fallback = $xml->xpath('//*[local-name()="Nameserver"]') ?: [];
            foreach ($fallback as $node) {
                $value = trim((string) $node);
                if ($value !== '') {
                    $nameservers[] = strtolower($value);
                }
            }
        }

        return [
            'ok' => true,
            'nameservers' => array_values(array_unique($nameservers)),
            'is_using_default' => $isUsingDefault,
            'xml' => $xml,
        ];
    }

    protected function splitDomainParts(string $fqdn): array
    {
        $fqdn = strtolower(trim($fqdn));
        if (!str_contains($fqdn, '.')) {
            return [null, null];
        }

        $parts = explode('.', $fqdn, 2);

        $sld = isset($parts[0]) ? Str::of($parts[0])->ascii()->trim()->value() : null;
        $tld = isset($parts[1]) ? Str::of($parts[1])->ascii()->trim()->value() : null;

        return [$sld ?: null, $tld ?: null];
    }
}
