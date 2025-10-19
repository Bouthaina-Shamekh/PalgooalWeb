<?php

namespace App\Services\DomainProviders;

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

    public function purchaseDomain(DomainProvider $p, array $params): array
    {
        $payload = array_merge([
            'command' => 'Purchase',
            'UseDNS' => 'default',
        ], $params);

        return $this->request($p, $payload);
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
            $payload['HostName' . $position] = $nameserver;
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



