<?php

namespace App\Services\Domains;

use App\Models\DomainProvider;
use App\Services\Domains\Clients\EnomClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * مصدر فحص التوافر الموثوق الوحيد (Server Source of Truth للتوافر).
 *
 * يُستخرَج هذا الكود من App\Http\Controllers\Admin\Management\DomainSearchController
 * (كان محصوراً هناك كـ protected methods مرتبطة بمنطق Request/HTTP الخاص بذلك الـ Controller)
 * ليصبح قابلاً للاستدعاء الداخلي من أي مسار مالي (Cart/Checkout/شراء العميل) دون الحاجة
 * لاستدعاء Controller من Controller أو تمرير Request وهمي.
 *
 * لا يُغيَّر منطق الاتصال بالمزوّدين (Namecheap XML / Enom checkAvailability) عن الأصل إطلاقاً؛
 * هذا الملف نقل حرفي لنفس المنطق الموجود مسبقاً.
 */
class DomainAvailabilityService
{
    protected function pricing(): DomainPricingService
    {
        return app(DomainPricingService::class);
    }

    /**
     * يطبّع item_option ليحدد إن كانت العملية "تسجيل جديد" (تخضع لفحص التوافر) أم لا.
     * القيم المكافئة لتسجيل جديد: register / new. أي قيمة أخرى (renew/transfer/subdomain/existing/own)
     * لا تخضع لفحص التوافر لأنها ليست تسجيلاً جديداً لدومين.
     */
    public function isNewRegistrationOption(?string $itemOption): bool
    {
        $normalized = strtolower(trim((string) $itemOption));

        return in_array($normalized, ['register', 'new'], true);
    }

    /**
     * فحص توافر دفعة دومينات لدى مزوّد فعّال واحد — نفس منطق الاختيار والتفريع الأصلي
     * (namecheap له أولوية، ثم enom). العقد:
     * ['ok'=>bool,'reason'=>string,'message'=>string,'results'=>[{domain,available,is_premium,price,currency}]]
     */
    public function checkDomains(array $domains, ?DomainProvider $provider = null): array
    {
        $domains = array_values(array_unique(array_filter(array_map(
            fn ($d) => strtolower(trim((string) $d)),
            $domains
        ), fn ($d) => $d !== '')));

        if (empty($domains)) {
            return ['ok' => false, 'reason' => 'invalid_input', 'message' => 'يرجى إدخال اسم دومين صحيح.', 'results' => []];
        }

        if ($provider === null) {
            $provider = DomainProvider::active()
                ->whereIn('type', ['namecheap', 'enom'])
                ->orderByRaw("FIELD(type,'namecheap','enom')")
                ->first();
        } elseif (!$provider->is_active || !in_array(strtolower((string) $provider->type), ['namecheap', 'enom'], true)) {
            return [
                'ok' => false,
                'reason' => 'invalid_provider',
                'message' => 'The selected registrar provider is inactive or unsupported.',
                'results' => [],
            ];
        }

        if (!$provider) {
            return ['ok' => false, 'reason' => 'no_provider', 'message' => 'لا يوجد مزوّد دومينات فعّال.', 'results' => []];
        }

        return $provider->type === 'namecheap'
            ? $this->namecheapCheck($provider, $domains)
            : $this->enomCheck($provider, $domains);
    }

    /**
     * إعادة تحقق دفعة دومينات مخصَّصة للاستخدام قبل إنشاء أي سجل مالي (Order/Invoice).
     * تعيد خريطة [domain => bool] عند نجاح الفحص تقنياً (بغضّ النظر عن نتيجة كل دومين)،
     * أو null عند فشل تقني عام في المزوّد — وعندها يجب على المستدعي عدم افتراض التوفر لأي دومين
     * وإيقاف العملية بالكامل.
     *
     * @param  array<int, string>  $domains
     * @return array<string, bool>|null
     */
    public function verifyRegistrationAvailabilityBatch(array $domains, DomainProvider $provider): ?array
    {
        $domains = array_values(array_unique(array_filter(array_map(
            fn ($d) => strtolower(trim((string) $d)),
            $domains
        ), fn ($d) => $d !== '')));

        if (empty($domains)) {
            return [];
        }

        $check = $this->checkDomains($domains, $provider);

        if (!($check['ok'] ?? false)) {
            Log::warning('DomainAvailabilityService: provider check failed during pre-purchase re-verification', [
                'domains' => $domains,
                'reason'  => $check['reason'] ?? null,
                'message' => $check['message'] ?? null,
            ]);

            return null;
        }

        $map = [];
        foreach ($check['results'] ?? [] as $row) {
            $key = strtolower((string) ($row['domain'] ?? ''));
            if ($key !== '') {
                $map[$key] = (bool) ($row['available'] ?? false);
            }
        }

        // النتيجة الناقصة فشل تقني/unknown، وليست دليلاً على أن الدومين غير متاح.
        foreach ($domains as $d) {
            if (!array_key_exists($d, $map)) {
                Log::warning('DomainAvailabilityService: selected provider omitted a requested domain', [
                    'provider_id' => $provider->getKey(),
                    'domain' => $d,
                ]);

                return null;
            }
        }

        return $map;
    }

