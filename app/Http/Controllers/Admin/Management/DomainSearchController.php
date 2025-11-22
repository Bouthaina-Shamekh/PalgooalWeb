<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use App\Models\DomainTldPrice;
use App\Services\Domains\Clients\EnomClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainSearchController extends Controller
{
    /** طµظپط­ط© ط¨ط³ظٹط·ط© (ط§ط®طھظٹط§ط±ظٹ) */
    public function page()
    {
        return view('domains.search');
    }

    /** API: ظپط­طµ طھظˆط§ظپط± ط§ظ„ط¯ظˆظ…ظٹظ†ط§طھ + ط¥ط±ط¬ط§ط¹ ط£ط±ط®طµ ط³ط¹ط± ظ…ظ† ظƒظ„ ط§ظ„ظ…ط²ظˆظ‘ط¯ظٹظ† (ط¨ط¯ظˆظ† ظƒط´ظپ ط§ظ„ط£ط³ظ…ط§ط،) */
    public function check(Request $req)
    {
        $started = microtime(true);

        // 1) طھط·ط¨ظٹط¹ ظ‚ط§ط¦ظ…ط© ط§ظ„ط¯ظˆظ…ظٹظ†ط§طھ ط§ظ„ظ…ط·ظ„ظˆط¨ط©
        $domains = $this->normalizeDomains($req);
        if (empty($domains)) {
            return response()->json([
                'ok'      => false,
                'message' => 'ظٹط±ط¬ظ‰ ط¥ط¯ط®ط§ظ„ ط§ط³ظ… ط¯ظˆظ…ظٹظ† طµط­ظٹط­.',
            ], 422);
        }

        // 2) ط§ط®طھط± ظ…ط²ظˆظ‘ط¯ ظˆط§ط­ط¯ ظ„ظ„ظپط­طµ ط§ظ„ط³ط±ظٹط¹ (ظ†ط¹ط·ظٹ ط£ظˆظ„ظˆظٹط© ظ„ط§ط³ظ…ط´ظٹظ¾)طŒ ط¨ط¯ظˆظ† ظƒط´ظپ ط§ط³ظ…ظ‡ ظ„ظ„ظˆط§ط¬ظ‡ط©
        $provider = DomainProvider::active()
            ->whereIn('type', ['namecheap', 'enom'])
            ->orderByRaw("FIELD(type,'namecheap','enom')")
            ->first();

        if (!$provider) {
            return response()->json([
                'ok'      => false,
                'message' => 'ظ„ط§ ظٹظˆط¬ط¯ ظ…ط²ظˆظ‘ط¯ ط¯ظˆظ…ظٹظ†ط§طھ ظپط¹ظ‘ط§ظ„.',
            ], 422);
        }

        // 3) ظپط­طµ ط§ظ„طھظˆط§ظپط± ظپظ‚ط·
        $check = $provider->type === 'namecheap'
            ? $this->namecheapCheck($provider, $domains)
            : $this->enomCheck($provider, $domains);

        if (!($check['ok'] ?? false)) {
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            return response()->json([
                'ok'          => false,
                'message'     => $check['message'] ?? 'طھط¹ط°ظ‘ط± ط§ظ„ظپط­طµ.',
                'reason'      => $check['reason']  ?? 'provider_error',
                'duration_ms' => $durationMs,
                'results'     => [],
                'fetched_at'  => now()->toIso8601String(),
            ], 422);
        }

        // 4) ط®ط±ظ‘ط¬ ط®ط±ظٹط·ط© ط§ظ„طھظˆط§ظپط± {domain => [available,is_premium, premium_price?]}
        $availability = [];
        foreach ($check['results'] ?? [] as $row) {
            $key = strtolower((string)($row['domain'] ?? ''));
            if ($key !== '') {
                $availability[$key] = [
                    'available'   => (bool)($row['available'] ?? false),
                    'is_premium'  => (bool)($row['is_premium'] ?? false),
                    'premium_price' => $row['price']   ?? null,
                    'premium_currency' => $row['currency'] ?? null,
                ];
            }
        }
        // ط£ظٹ ظ†ط·ط§ظ‚ ظ„ظ… ظٹط±ط¬ط¹ ظ…ظ† ط§ظ„ظ…ط²ظˆط¯طŒ ط§ط¹طھط¨ط±ظ‡ ط؛ظٹط± ظ…طھط§ط­
        foreach ($domains as $d) {
            $k = strtolower($d);
            if (!isset($availability[$k])) {
                $availability[$k] = ['available' => false, 'is_premium' => false, 'premium_price' => null, 'premium_currency' => null];
            }
        }

        // 5) ط£ط±ط®طµ ط³ط¹ط± (sale ط«ظ… cost) ظ„ظƒظ„ TLD ظ…ظ† ظ‚ط§ط¹ط¯ط© ط§ظ„ط¨ظٹط§ظ†ط§طھ (register/1y) ط¹ط¨ط± ط¬ظ…ظٹط¹ ط§ظ„ظ…ط²ظˆظ‘ط¯ظٹظ† ط§ظ„ظپط¹ظ‘ط§ظ„ظٹظ†
        $tlds = array_unique(array_map(fn($d) => strtolower(pathinfo($d, PATHINFO_EXTENSION)), $domains));
        $bestPriceByTld = $this->bestPricesForTlds($tlds);

        // 6) طھط±ظƒظٹط¨ ط§ظ„ظ†طھط§ط¦ط¬ ط§ظ„ظ†ظ‡ط§ط¦ظٹط© (ط¨ط¯ظˆظ† ظƒط´ظپ ط§ط³ظ… ط£ظٹ ظ…ط²ظˆظ‘ط¯)
        $results = [];
        foreach ($domains as $domain) {
            $key = strtolower($domain);
            $tld = strtolower(pathinfo($domain, PATHINFO_EXTENSION));
            $availRow = $availability[$key];

            // ط§ظ„ط³ط¹ط± ط§ظ„ظ…ط¹ط±ظˆط¶:
            // - ط¥ظ† ظƒط§ظ† Premium ظˆظ†ظٹظ… ط´ظٹط¨ ط£ط±ط³ظ„ ط³ط¹ط± ط¨ط±ظٹظ…ظٹظˆظ… â†’ ظ†ط¹ط±ط¶ظ‡ ظƒظ…ط§ ظ‡ظˆ (ظ‚ط¯ ظٹط®طھظ„ظپ ط¹ظ† ط§ظ„ط¬ط¯ظˆظ„).
            // - ط؛ظٹط± ط°ظ„ظƒ â†’ ظ†ط¹ط±ط¶ ط£ط±ط®طµ ط³ط¹ط± ظ…ظ† ط¬ط¯ظˆظ„ظ†ط§ (sale ط«ظ… cost).
            $price = null;
            $currency = null;

            if ($availRow['is_premium'] && $availRow['premium_price'] !== null) {
                $price    = (float)$availRow['premium_price'];
                $currency = $availRow['premium_currency'] ?: 'USD';
            } else {
                $best = $bestPriceByTld[$tld] ?? null;
                if ($best) {
                    $price    = $best['price'];
                    $currency = $best['currency'] ?? 'USD';
                }
            }

            $results[] = [
                'domain'     => $domain,
                'available'  => (bool)$availRow['available'],
                'is_premium' => (bool)$availRow['is_premium'],
                'price'      => $price,
                'currency'   => $currency ?? 'USD',
            ];
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        return response()->json([
            'ok'          => true,
            'message'     => $check['message'] ?? 'طھظ….',
            'reason'      => $check['reason']  ?? 'ok',
            // ظ„ط§ ظ†ط±ط¬ظ‘ط¹ ط§ط³ظ… ظ…ط²ظˆظ‘ط¯:
            // 'provider' ظ…ط­ط°ظˆظپ ط¹ظ…ط¯ط§ظ‹
            'duration_ms' => $durationMs,
            'results'     => $results,
            'fetched_at'  => now()->toIso8601String(),
        ], 200);
    }

    /* ===================== Namecheap ===================== */

    protected function namecheapCheck(DomainProvider $p, array $domains): array
    {
        try {
            $endpoint = $this->namecheapEndpoint($p);
            $params = [
                'ApiUser'    => trim((string)$p->username),
                'ApiKey'     => trim((string)$p->api_key),     // ظ…ظپظƒظˆظƒ طھظ„ظ‚ط§ط¦ظٹظ‹ط§ ط¹ط¨ط± casts
                'UserName'   => trim((string)$p->username),
                'ClientIp'   => trim((string)$p->client_ip),   // ظٹط¬ط¨ ط£ظ† ظٹظƒظˆظ† ظ…ط¨ظٹظ‘ط¶ظ‹ط§ ظپظٹ ظ„ظˆط­ط© Namecheap
                'Command'    => 'namecheap.domains.check',
                'DomainList' => implode(',', $domains),
            ];

            $resp = Http::withHeaders([
                'Accept'     => 'application/xml',
                'User-Agent' => 'PalgoalsBot/1.0',
            ])
                ->withOptions(['curl' => [\CURLOPT_IPRESOLVE => \CURL_IPRESOLVE_V4]])
                ->connectTimeout(5)->timeout(12)->retry(1, 200)
                ->get($endpoint, $params);

            if (!$resp->ok() || stripos((string)$resp->header('Content-Type'), 'xml') === false) {
                return ['ok' => false, 'message' => "HTTP {$resp->status()} ط£ظˆ ط§ط³طھط¬ط§ط¨ط© ط؛ظٹط± XML", 'reason' => 'http_error'];
            }

            $xml = @simplexml_load_string((string)$resp->body(), 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
            if ($xml === false) {
                return ['ok' => false, 'message' => 'طھط¹ط°ط± طھط­ظ„ظٹظ„ XML', 'reason' => 'xml_parse_error'];
            }

            $xml->registerXPathNamespace('nc', 'http://api.namecheap.com/xml.response');
            $statusAttr = (string)($xml['Status'] ?? '');

            if (strcasecmp($statusAttr, 'OK') !== 0) {
                $err = $xml->xpath('//nc:Errors/nc:Error')[0] ?? null;
                $msg = $err ? (string)$err : 'طھط¹ط°ظ‘ط± طھظ†ظپظٹط° ط§ظ„ط·ظ„ط¨.';
                return ['ok' => false, 'message' => $msg, 'reason' => 'provider_error'];
            }

            $nodes = $xml->xpath('//nc:DomainCheckResult') ?? [];
            $out = [];
            foreach ($nodes as $n) {
                $attrs      = $n->attributes();
                $domain     = (string)($attrs->Domain ?? '');
                $available  = strtolower((string)($attrs->Available ?? '')) === 'true';
                $isPremium  = strtolower((string)($attrs->IsPremiumName ?? '')) === 'true';

                $price      = null;
                $currency   = null;
                if ($isPremium) {
                    if (isset($attrs->PremiumRegistrationPrice) && (string)$attrs->PremiumRegistrationPrice !== '') {
                        $price    = (float)$attrs->PremiumRegistrationPrice;
                        $currency = 'USD';
                    }
                }

                $out[] = [
                    'domain'     => $domain,
                    'available'  => $available,
                    'is_premium' => $isPremium,
                    'price'      => $price,     // ظپظ‚ط· ظ„ظ„ط¨ط±ظٹظ…ظٹظˆظ… ط¥ظ† طھظˆظپظ‘ط±
                    'currency'   => $currency,
                ];
            }

            return ['ok' => true, 'results' => $out, 'reason' => 'ok', 'message' => 'طھظ….'];
        } catch (\Throwable $e) {
            Log::error('Namecheap check exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'ط§ط³طھط«ظ†ط§ط،: ' . $e->getMessage(), 'reason' => 'exception'];
        }
    }

    protected function namecheapEndpoint(DomainProvider $p): string
    {
        if (!empty($p->endpoint)) return rtrim((string)$p->endpoint, '/');
        return $p->mode === 'test'
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response';
    }

    /* ======================= Enom ======================= */

    protected function enomCheck(DomainProvider $p, array $domains): array
    {
        try {
            /** @var EnomClient $client */
            $client = app(EnomClient::class);
            $out = [];

            foreach ($domains as $fqdn) {
                [$sld, $tld] = $this->splitDomain($fqdn);
                if (!$sld || !$tld) {
                    $out[] = ['domain' => $fqdn, 'available' => null];
                    continue;
                }

                $r = $client->checkAvailability($p, $sld, $tld);
                if (!empty($r['ok']) === false && isset($r['message'])) {
                    return ['ok' => false, 'message' => $r['message'] ?? 'طھط¹ط°ظ‘ط± ط§ظ„ظپط­طµ.', 'reason' => $r['reason'] ?? 'provider_error'];
                }

                $out[] = [
                    'domain'     => $fqdn,
                    'available'  => (bool)($r['available'] ?? null),
                    'is_premium' => false,
                    'price'      => null,
                    'currency'   => null,
                ];
            }

            return ['ok' => true, 'results' => $out, 'reason' => 'ok', 'message' => 'طھظ….'];
        } catch (\Throwable $e) {
            Log::error('Enom check exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'ط§ط³طھط«ظ†ط§ط،: ' . $e->getMessage(), 'reason' => 'exception'];
        }
    }

    /* ====================== Helpers ====================== */

    protected function normalizeDomains(Request $req): array
    {
        $q            = trim((string) $req->query('q', ''));
        $tldsIn       = trim((string) $req->query('tlds', ''));
        $domainsParam = trim((string) $req->query('domains', ''));

        $domains = [];
        if ($domainsParam !== '') {
            $domains = array_filter(array_map('trim', explode(',', $domainsParam)));
        } elseif ($q !== '') {
            $sld  = strtolower($this->toAsciiLabel($q));
            $tlds = $tldsIn !== '' ? array_filter(array_map('trim', explode(',', strtolower($tldsIn)))) : ['com', 'net', 'org'];
            foreach ($tlds as $tld) {
                if ($this->isValidLabel($sld) && $this->isValidTld($tld)) {
                    $domains[] = $sld . '.' . $tld;
                }
            }
        }

        $domains = array_values(array_unique(array_map(function ($d) {
            $d = strtolower(trim($d));
            return (str_contains($d, '.') && strlen($d) <= 253) ? $d : null;
        }, $domains)));

        return array_filter($domains);
    }

    protected function splitDomain(string $fqdn): array
    {
        $fqdn = strtolower(trim($fqdn));
        if (!str_contains($fqdn, '.')) return [null, null];
        $parts = explode('.', $fqdn, 2);
        return [
            preg_replace('/[^a-z0-9-]/', '', $parts[0] ?? ''),
            preg_replace('/[^a-z0-9.-]/', '', $parts[1] ?? ''),
        ];
    }

    protected function toAsciiLabel(string $s): string
    {
        $s = trim($s);
        if ($s === '') return $s;
        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($s, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) return strtolower($ascii);
        }
        return strtolower($s);
    }

    protected function isValidLabel(string $s): bool
    {
        // 1..63طŒ ط£ط­ط±ظپ/ط£ط±ظ‚ط§ظ…/ط´ط±ط·ط©طŒ ظ„ط§ ظٹط¨ط¯ط£ ط£ظˆ ظٹظ†طھظ‡ظٹ ط¨ط´ط±ط·ط©
        return (bool) preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)$/', $s);
    }

    protected function isValidTld(string $tld): bool
    {
        return (bool) preg_match('/^(?:[a-z]{2,63}|[a-z0-9.-]{2,63})$/', $tld);
    }

    /** ط£ظ‚ظ„ ط³ط¹ط± ظ„ظƒظ„ TLD ظ…ظ† ط¬ظ…ظٹط¹ ط§ظ„ظ…ط²ظˆظ‘ط¯ظٹظ† ط§ظ„ظپط¹ظ‘ط§ظ„ظٹظ† (register/1y)طŒ ط¨ط¯ظˆظ† ظƒط´ظپ ط£ط³ظ…ط§ط، */
    protected function bestPricesForTlds(array $tlds): array
    {
        if (empty($tlds)) return [];

        $rows = DomainTldPrice::query()
            ->select([
                'domain_tld_prices.sale',
                'domain_tld_prices.cost',
                'domain_tlds.tld',
                'domain_tlds.currency',
                'domain_tlds.in_catalog',
                'domain_tlds.enabled',
                'domain_providers.is_active',
            ])
            ->join('domain_tlds', 'domain_tlds.id', '=', 'domain_tld_prices.domain_tld_id')
            ->join('domain_providers', 'domain_providers.id', '=', 'domain_tlds.provider_id')
            ->whereIn('domain_tlds.tld', $tlds)
            ->where('domain_tld_prices.action', 'register')
            ->where('domain_tld_prices.years', 1)
            ->get();

        $best = [];
        foreach ($rows as $r) {
            // ظ†ط¹طھظ…ط¯ ظپظ‚ط· ط§ظ„ظ…ط²ظˆظ‘ط¯ظٹظ† ط§ظ„ظپط¹ظ‘ط§ظ„ظٹظ† ظˆ TLDs ط§ظ„ظ…ظپط¹ظ‘ظ„ط©
            if (!$r->is_active || !$r->enabled) continue;

            // ط¥ظ† ط­ط¨ظٹطھ طھظ‚طµط±ظ‡ط§ ط¹ظ„ظ‰ ط§ظ„ظ…ظˆط¬ظˆط¯ ظپظٹ ط§ظ„ظƒطھط§ظ„ظˆط¬ ظپظ‚ط· ظپط¹ظ‘ظ„ ط§ظ„ط³ط·ط± ط§ظ„طھط§ظ„ظٹ:
            // if (!$r->in_catalog) continue;

            $price = $r->sale ?? $r->cost;
            if ($price === null) continue;

            $tldKey = strtolower($r->tld);
            if (!isset($best[$tldKey]) || (float)$price < $best[$tldKey]['price']) {
                $best[$tldKey] = [
                    'price'    => (float)$price,
                    'currency' => $r->currency ?: 'USD',
                ];
            }
        }

        return $best;
    }
}


