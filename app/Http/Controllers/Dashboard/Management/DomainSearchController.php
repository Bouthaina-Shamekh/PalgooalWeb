<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;

use App\Models\DomainProvider;
use App\Services\DomainProviders\EnomClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DomainSearchController extends Controller
{
    /** صفحة بسيطة (اختياري) */
    public function page()
    {
        return view('domains.search');
    }

    /** API: فحص توافر دومينات (batch) */
    public function check(Request $req)
    {
        $q           = trim((string) $req->query('q', ''));
        $tldsIn      = trim((string) $req->query('tlds', ''));
        $domainsParam = trim((string) $req->query('domains', ''));

        // جهّز قائمة الدومينات
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

        $domains = array_values(array_unique($domains));
        if (empty($domains)) {
            return response()->json([
                'ok' => false,
                'message' => 'يرجى إدخال اسم دومين صحيح.',
            ], 422);
        }

        // اختر مزوّد فعّال (نعطي أولوية لـ namecheap ثم enom)
        $provider = DomainProvider::active()
            ->whereIn('type', ['namecheap', 'enom'])
            ->orderByRaw("FIELD(type,'namecheap','enom')")
            ->first();

        if (!$provider) {
            return response()->json([
                'ok' => false,
                'message' => 'لا يوجد مزوّد دومينات فعّال.',
            ], 422);
        }

        $started = microtime(true);

        if ($provider->type === 'namecheap') {
            $res = $this->namecheapCheck($provider, $domains);
        } else { // enom
            $res = $this->enomCheck($provider, $domains);
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        if (!($res['ok'] ?? false)) {
            return response()->json([
                'ok'          => false,
                'message'     => $res['message'] ?? 'تعذّر الفحص.',
                'reason'      => $res['reason'] ?? 'provider_error',
                'provider'    => $provider->type,
                'duration_ms' => $durationMs,
                'results'     => [],
                'fetched_at'  => now()->toIso8601String(),
            ], 422);
        }

        return response()->json([
            'ok'          => true,
            'message'     => $res['message'] ?? 'تم.',
            'reason'      => $res['reason'] ?? 'ok',
            'provider'    => $provider->type,
            'duration_ms' => $durationMs,
            'results'     => $res['results'] ?? [],
            'fetched_at'  => now()->toIso8601String(),
        ], 200);
    }

    /* ===================== Namecheap ===================== */

    protected function namecheapCheck(DomainProvider $p, array $domains): array
    {
        try {
            $endpoint = $this->namecheapEndpoint($p);
            $params = [
                'ApiUser'  => trim((string)$p->username),
                'ApiKey'   => trim((string)$p->api_key),     // مفكوك تلقائيًا عبر casts
                'UserName' => trim((string)$p->username),
                'ClientIp' => trim((string)$p->client_ip),   // يجب أن يكون مبيّضًا في لوحة Namecheap
                'Command'  => 'namecheap.domains.check',
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
                $attrs = $n->attributes();
                $domain    = (string)($attrs->Domain ?? '');
                $available = strtolower((string)($attrs->Available ?? '')) === 'true';
                $isPremium = strtolower((string)($attrs->IsPremiumName ?? '')) === 'true';

                $price    = null;
                $currency = null;
                if ($isPremium) {
                    // إن وُجدت أسعار بريميوم في الرد
                    if (isset($attrs->PremiumRegistrationPrice) && (string)$attrs->PremiumRegistrationPrice !== '') {
                        $price = (float)$attrs->PremiumRegistrationPrice;
                        $currency = 'USD';
                    }
                }

                $out[] = [
                    'domain'     => $domain,
                    'available'  => $available,
                    'is_premium' => $isPremium,
                    'price'      => $price,
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
                if (!($r['ok'] ?? true)) {
                    return ['ok' => false, 'message' => $r['message'] ?? 'تعذّر الفحص.', 'reason' => $r['reason'] ?? 'provider_error'];
                }

                $out[] = [
                    'domain'     => $fqdn,
                    'available'  => (bool)($r['available'] ?? null),
                    'is_premium' => null,
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
}
