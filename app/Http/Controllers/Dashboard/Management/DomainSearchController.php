<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use App\Models\DomainTldPrice;
use App\Services\DomainProviders\EnomClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainSearchController extends Controller
{
    /** صفحة بسيطة (اختياري) */
    public function page()
    {
        return view('domains.search');
    }

    /** API: فحص توافر الدومينات + إرجاع أرخص سعر من كل المزوّدين (بدون كشف الأسماء) */
    public function check(Request $req)
    {
        $started = microtime(true);

        // 1) تطبيع قائمة الدومينات المطلوبة
        $domains = $this->normalizeDomains($req);
        if (empty($domains)) {
            return response()->json([
                'ok'      => false,
                'message' => 'يرجى إدخال اسم دومين صحيح.',
            ], 422);
        }

        // 2) اختر مزوّد واحد للفحص السريع (نعطي أولوية لاسمشيپ)، بدون كشف اسمه للواجهة
        $provider = DomainProvider::active()
            ->whereIn('type', ['namecheap', 'enom'])
            ->orderByRaw("FIELD(type,'namecheap','enom')")
            ->first();

        if (!$provider) {
            return response()->json([
                'ok'      => false,
                'message' => 'لا يوجد مزوّد دومينات فعّال.',
            ], 422);
        }

        // 3) فحص التوافر فقط
        $check = $provider->type === 'namecheap'
            ? $this->namecheapCheck($provider, $domains)
            : $this->enomCheck($provider, $domains);

        if (!($check['ok'] ?? false)) {
            $durationMs = (int) round((microtime(true) - $started) * 1000);
            return response()->json([
                'ok'          => false,
                'message'     => $check['message'] ?? 'تعذّر الفحص.',
                'reason'      => $check['reason']  ?? 'provider_error',
                'duration_ms' => $durationMs,
                'results'     => [],
                'fetched_at'  => now()->toIso8601String(),
            ], 422);
        }

        // 4) خرّج خريطة التوافر {domain => [available,is_premium, premium_price?]}
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
        // أي نطاق لم يرجع من المزود، اعتبره غير متاح
        foreach ($domains as $d) {
            $k = strtolower($d);
            if (!isset($availability[$k])) {
                $availability[$k] = ['available' => false, 'is_premium' => false, 'premium_price' => null, 'premium_currency' => null];
            }
        }

        // 5) أرخص سعر (sale ثم cost) لكل TLD من قاعدة البيانات (register/1y) عبر جميع المزوّدين الفعّالين
        $tlds = array_unique(array_map(fn($d) => strtolower(pathinfo($d, PATHINFO_EXTENSION)), $domains));
        $bestPriceByTld = $this->bestPricesForTlds($tlds);

        // 6) تركيب النتائج النهائية (بدون كشف اسم أي مزوّد)
        $results = [];
        foreach ($domains as $domain) {
            $key = strtolower($domain);
            $tld = strtolower(pathinfo($domain, PATHINFO_EXTENSION));
            $availRow = $availability[$key];

            // السعر المعروض:
            // - إن كان Premium ونيم شيب أرسل سعر بريميوم → نعرضه كما هو (قد يختلف عن الجدول).
            // - غير ذلك → نعرض أرخص سعر من جدولنا (sale ثم cost).
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
            'message'     => $check['message'] ?? 'تم.',
            'reason'      => $check['reason']  ?? 'ok',
            // لا نرجّع اسم مزوّد:
            // 'provider' محذوف عمداً
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
                'ApiKey'     => trim((string)$p->api_key),     // مفكوك تلقائيًا عبر casts
                'UserName'   => trim((string)$p->username),
                'ClientIp'   => trim((string)$p->client_ip),   // يجب أن يكون مبيّضًا في لوحة Namecheap
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
                return ['ok' => false, 'message' => "HTTP {$resp->status()} أو استجابة غير XML", 'reason' => 'http_error'];
            }

            $xml = @simplexml_load_string((string)$resp->body(), 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
            if ($xml === false) {
                return ['ok' => false, 'message' => 'تعذر تحليل XML', 'reason' => 'xml_parse_error'];
            }

            $xml->registerXPathNamespace('nc', 'http://api.namecheap.com/xml.response');
            $statusAttr = (string)($xml['Status'] ?? '');

            if (strcasecmp($statusAttr, 'OK') !== 0) {
                $err = $xml->xpath('//nc:Errors/nc:Error')[0] ?? null;
                $msg = $err ? (string)$err : 'تعذّر تنفيذ الطلب.';
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
                    'price'      => $price,     // فقط للبريميوم إن توفّر
                    'currency'   => $currency,
                ];
            }

            return ['ok' => true, 'results' => $out, 'reason' => 'ok', 'message' => 'تم.'];
        } catch (\Throwable $e) {
            Log::error('Namecheap check exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'استثناء: ' . $e->getMessage(), 'reason' => 'exception'];
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
                    return ['ok' => false, 'message' => $r['message'] ?? 'تعذّر الفحص.', 'reason' => $r['reason'] ?? 'provider_error'];
                }

                $out[] = [
                    'domain'     => $fqdn,
                    'available'  => (bool)($r['available'] ?? null),
                    'is_premium' => false,
                    'price'      => null,
                    'currency'   => null,
                ];
            }

            return ['ok' => true, 'results' => $out, 'reason' => 'ok', 'message' => 'تم.'];
        } catch (\Throwable $e) {
            Log::error('Enom check exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'استثناء: ' . $e->getMessage(), 'reason' => 'exception'];
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
        // 1..63، أحرف/أرقام/شرطة، لا يبدأ أو ينتهي بشرطة
        return (bool) preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)$/', $s);
    }

    protected function isValidTld(string $tld): bool
    {
        return (bool) preg_match('/^(?:[a-z]{2,63}|[a-z0-9.-]{2,63})$/', $tld);
    }

    /** أقل سعر لكل TLD من جميع المزوّدين الفعّالين (register/1y)، بدون كشف أسماء */
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
            // نعتمد فقط المزوّدين الفعّالين و TLDs المفعّلة
            if (!$r->is_active || !$r->enabled) continue;

            // إن حبيت تقصرها على الموجود في الكتالوج فقط فعّل السطر التالي:
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
