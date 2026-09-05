<?php

namespace App\Services\Domains;

use App\Models\DomainTld;
use App\Models\DomainTldPrice;

/**
 * مصدر التسعير الموثوق الوحيد لتسجيل الدومينات (Server Source of Truth).
 *
 * القاعدة: domain_tld_prices هو الجدول الوحيد الذي يحمل أي سعر في المخطط الحالي.
 * جدول domain_tlds لا يحمل أي عمود سعري (مؤكَّد من الـ migrations)، ويُستخدم فقط
 * لبيانات المزوّد/العملة/التفعيل. لا تُقبل أي قيمة سعر قادمة من Request أو Session
 * في أي مكان يستدعي هذه الخدمة.
 *
 * TLD-3F.1 — Live Provider Eligibility Guard: كل استعلامات هذه الخدمة (تسعير وتوافر-فقط)
 * تشترط provider.mode = 'live' إلى جانب provider.is_active = true وprovider.type ضمن
 * ['enom','namecheap']. أي مزوّد test/sandbox مستبعد تماماً من مسار العميل الحقيقي — بصرف
 * النظر عن سعره أو حالة تفعيله. هذا القيد على مستوى الاستعلام (query-level)، وليس فلترة لاحقة.
 */
class DomainPricingService
{
    /**
     * عرض سعر تسجيل موثوق لدومين واحد.
     * يعيد null إن تعذّر استخراج TLD صالح أو لم يوجد سعر موثوق له.
     *
     * @return array{tld:string,price:float,price_cents:int,currency:string,provider_id:int,provider_type:string,provider_mode:string,domain_tld_id:int}|null
     */
    public function registrationQuoteForDomain(string $domain, int $years = 1): ?array
    {
        $tld = $this->extractTld($domain);
        if ($tld === null) {
            return null;
        }

        $quotes = $this->registrationQuotesForTlds([$tld], $years);

        return $quotes[$tld] ?? null;
    }

    /**
     * يعيد أفضل سعر تسجيل موثوق لكل TLD (مفاتيح المصفوفة الناتجة = TLD بحروف صغيرة).
     * أي TLD بلا سعر موثوق لا يظهر إطلاقاً في المصفوفة الناتجة (لا يُوضع كـ null داخلها).
     *
     * TLD-3F.1: كل صف يُستبعد ما لم يكن provider.mode = 'live' (شرط استعلام إضافي إلى جانب
     * is_active/enabled الحاليين) — لا تغيير على قاعدة sale-only، أو منطق أرخص سعر، أو التعامل
     * مع years/العملة.
     *
     * @param  array<int, string>  $tlds
     * @return array<string, array{tld:string,price:float,price_cents:int,currency:string,provider_id:int,provider_type:string,provider_mode:string,domain_tld_id:int}>
     */
    public function registrationQuotesForTlds(array $tlds, int $years = 1): array
    {
        $cleanTlds = array_values(array_unique(array_filter(array_map(
            fn ($t) => strtolower(trim((string) $t)),
            $tlds
        ), fn ($t) => $t !== '')));

        if (empty($cleanTlds)) {
            return [];
        }

        $years = max(1, $years);

        $rows = DomainTldPrice::query()
            ->select([
                'domain_tld_prices.sale',
                'domain_tld_prices.cost',
                'domain_tlds.id as domain_tld_id',
                'domain_tlds.tld',
                'domain_tlds.currency',
                'domain_tlds.enabled',
                'domain_tlds.provider_id',
                'domain_providers.is_active',
                'domain_providers.type as provider_type',
                'domain_providers.mode as provider_mode',
            ])
            ->join('domain_tlds', 'domain_tlds.id', '=', 'domain_tld_prices.domain_tld_id')
            ->join('domain_providers', 'domain_providers.id', '=', 'domain_tlds.provider_id')
            ->whereIn('domain_tlds.tld', $cleanTlds)
            ->where('domain_tld_prices.action', 'register')
            ->where('domain_tld_prices.years', $years)
            ->whereIn('domain_providers.type', ['namecheap', 'enom'])
            // TLD-3F.1 — Live Provider Eligibility Guard: a test/sandbox-mode provider must never
            // participate in the customer-facing registration quote, regardless of price or
            // active status. Query-level filter, ranked alongside the existing type restriction.
            ->where('domain_providers.mode', 'live')
            ->get();

        $candidates = [];

        foreach ($rows as $row) {
            // شروط الاستعلام الإلزامية: مزوّد فعّال + امتداد مفعّل
            if (!$row->is_active || !$row->enabled) {
                continue;
            }

            // TLD-3A — القاعدة التجارية الجديدة: sale فقط. عرض/بيع تسجيل الدومين لا يُشتق أبداً من
            // cost (بيانات تكلفة داخلية فقط، تُستخدم في التسعير بالجملة الإداري ولا تُستخدم كسعر
            // عميل). sale=null أو 0 أو غير رقمية → لا Trusted Quote لهذا الصف إطلاقاً (لا fallback).
            $price = $this->pickValidPrice($row->sale);
            if ($price === null) {
                continue;
            }

            $currency = strtoupper(trim((string) ($row->currency ?? '')));
            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                continue;
            }

            $tldKey = strtolower(trim((string) $row->tld));
            if ($tldKey === '') {
                continue;
            }

            $candidate = [
                'tld'           => $tldKey,
                'price'         => $price,
                'price_cents'   => $this->toCents($price),
                'currency'      => $currency,
                'provider_id'   => (int) $row->provider_id,
                'provider_type' => strtolower(trim((string) $row->provider_type)),
                'provider_mode' => strtolower(trim((string) $row->provider_mode)),
                'domain_tld_id' => (int) $row->domain_tld_id,
            ];

            $candidates[$tldKey][$currency][] = $candidate;
        }

