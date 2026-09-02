<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use App\Services\Domains\DomainAvailabilityService;
use App\Services\Domains\DomainPricingService;
use Illuminate\Http\Request;

class DomainSearchController extends Controller
{
    /** يحلّ خدمة التسعير الموثوقة (Server Source of Truth) — domain_tld_prices حصراً */
    protected function pricing(): DomainPricingService
    {
        return app(DomainPricingService::class);
    }

    /** يحلّ خدمة فحص التوافر الموثوقة (اختيار المزوّد + Namecheap/Enom) — مصدر مشترك لكل مسارات المشروع */
    protected function availability(): DomainAvailabilityService
    {
        return app(DomainAvailabilityService::class);
    }

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

        // Quote التسعير هو مصدر حقيقة المزوّد عند وجود سعر بيع موثوق. كل مجموعة تُفحص لدى
        // المزوّد الذي سعّرها في هذه الحالة.
        $tlds = array_unique(array_map(fn($d) => strtolower(pathinfo($d, PATHINFO_EXTENSION)), $domains));
        $bestPriceByTld = $this->pricing()->registrationQuotesForTlds($tlds);

        // TLD-3A.1 — Root cause fix: فحص التوافر (Availability) لا يجوز أن يُحجَب بسبب غياب سعر
        // بيع موثوق (missing_sale). providersForTlds() توجّه طلب التوافر لمزوّد فعّال حتى إن لم
        // يوجد Trusted Quote بعد؛ لا تشارك في أي قرار تسعيري ولا تُستخدم لعرض أي سعر لاحقاً —
        // $bestPriceByTld يبقى المصدر الوحيد للسعر المعروض، كما هو دون أي تغيير.
        $providersByTld = $this->pricing()->providersForTlds($tlds);
        $providerGroups = [];

        foreach ($domains as $domain) {
            $tld = strtolower(pathinfo($domain, PATHINFO_EXTENSION));
            $providerId = (int) ($bestPriceByTld[$tld]['provider_id'] ?? $providersByTld[$tld]['provider_id'] ?? 0);

            if ($providerId > 0) {
                $providerGroups[$providerId][] = $domain;
            }
        }

        $providers = DomainProvider::query()
            ->active()
            ->whereKey(array_keys($providerGroups))
            ->get()
            ->keyBy('id');
        $checkResults = [];
        $checkMessage = 'تم.';

        foreach ($providerGroups as $providerId => $providerDomains) {
            $provider = $providers->get($providerId);

            if (!$provider instanceof DomainProvider) {
                $check = ['reason' => 'invalid_provider', 'message' => 'تعذّر استخدام مزوّد السعر المحدد.'];
            } else {
                $check = $this->availability()->checkDomains($providerDomains, $provider);
            }

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

            $checkResults = array_merge($checkResults, $check['results'] ?? []);
            $checkMessage = $check['message'] ?? $checkMessage;
        }

        // 4) خرّج خريطة التوافر {domain => [available,is_premium, premium_price?]}
        // available هنا Tri-state: true (متاح) / false (غير متاح، مؤكَّد من المزوّد) / null (Unknown —
        // فشل تقني أو نتيجة ناقصة من المزوّد). null لا يُعامَل أبداً كـ"غير متاح" — عقد مثبت مسبقاً في
        // DomainAvailabilityService::verifyRegistrationAvailabilityBatch()، ويُطبَّق هنا لنفس السبب
        // على مسار البحث العام (D-BUILDER-1).
        $availability = [];
        foreach ($checkResults as $row) {
            $key = strtolower((string)($row['domain'] ?? ''));
            if ($key !== '') {
                $rawAvailable = array_key_exists('available', $row) ? $row['available'] : null;
                $availability[$key] = [
                    'available'   => $rawAvailable === null ? null : (bool) $rawAvailable,
                    'is_premium'  => (bool)($row['is_premium'] ?? false),
                    'premium_price' => $row['price']   ?? null,
                    'premium_currency' => $row['currency'] ?? null,
                ];
            }
        }
        // أي نطاق لم يرجع من المزود ضمن نتائج الدفعة إطلاقاً: فشل تقني/Unknown، وليس دليلاً على عدم التوافر.
        foreach ($domains as $d) {
            $k = strtolower($d);
            if (!isset($availability[$k])) {
                $availability[$k] = ['available' => null, 'is_premium' => false, 'premium_price' => null, 'premium_currency' => null];
            }
        }