    /* ===================== Namecheap ===================== */

    protected function namecheapCheck(DomainProvider $p, array $domains): array
    {
        try {
            $endpoint = $this->namecheapEndpoint($p);
            $params = [
                'ApiUser'    => trim((string) $p->username),
                'ApiKey'     => trim((string) $p->api_key),     // مفكوك تلقائيًا عبر casts
                'UserName'   => trim((string) $p->username),
                'ClientIp'   => trim((string) $p->client_ip),   // يجب أن يكون مبيّضًا في لوحة Namecheap
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

            if (!$resp->ok() || stripos((string) $resp->header('Content-Type'), 'xml') === false) {
                return ['ok' => false, 'message' => "HTTP {$resp->status()} أو استجابة غير XML", 'reason' => 'http_error'];
            }

            $xml = @simplexml_load_string((string) $resp->body(), 'SimpleXMLElement', \LIBXML_NOCDATA | \LIBXML_NOWARNING | \LIBXML_NOERROR);
            if ($xml === false) {
                return ['ok' => false, 'message' => 'تعذر تحليل XML', 'reason' => 'xml_parse_error'];
            }

            $xml->registerXPathNamespace('nc', 'http://api.namecheap.com/xml.response');
            $statusAttr = (string) ($xml['Status'] ?? '');

            if (strcasecmp($statusAttr, 'OK') !== 0) {
                $err = $xml->xpath('//nc:Errors/nc:Error')[0] ?? null;
                $msg = $err ? (string) $err : 'تعذّر تنفيذ الطلب.';
                return ['ok' => false, 'message' => $msg, 'reason' => 'provider_error'];
            }

            $nodes = $xml->xpath('//nc:DomainCheckResult') ?? [];
            $out = [];
            foreach ($nodes as $n) {
                $attrs      = $n->attributes();
                $domain     = (string) ($attrs->Domain ?? '');
                $available  = strtolower((string) ($attrs->Available ?? '')) === 'true';
                $isPremium  = strtolower((string) ($attrs->IsPremiumName ?? '')) === 'true';

                $price      = null;
                $currency   = null;
                if ($isPremium) {
                    if (isset($attrs->PremiumRegistrationPrice) && (string) $attrs->PremiumRegistrationPrice !== '') {
                        $price    = (float) $attrs->PremiumRegistrationPrice;
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
        if (!empty($p->endpoint)) return rtrim((string) $p->endpoint, '/');
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

            // جهّز أسعار الكتالوج دفعة واحدة لكل TLDs في هذه الدفعة (بدل استعلام منفصل لكل دومين)
            $tlds = array_unique(array_map(fn ($d) => strtolower(pathinfo($d, PATHINFO_EXTENSION)), $domains));
            $quotesByTld = $this->pricing()->registrationQuotesForTlds($tlds);

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

                // أرفق السعر/العملة من الكتالوج الموثوق حسب TLD؛ لا سعر افتراضي مُخترَع إن لم يوجد في الكتالوج
                $quote = $quotesByTld[strtolower($tld)] ?? null;

                $out[] = [
                    'domain'     => $fqdn,
                    'available'  => (bool) ($r['available'] ?? null),
                    'is_premium' => false,
                    'price'      => $quote['price'] ?? null,
                    'currency'   => $quote['currency'] ?? null,
                ];
            }

            return ['ok' => true, 'results' => $out, 'reason' => 'ok', 'message' => 'تم.'];
        } catch (\Throwable $e) {
            Log::error('Enom check exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'استثناء: ' . $e->getMessage(), 'reason' => 'exception'];
        }
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
}