        $best = [];

        foreach ($candidates as $tldKey => $quotesByCurrency) {
            // Different currencies are not numerically comparable without an FX policy.
            // An ambiguous TLD is therefore unavailable for checkout in this phase.
            if (count($quotesByCurrency) !== 1) {
                continue;
            }

            $sameCurrencyQuotes = reset($quotesByCurrency);
            usort($sameCurrencyQuotes, fn (array $left, array $right) => $left['price_cents'] <=> $right['price_cents']);
            $best[$tldKey] = $sameCurrencyQuotes[0];
        }

        return $best;
    }

    /**
     * TLD-3A.1 — يحلّ مزوّداً موثوقاً بفحص التوافر لكل TLD، بصرف النظر عن وجود سعر sale صالح.
     *
     * جذر مشكلة TLD-3A.1: قبل هذا الإصلاح كان اختيار "أي مزوّد نسأله عن التوافر" في
     * DomainSearchController::check() يعتمد حصراً على provider_id القادم من
     * registrationQuotesForTlds()، فإذا غاب سعر بيع موثوق (sale=null) لم يُستشَر أي مزوّد
     * إطلاقاً، وسقط الدومين خطأً إلى status=unknown رغم كونه متاحاً فعلياً — لا يجوز أبداً أن
     * يتحول "لا سعر بيع" إلى "تعذّر التحقق التقني".
     *
     * هذه الدالة منفصلة تماماً عن أي قرار تسعيري: لا تشارك في اختيار "أفضل سعر" ولا في ترتيب
     * المزوّدين المعتمد بالتسعير (registrationQuotesForTlds() لم يتغيّر بحرف واحد)، والغرض
     * الوحيد منها توجيه طلب فحص التوافر عندما لا يوجد Trusted Quote بعد. عند تعدد المزوّدين
     * الفعّالين لنفس TLD بلا أي ترجيح سعري ممكن، يُختار أول صف مطابق (لا يوجد سعر يفصل بينهم أصلاً).
     *
     * TLD-3F.1: نفس الاستبعاد — provider.mode = 'live' مطلوب أيضاً هنا، وإلا فإن دومين مرتبط
     * حصراً بمزوّد test/sandbox سيظهر "متاح" عبر مزوّد لا يجوز استخدامه لعميل حقيقي إطلاقاً.
     *
     * @param  array<int, string>  $tlds
     * @return array<string, array{provider_id:int, provider_type:string, provider_mode:string}>
     */
    public function providersForTlds(array $tlds): array
    {
        $cleanTlds = array_values(array_unique(array_filter(array_map(
            fn ($t) => strtolower(trim((string) $t)),
            $tlds
        ), fn ($t) => $t !== '')));

        if (empty($cleanTlds)) {
            return [];
        }

        $rows = DomainTld::query()
            ->select([
                'domain_tlds.tld',
                'domain_tlds.enabled',
                'domain_tlds.provider_id',
                'domain_providers.is_active',
                'domain_providers.type as provider_type',
                'domain_providers.mode as provider_mode',
            ])
            ->join('domain_providers', 'domain_providers.id', '=', 'domain_tlds.provider_id')
            ->whereIn('domain_tlds.tld', $cleanTlds)
            ->whereIn('domain_providers.type', ['namecheap', 'enom'])
            // TLD-3F.1 — Live Provider Eligibility Guard: the availability-only fallback must
            // never resolve a test/sandbox-mode provider either — availability may only be shown
            // via a provider that is actually eligible for real customer registration.
            ->where('domain_providers.mode', 'live')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if (!$row->is_active || !$row->enabled) {
                continue;
            }

            $tldKey = strtolower(trim((string) $row->tld));
            if ($tldKey === '' || isset($out[$tldKey])) {
                continue;
            }

            $out[$tldKey] = [
                'provider_id'   => (int) $row->provider_id,
                'provider_type' => strtolower(trim((string) $row->provider_type)),
                'provider_mode' => strtolower(trim((string) $row->provider_mode)),
            ];
        }

        return $out;
    }

    /**
     * يستخرج TLD من اسم دومين بأمان: lowercase + trim، بدون أي نقطة بادئة،
     * ويعيد null إن تعذّر استخراج امتداد حقيقي (لا نقطة في الاسم، أو الجزء بعدها فارغ).
     */
    protected function extractTld(string $domain): ?string
    {
        $domain = strtolower(trim($domain));

        if ($domain === '' || !str_contains($domain, '.')) {
            return null;
        }

        $tld = ltrim(strtolower(trim(pathinfo($domain, PATHINFO_EXTENSION))), '.');

        return $tld !== '' ? $tld : null;
    }

    /**
     * TLD-3A — Require Explicit Sale Price for Domain Quotes.
     *
     * يقبل sale فقط كسعر عميل موثوق (رقمي وأكبر من صفر). القيمة null أو '' أو غير رقمية أو <= 0
     * تُرفض بلا أي fallback إلى cost. cost يبقى بيانات تكلفة داخلية بحتة (يُعرض للإدارة في التسعير
     * بالجملة فقط) ولا يجوز أبداً أن يتحوّل إلى سعر عميل تلقائياً — لا يوجد حالياً أي علامة في
     * الكتالوج (domain_tlds/domain_tld_prices) تُفعّل صراحةً دعم دومينات مجانية، لذا الصفر مرفوض دائماً.
     */
    protected function pickValidPrice($sale): ?float
    {
        if ($sale === null || $sale === '') {
            return null;
        }
        if (!is_numeric($sale)) {
            return null;
        }

        $value = (float) $sale;

        return $value > 0 ? $value : null;
    }

    /**
     * تحويل السعر (بوحدة العملة الكاملة) إلى سنتات صحيحة، بطريقة ثابتة وآمنة على السيرفر.
     */
    protected function toCents(float $price): int
    {
        return (int) round($price * 100);
    }
}