        // 6) تركيب النتائج النهائية (بدون كشف اسم أي مزوّد)
        $results = [];
        foreach ($domains as $domain) {
            $key = strtolower($domain);
            $tld = strtolower(pathinfo($domain, PATHINFO_EXTENSION));
            $availRow = $availability[$key];
            $isAvailable = $availRow['available']; // true|false|null (unknown)

            // السعر المعروض:
            // - إن كان Premium ونيم شيب أرسل سعر بريميوم → نعرضه كما هو (قد يختلف عن الجدول).
            // - غير ذلك → نعرض أرخص سعر من جدولنا (sale ثم cost).
            // لا يُعرض أي سعر إطلاقاً إلا لدومين مؤكَّد التوافر (isAvailable === true)، تجنّباً لعرض
            // سعر بجانب "غير متاح" أو "تعذّر التحقق" وكأنه قابل للشراء.
            $price = null;
            $currency = null;

            if ($isAvailable === true) {
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
            }

            // TLD-3A — فصل Availability عن Sellability (حقول إضافية، لا تُلغي/تُعيد تسمية أي حقل قائم):
            // sellable=true فقط عندما توجد قيمة سعر فعلية بالفعل (بريميوم من المزوّد، أو sale موثوق
            // من الكتالوج عبر DomainPricingService). pricing_status='missing_sale' حصراً في حالة
            // available=true لكن بلا سعر بيع موثوق (لا يجوز تحويلها إلى unavailable أو unknown).
            $sellable = $isAvailable === true && $price !== null;
            $pricingStatus = $isAvailable === true ? ($price !== null ? 'ok' : 'missing_sale') : null;

            $results[] = [
                'domain'     => $domain,
                'available'  => $isAvailable,
                // status هو العقد الصريح الموصى باستخدامه من الواجهة: 'available' | 'unavailable' | 'unknown'.
                // available يبقى موجوداً للتوافق الخلفي (true|false|null بنفس الدلالة).
                'status'     => $isAvailable === true ? 'available' : ($isAvailable === false ? 'unavailable' : 'unknown'),
                'is_premium' => (bool)$availRow['is_premium'],
                'price'      => $price,
                'currency'   => $price !== null ? ($currency ?? 'USD') : null,
                'sellable'       => $sellable,
                'pricing_status' => $pricingStatus,
            ];
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        return response()->json([
            'ok'          => true,
            'message'     => $checkMessage,
            'reason'      => 'ok',
            // لا نرجّع اسم مزوّد:
            // 'provider' محذوف عمداً
            'duration_ms' => $durationMs,
            'results'     => $results,
            'fetched_at'  => now()->toIso8601String(),
        ], 200);
    }

    /* ====================== Helpers ====================== */
    /* ملاحظة: منطق اختيار المزوّد وفحص Namecheap/Enom (namecheapCheck/enomCheck/splitDomain)
       انتقل بالكامل إلى App\Services\Domains\DomainAvailabilityService ليكون قابلاً لإعادة
       الاستخدام من مسارات أخرى (Cart/Checkout/شراء العميل) دون تكرار أو استدعاء Controller من Controller. */

    /** يعيد قيمة معامل query كنص فقط؛ أي نوع آخر (مصفوفة مثلاً عبر q[]/domains[]) يُعامَل كسلسلة فارغة لمنع Array to string conversion */
    protected function queryScalar(Request $req, string $key): string
    {
        $v = $req->query($key, '');
        return is_string($v) ? $v : '';
    }

    /**
     * يطبّع معامل tlds سواء أُرسل كنص CSV (?tlds=com,net) أو كمصفوفة (?tlds[]=com&tlds[]=net).
     * يتجاهل العناصر غير النصية والفارغة، ويطبّق strtolower/trim فقط؛ التحقق النهائي (isValidTld) يبقى في المستدعي.
     */
    protected function queryTlds(Request $req): array
    {
        $raw = $req->query('tlds', '');

        if (is_string($raw)) {
            if (trim($raw) === '') {
                return [];
            }
            return array_values(array_filter(array_map(
                fn($t) => strtolower(trim($t)),
                explode(',', $raw)
            ), fn($t) => $t !== ''));
        }

        if (is_array($raw)) {
            $out = [];
            foreach ($raw as $item) {
                if (!is_string($item)) {
                    continue;
                }
                $item = strtolower(trim($item));
                if ($item !== '') {
                    $out[] = $item;
                }
            }
            return $out;
        }

        return [];
    }

    protected function normalizeDomains(Request $req): array
    {
        $q            = trim($this->queryScalar($req, 'q'));
        $tldsList     = $this->queryTlds($req);
        $domainsParam = trim($this->queryScalar($req, 'domains'));

        $domains = [];
        if ($domainsParam !== '') {
            $domains = array_filter(array_map('trim', explode(',', $domainsParam)));
        } elseif ($q !== '') {
            $sld  = strtolower($this->toAsciiLabel($q));
            $tlds = !empty($tldsList) ? $tldsList : ['com', 'net', 'org'];
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
