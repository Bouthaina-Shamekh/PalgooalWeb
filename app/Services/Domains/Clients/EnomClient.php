<?php

namespace App\Services\Domains\Clients;

use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnomClient
{
    protected function endpointFor(DomainProvider $p): string
    {
        return $p->mode === 'test'
            ? 'https://resellertest.enom.com/interface.asp'
            : 'https://reseller.enom.com/interface.asp';
    }

    /** ?د???ز???ز?è?ذ: ApiToken -> ApiKey -> UID/PW */
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

    /** ?????ذ HTTP ???د?à ?ح???ë eNom */
    protected function request(DomainProvider $p, array $params): array
    {
        $endpoint = $this->endpointFor($p);
        $query    = array_merge($this->baseParams($p), $params);

        $t0   = microtime(true);
        $resp = Http::withHeaders([
            'Accept'     => 'application/xml',
            'User-Agent' => 'PalgoalsBot/1.0',
        ])
            ->withOptions([
                'curl' => [\CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4],
            ])
            ->connectTimeout(6)->timeout(15)->retry(1, 200)
            ->get($endpoint, $query);

        $ms   = (int)round((microtime(true) - $t0) * 1000);
        $ct   = (string)$resp->header('Content-Type');
        $body = (string)$resp->body();

        if (!$resp->ok()) {
            Log::warning('Enom HTTP error', ['status' => $resp->status(), 'ms' => $ms, 'snippet' => mb_substr($body, 0, 300)]);
            return ['ok' => false, 'reason' => 'http_error', 'http_code' => $resp->status(), 'message' => "HTTP {$resp->status()}"];
        }
        if (stripos($ct, 'xml') === false) {
            Log::warning('Enom non-XML', ['ms' => $ms, 'ct' => $ct, 'snippet' => mb_substr($body, 0, 300)]);
            return ['ok' => false, 'reason' => 'non_xml', 'message' => '?د???ز?ش?د?ذ?ر ???è?? ?ذ???è???ر XML.'];
        }

        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $xml  = @simplexml_load_string($body, 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOERROR | \LIBXML_NOWARNING);
        if ($xml === false) {
            Log::warning('Enom XML parse failed', ['ms' => $ms, 'snippet' => mb_substr($body, 0, 300)]);
            return ['ok' => false, 'reason' => 'xml_parse_error', 'message' => '?????? ?ز?ص???è?? XML.'];
        }

        if ((int)($xml->ErrCount ?? 0) > 0) {
            $errors = [];
            if (isset($xml->errors)) foreach ($xml->errors->children() as $e) $errors[] = trim((string)$e);
            if (isset($xml->responses->response->ResponseString)) $errors[] = trim((string)$xml->responses->response->ResponseString);
            $msg = $errors ? implode(' | ', $errors) : 'Unknown eNom API error';
            return ['ok' => false, 'reason' => 'provider_error', 'message' => $msg, 'xml' => $xml];
        }

        $responseSummaries = [];
        if (isset($xml->responses)) {
            foreach ($xml->responses->children() as $response) {
                $code = isset($response->ResponseNumber) ? (int) $response->ResponseNumber : null;
                $msg  = isset($response->ResponseString) ? trim((string) $response->ResponseString) : null;
                $responseSummaries[] = ['code' => $code, 'message' => $msg];

                if ($code !== null && $code >= 300) {
                    $human = $msg ?: 'eNom rejected the request.';
                    return [
                        'ok' => false,
                        'reason' => 'provider_response',
                        'message' => $human,
                        'code' => $code,
                        'xml' => $xml,
                    ];
                }
            }
        }

        if (isset($xml->RRPCode) && (int) $xml->RRPCode >= 3000) {
            $rrpMessage = isset($xml->RRPText) ? trim((string) $xml->RRPText) : 'Registrar returned an error.';
            return [
                'ok' => false,
                'reason' => 'rrp_error',
                'message' => $rrpMessage,
                'code' => (int) $xml->RRPCode,
                'xml' => $xml,
            ];
        }

        Log::debug('Enom command response', [
            'command' => $params['command'] ?? null,
            'ms' => $ms,
            'responses' => $responseSummaries,
        ]);

        return ['ok' => true, 'xml' => $xml];
    }

    /** ?د???????è?» */
    public function getBalance(DomainProvider $p): array
    {
        try {
            $r = $this->request($p, ['command' => 'GetBalance']);
            if (!$r['ok']) return array_merge($r, ['balance' => null, 'currency' => null]);

            $xml = $r['xml'];
            $balance = null;
            $currency = null;

            if (isset($xml->GetBalance)) {
                $gb = $xml->GetBalance;
                foreach (['AvailableBalance', 'AccountBalance', 'Balance'] as $k) {
                    if (isset($gb->{$k}) && (string)$gb->{$k} !== '') {
                        $balance = (float)$gb->{$k};
                        break;
                    }
                }
                foreach (['Currency', 'currency'] as $k) {
                    if (isset($gb->{$k}) && (string)$gb->{$k} !== '') {
                        $currency = (string)$gb->{$k};
                        break;
                    }
                }
            } else {
                $s = $xml->asXML() ?: '';
                if (preg_match('/<(?:AvailableBalance|AccountBalance|balance)>([0-9]+(?:\.[0-9]+)?)</i', $s, $m)) $balance = (float)$m[1];
                if (preg_match('/<currency>([A-Z]{3})</i', $s, $m2)) $currency = strtoupper($m2[1]);
            }

            return ['ok' => true, 'reason' => 'ok', 'message' => '?ز?à ?ش???ذ ?د???????è?» ?ذ???ش?د?ص.', 'balance' => $balance, 'currency' => $currency];
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'exception', 'message' => $e->getMessage(), 'balance' => null, 'currency' => null];
        }
    }

    /** ?ز?ص?ê?è?? ???ê?? ?د?????à???è?ر */
    protected function productType(string $action): ?int
    {
        $map = ['register' => 10, 'renew' => 16, 'transfer' => 19];
        $k = strtolower($action);
        return $map[$k] ?? null;
    }

    /** Parser ?????ز?د?خ?ش <productprice> */
    protected function parsePriceXml(\SimpleXMLElement $xml): array
    {
        $price = null;
        $enabled = null;
        $currency = null;

        if (isset($xml->productprice)) {
            if (isset($xml->productprice->price) && (string)$xml->productprice->price !== '') {
                $price = (float)$xml->productprice->price;
            }
            if (isset($xml->productprice->productenabled)) {
                $s = strtolower((string)$xml->productprice->productenabled);
                $enabled = in_array($s, ['true', '1', 'yes'], true);
            }
            if (isset($xml->productprice->currency) && (string)$xml->productprice->currency !== '') {
                $currency = (string)$xml->productprice->currency;
            }
        } else {
            $s = $xml->asXML() ?: '';
            if (preg_match('/<price>([0-9]+(?:\.[0-9]+)?)<\/price>/i', $s, $m)) $price = (float)$m[1];
            if (preg_match('/<productenabled>([^<]+)<\/productenabled>/i', $s, $m2)) $enabled = in_array(strtolower(trim($m2[1])), ['true', '1', 'yes'], true);
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
        return ['ok' => ($price !== null), 'price' => $price, 'enabled' => $enabled, 'currency' => $currency, 'source' => 'product', 'raw' => $r['xml']];
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
        return ['ok' => ($price !== null), 'price' => $price, 'enabled' => $enabled, 'currency' => $currency, 'source' => 'retail', 'raw' => $r['xml']];
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
        return ['ok' => ($price !== null), 'price' => $price, 'enabled' => $enabled, 'currency' => $currency, 'source' => 'reseller', 'raw' => $r['xml']];
    }

    /** ?à?ص?د?ê???ر ?د???ص???ê?? ?????ë ?ث?è ?????? ?à?ز?د?ص */
    public function getAnyPrice(DomainProvider $p, string $tld, string $action, int $years = 1): array
    {
        $first = $this->getProductPrice($p, $tld, $action, $years);
        if ($first['ok'] && $first['price'] !== null) return $first;

        $second = $this->getRetailPrice($p, $tld, $action, $years);
        if ($second['ok'] && $second['price'] !== null) return $second;

        $third = $this->getResellerPrice($p, $tld, $action, $years);
        return $third; // fallback ?ث?«?è??
    }

    /**
     * فحص توفر دومين واحد عبر أمر Enom "Check".
     * لا تنفّذ Retry أو Batching هنا؛ التكرار على عدة دومينات مسؤولية المستدعي (DomainSearchController::enomCheck()).
     */
    public function checkAvailability(DomainProvider $provider, string $sld, string $tld): array
    {
        try {
            $sld = trim($sld);
            $tld = ltrim(trim($tld), '.');

            if ($sld === '' || $tld === '' || str_contains($sld, '.')) {
                return [
                    'ok' => false,
                    'available' => null,
                    'reason' => 'invalid_input',
                    'message' => 'Invalid domain name.',
                ];
            }

            $r = $this->request($provider, [
                'command' => 'Check',
                'SLD' => $sld,
                'TLD' => $tld,
            ]);

            if (!($r['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'available' => null,
                    'reason' => $r['reason'] ?? 'provider_error',
                    'message' => $r['message'] ?? 'eNom rejected the request.',
                ];
            }

            $available = $this->parseAvailability($r['xml'] ?? null);

            if ($available === null) {
                return [
                    'ok' => false,
                    'available' => null,
                    'reason' => 'unexpected_response',
                    'message' => 'Unable to determine domain availability.',
                ];
            }

            return [
                'ok' => true,
                'available' => $available,
                'reason' => 'ok',
                'message' => $available ? 'Domain is available.' : 'Domain is not available.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'available' => null,
                'reason' => 'exception',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * يحدد التوفر من استجابة XML: أولاً حقل صريح "Available" (أي حالة أحرف/أي موضع)،
     * ثم fallback على RRPText إن لم يوجد حقل صريح. يعيد null إن تعذّر الاستنتاج بثقة.
     */
    protected function parseAvailability(?\SimpleXMLElement $xml): ?bool
    {
        if ($xml === null) {
            return null;
        }

        $explicit = $this->findFieldValue($xml, 'Available');
        if ($explicit !== null) {
            return $this->interpretAvailableText($explicit);
        }

        $rrpText = $this->findFieldValue($xml, 'RRPText');
        if ($rrpText !== null) {
            $low = strtolower($rrpText);
            if (str_contains($low, 'not available') || str_contains($low, 'unavailable')) {
                return false;
            }
            if (str_contains($low, 'available')) {
                return true;
            }
        }

        return null;
    }

    /** يفسّر نص/قيمة حقل Available الصريح إلى true/false، أو null إن كانت القيمة غامضة */
    protected function interpretAvailableText(string $value): ?bool
    {
        $v = strtolower(trim($value));

        $trueValues  = ['true', '1', 'yes', 'available'];
        $falseValues = ['false', '0', 'no', 'not available', 'unavailable'];

        if (in_array($v, $trueValues, true)) {
            return true;
        }
        if (in_array($v, $falseValues, true)) {
            return false;
        }

        // نص أطول قد يتضمن العبارة داخل جملة كاملة: تحقّق من "not available" أولاً لتفادي تطابق خاطئ
        if (str_contains($v, 'not available') || str_contains($v, 'unavailable')) {
            return false;
        }
        if (str_contains($v, 'available')) {
            return true;
        }

        return null;
    }

    /** بحث متكرر (case-insensitive) عن عنصر أو خاصية باسم معيّن داخل شجرة XML، بأي عمق/موضع */
    protected function findFieldValue(\SimpleXMLElement $node, string $fieldName): ?string
    {
        foreach ($node->attributes() as $attrName => $attrVal) {
            if (strcasecmp((string) $attrName, $fieldName) === 0) {
                $val = trim((string) $attrVal);
                if ($val !== '') return $val;
            }
        }

        foreach ($node->children() as $childName => $child) {
            if (strcasecmp((string) $childName, $fieldName) === 0) {
                $val = trim((string) $child);
                if ($val !== '') return $val;
            }
        }

        foreach ($node->children() as $child) {
            $found = $this->findFieldValue($child, $fieldName);
            if ($found !== null) return $found;
        }

        return null;
    }

    public function purchaseDomain(DomainProvider $p, array $params): array
    {
        $payload = array_merge([
            'command' => 'Purchase',
            'UseDNS' => 'default',
        ], $params);

        return $this->request($p, $payload);
    }

    public function renewDomain(DomainProvider $p, string $fqdn, int $years = 1): array
    {
        [$sld, $tld] = $this->splitDomainParts($fqdn);

        if (!$sld || !$tld) {
            return [
                'ok' => false,
                'reason' => 'invalid_domain',
                'message' => 'Unable to split domain into SLD/TLD for renewal.',
            ];
        }

        return $this->request($p, [
            'command' => 'Extend',
            'SLD' => $sld,
            'TLD' => $tld,
            'NumYears' => max(1, $years),
        ]);
    }

    public function checkNameserverStatus(DomainProvider $p, string $nameserver): array
    {
        $response = $this->request($p, [
            'command' => 'CheckNSStatus',
            'CheckNSName' => strtolower(trim($nameserver)),
        ]);

        if (!($response['ok'] ?? false)) {
            return $response;
        }

        $xml = $response['xml'];
        $success = (int) ($xml->NsCheckSuccess ?? 0) === 1;
        $currentIp = isset($xml->CheckNsStatus->ipaddress) ? trim((string) $xml->CheckNsStatus->ipaddress) : null;

        return [
            'ok' => true,
            'exists' => $success,
            'ip' => $currentIp ?: null,
            'xml' => $xml,
        ];
    }

    public function registerNameserver(DomainProvider $p, string $nameserver, string $ip): array
    {
        return $this->request($p, [
            'command' => 'RegisterNameServer',
            'Add' => 'true',
            'NSName' => strtolower(trim($nameserver)),
            'IP' => trim($ip),
        ]);
    }

    public function updateNameserverIp(DomainProvider $p, string $nameserver, string $oldIp, string $newIp): array
    {
        return $this->request($p, [
            'command' => 'UpdateNameServer',
            'NS' => strtolower(trim($nameserver)),
            'OldIP' => trim($oldIp),
            'NewIP' => trim($newIp),
        ]);
    }

    public function updateNameservers(DomainProvider $p, string $fqdn, array $nameservers): array
    {
        [$sld, $tld] = $this->splitDomainParts($fqdn);

        if (!$sld || !$tld) {
            return [
                'ok' => false,
                'reason' => 'invalid_domain',
                'message' => 'Unable to split domain into SLD/TLD for nameserver update.',
            ];
        }

        $payload = [
            'command' => 'ModifyNS',
            'SLD' => $sld,
            'TLD' => $tld,
            'UseDNS' => 'custom',
            'CustomDNS' => '1',
        ];

        foreach (array_slice(array_values($nameservers), 0, 12) as $index => $nameserver) {
            $position = $index + 1;
            $payload['NS' . $position] = $nameserver;
        }

        return $this->request($p, $payload);
    }

    public function getDns(DomainProvider $p, string $fqdn): array
    {
        [$sld, $tld] = $this->splitDomainParts($fqdn);

        if (!$sld || !$tld) {
            return [
                'ok' => false,
                'reason' => 'invalid_domain',
                'message' => 'Unable to split domain into SLD/TLD for GetDNS request.',
            ];
        }

        $dnsResponse = $this->request($p, [
            'command' => 'GetDNS',
            'SLD' => $sld,
            'TLD' => $tld,
        ]);

        if (($dnsResponse['ok'] ?? false) === false) {
            $dnsResponse = null;
        }

        $infoResponse = $this->request($p, [
            'command' => 'GetDomainInfo',
            'SLD' => $sld,
            'TLD' => $tld,
        ]);

        if (($infoResponse['ok'] ?? false) === false) {
            return $infoResponse;
        }

        $xml = $infoResponse['xml'];
        $useDns = strtolower(trim((string) ($xml->UseDNS ?? '')));
        $nameservers = [];

        for ($i = 1; $i <= 12; $i++) {
            $candidates = [
                'host' . $i,
                'Host' . $i,
                'ns' . $i,
                'Ns' . $i,
                'dns' . $i,
                'Dns' . $i,
                'nameserver' . $i,
                'Nameserver' . $i,
                'nameServer' . $i,
                'NameServer' . $i,
            ];

            foreach ($candidates as $key) {
                if (isset($xml->{$key})) {
                    $value = trim((string) $xml->{$key});
                    if ($value !== '') {
                        $nameservers[] = $value;
                        break;
                    }
                }
            }
        }

        if (empty($nameservers) && $dnsResponse) {
            $dnsXml = $dnsResponse['xml'];
            if (isset($dnsXml->dns) && isset($dnsXml->dns->entry)) {
                foreach ($dnsXml->dns->entry as $entry) {
                    $value = trim((string) ($entry->hostname ?? $entry->host ?? ''));
                    if ($value !== '') {
                        $nameservers[] = $value;
                    }
                }
            }
        }

        return [
            'ok' => true,
            'use_dns' => $useDns !== '' ? $useDns : null,
            'nameservers' => array_values(array_unique($nameservers)),
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
