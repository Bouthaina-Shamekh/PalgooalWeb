<?php

namespace App\Services\Domains;

use App\Models\DomainTldPrice;

/**
 * مصدر التسعير الموثوق الوحيد لتسجيل الدومينات (Server Source of Truth).
 *
 * القاعدة: domain_tld_prices هو الجدول الوحيد الذي يحمل أي سعر في المخطط الحالي.
 * جدول domain_tlds لا يحمل أي عمود سعري (مؤكَّد من الـ migrations)، ويُستخدم فقط
 * لبيانات المزوّد/العملة/التفعيل. لا تُقبل أي قيمة سعر قادمة من Request أو Session
 * في أي مكان يستدعي هذه الخدمة.
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
            ->get();

        $candidates = [];

        foreach ($rows as $row) {
            // شروط الاستعلام الإلزامية: مزوّد فعّال + امتداد مفعّل
            if (!$row->is_active || !$row->enabled) {
                continue;
            }

            // نفس أولوية الاختيار الحالية: sale أولاً ثم cost، مع تطبيق تحقق صحة السعر على كل مرشّح
            $price = $this->pickValidPrice($row->sale, $row->cost);
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
     * يختار sale إن كانت قيمة سعر صالحة (رقمية وأكبر من صفر)، وإلا cost بنفس الشرط.
     * القيمة null أو السالبة أو الصفرية تُرفض؛ لا يوجد حالياً أي علامة في الكتالوج
     * (domain_tlds/domain_tld_prices) تُفعّل صراحةً دعم دومينات مجانية، لذا الصفر مرفوض دائماً.
     */
    protected function pickValidPrice($sale, $cost): ?float
    {
        foreach ([$sale, $cost] as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            if (!is_numeric($candidate)) {
                continue;
            }

            $value = (float) $candidate;
            if ($value > 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * تحويل السعر (بوحدة العملة الكاملة) إلى سنتات صحيحة، بطريقة ثابتة وآمنة على السيرفر.
     */
    protected function toCents(float $price): int
    {
        return (int) round($price * 100);
    }
}
