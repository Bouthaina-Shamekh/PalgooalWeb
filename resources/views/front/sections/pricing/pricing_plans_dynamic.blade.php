@php
    // ── Section Meta ─────────────────────────────────────────────────────────
    $section_id = trim((string) ($data['section_id'] ?? ''));
    $title      = trim((string) ($data['title']      ?? ''));
    $subtitle   = trim((string) ($data['subtitle']   ?? ''));

    // ── Fetch active plans ───────────────────────────────────────────────────
    $allPlans = \App\Models\Plan::with(['translations'])
        ->active()
        ->orderBy('monthly_price_cents', 'asc')
        ->get();

    // ── Reorder: featured plan in the middle slot ────────────────────────────
    $featuredPlans = $allPlans->where('is_featured', true)->values();
    $regularPlans  = $allPlans->where('is_featured', false)->values();

    $plans = collect();
    if ($regularPlans->isNotEmpty())  $plans->push($regularPlans->get(0));
    if ($featuredPlans->isNotEmpty()) $plans->push($featuredPlans->first());
    $regularPlans->slice(1)->each(fn($p) => $plans->push($p));
    $plans = $plans->filter()->values();

    // ── Discount helpers ─────────────────────────────────────────────────────
    /**
     * Returns the discount percent (0–100) for a plan.
     * Formula: ((monthly × 12) − annual) / (monthly × 12) × 100
     * Returns 0 if data is missing, null, or no actual discount.
     */
    $computeDiscountPercent = function (\App\Models\Plan $plan): int {
        $monthly = $plan->monthly_price;  // dollars (float|null)
        $annual  = $plan->annual_price;   // total / year (float|null)
        if (! $monthly || ! $annual || $monthly <= 0) return 0;
        $yearlyIfMonthly = $monthly * 12;
        if ($yearlyIfMonthly <= 0) return 0;
        $pct = (($yearlyIfMonthly - $annual) / $yearlyIfMonthly) * 100;
        return $pct > 0 ? (int) round($pct) : 0;
    };

    /**
     * Returns the dollar saving per year for a plan.
     * Returns 0 if no real saving.
     */
    $computeYearlySavingDollars = function (\App\Models\Plan $plan): int {
        $monthly = $plan->monthly_price;
        $annual  = $plan->annual_price;
        if (! $monthly || ! $annual) return 0;
        return max(0, (int) round(($monthly * 12) - $annual));
    };

    // ── Aggregate values (used in the toggle area) ───────────────────────────
    // Max discount % across all active plans — drives the Annual badge
    $maxDiscountPercent = (int) $allPlans->map($computeDiscountPercent)->max();

    // Max dollar saving — drives the savings-hint line
    $maxSavingDollars = (int) $allPlans->map($computeYearlySavingDollars)->max();

    // ── Feature resolver ─────────────────────────────────────────────────────
    /**
     * Extracts the feature list for a given billing cycle from a PlanTranslation.
     * Supports both nested  {monthly:[…], annual:[…]}  and flat [] formats.
     */
    $resolveFeatures = function ($trans, string $billing = 'monthly'): array {
        $raw = $trans?->features ?? [];
        if (! is_array($raw)) return [];

        if (isset($raw[$billing]) && is_array($raw[$billing])) {
            $items = $raw[$billing];
        } elseif (isset($raw['monthly']) && is_array($raw['monthly'])) {
            $items = $raw['monthly'];
        } elseif (isset($raw['annual']) && is_array($raw['annual'])) {
            $items = $raw['annual'];
        } else {
            $items = array_values($raw);  // flat / legacy format
        }

        return array_values(array_filter(
            array_map(function ($item) {
                if (is_array($item)) {
                    if (! ($item['available'] ?? true)) return null;
                    return trim((string) ($item['text'] ?? ''));
                }
                return trim((string) $item);
            }, $items),
            fn($v) => $v !== null && $v !== ''
        ));
    };
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     Pricing Plans Dynamic — Annual Billing & Discount Support
     ──────────────────────────────────────────────────────────────────────────
     Alpine.js `annual` boolean drives ALL toggle behaviour (price, badge,
     billing-line, features, CTA URL) without any page reload.
     ═══════════════════════════════════════════════════════════════════════════ --}}
<section
    id="{{ $section_id }}"
    class="bg-[#F2F2F2] py-16 md:py-24 px-4 sm:px-6 lg:px-12"
    x-data="{ annual: false }">

    <div class="container mx-auto">

        {{-- ── Section heading ──────────────────────────────────────────── --}}
        @if ($title)
            <h2 class="text-purple-brand font-extrabold text-3xl md:text-4xl lg:text-[40px] text-center uppercase mb-4 animate-from-up">
                {{ $title }}
            </h2>
        @endif

        @if ($subtitle)
            <p class="text-[#555] text-base md:text-lg text-center max-w-[800px] mx-auto mb-10 animate-from-up">
                {!! $subtitle !!}
            </p>
        @endif

        {{-- ── Billing Toggle — Segmented Pill ────────────────────────── --}}
        <div class="flex flex-col items-center gap-3 mb-20 animate-from-up">

            <div class="inline-flex items-center bg-white border border-[#e2e8f0] rounded-full p-1.5 shadow-[0_2px_12px_rgba(0,0,0,0.08)]">

                {{-- Monthly button --}}
                <button
                    @click="annual = false"
                    type="button"
                    :class="! annual
                        ? 'bg-purple-brand text-white shadow-[0_2px_8px_rgba(36,10,55,0.35)]'
                        : 'text-[#888] hover:text-purple-brand'"
                    class="px-7 py-2.5 rounded-full text-sm font-semibold transition-all duration-250 select-none focus:outline-none">
                    {{ t('site.Monthly', 'Monthly') }}
                </button>

                {{-- Annual button — badge shows computed max discount % --}}
                <button
                    @click="annual = true"
                    type="button"
                    :class="annual
                        ? 'bg-purple-brand text-white shadow-[0_2px_8px_rgba(36,10,55,0.35)]'
                        : 'text-[#888] hover:text-purple-brand'"
                    class="relative px-7 py-2.5 rounded-full text-sm font-semibold transition-all duration-250 select-none focus:outline-none flex items-center gap-2">
                    {{ t('site.Annual', 'Annual') }}
                    @if ($maxDiscountPercent > 0)
                        <span class="inline-flex items-center bg-red-brand text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide leading-none">
                            −{{ $maxDiscountPercent }}%
                        </span>
                    @endif
                </button>

            </div>

            {{-- ── Savings hint — animated in/out with annual toggle ── --}}
            @if ($maxSavingDollars > 0)
                <p class="text-sm text-[#666] transition-all duration-300"
                    :class="annual ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1 pointer-events-none'">
                    💡 {{ t('site.You_Save_Up_To', 'You save up to') }}
                    <span class="font-bold text-red-brand">${{ $maxSavingDollars }}/year</span>
                    {{ t('site.With_Annual_Billing', 'with annual billing') }}
                </p>
            @endif

        </div>
        {{-- ── End Toggle ──────────────────────────────────────────────── --}}

        {{-- ── Plan Cards Grid ─────────────────────────────────────────── --}}
        @if ($plans->isEmpty())
            <p class="text-center text-[#888] py-12">
                {{ t('site.No_Plans_Available', 'No plans available.') }}
            </p>
        @else

            <div class="grid grid-cols-1 md:grid-cols-{{ $plans->count() }} gap-6 lg:gap-10 items-stretch pt-5 md:pt-6">

                @foreach ($plans as $loopIndex => $plan)
                    @php
                        // ── Plan translation ──────────────────────────────
                        $trans     = $plan->translation();
                        $planTitle = $plan->title;
                        $planDesc  = trim((string) ($trans?->getAttributeValue('description') ?? ''));
                        $isFeatured   = (bool) $plan->is_featured;
                        $featuredLabel = $plan->featured_label
                            ?? t('site.Most_Popular', 'Most Popular');

                        // ── Feature lists ─────────────────────────────────
                        $featuresMonthly   = $resolveFeatures($trans, 'monthly');
                        $featuresAnnual    = $resolveFeatures($trans, 'annual');
                        $features          = $featuresMonthly ?: $featuresAnnual;
                        $hasAnnualFeatures = ! empty($featuresAnnual)
                                         && ($featuresAnnual !== $featuresMonthly);

                        // ── Prices ────────────────────────────────────────
                        $monthlyPrice = $plan->monthly_price;   // float|null ($/mo)
                        $annualPrice  = $plan->annual_price;    // float|null ($/year)
                        $monthlyEquiv = $annualPrice
                            ? number_format($annualPrice / 12, 2)
                            : null;

                        // ── Discount calculation ──────────────────────────
                        $discountPercent  = $computeDiscountPercent($plan);
                        $yearlySaving     = $computeYearlySavingDollars($plan);
                        $hasDiscount      = $discountPercent > 0;

                        // ── Card animation ───────────────────────────────
                        $totalPlans = $plans->count();
                        $mid        = (int) floor($totalPlans / 2);
                        $animClass  = ($loopIndex === $mid && $totalPlans >= 3)
                            ? ''
                            : ($loopIndex < $mid ? 'animate-from-left' : 'animate-from-right');

                        // ── CTA URL ──────────────────────────────────────
                        $ctaBaseUrl = route('checkout.cart') . '?plan_id=' . $plan->id;
                    @endphp

                    {{-- ── FEATURED PLAN ── --}}
                    @if ($isFeatured)

                        <div class="plan-featured relative flex flex-col transition-all duration-300 hover:-translate-y-2 md:-translate-y-6 md:!scale-110">

                            {{-- Most-Popular badge — sits outside overflow-hidden card --}}
                            <div class="absolute -top-4 left-0 right-0 flex justify-center z-10">
                                <span class="inline-flex items-center gap-1.5 bg-red-brand text-white text-xs font-bold px-5 py-1.5 rounded-full uppercase tracking-widest whitespace-nowrap shadow-lg ring-2 ring-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 fill-white" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    {{ $featuredLabel }}
                                </span>
                            </div>

                            {{-- Inner card WITH overflow-hidden so discount badge clips correctly --}}
                            <div class="relative bg-white rounded-[20px] overflow-hidden flex flex-col shadow-[0_8px_30px_rgba(36,10,55,0.18)] ring-2 ring-purple-brand flex-1">

                                {{-- Discount badge — top-right corner, only in annual mode --}}
                                @if ($hasDiscount)
                                    <div class="absolute top-3 end-3 z-20"
                                         x-show="annual" x-transition.opacity.duration.300ms>
                                        <span class="inline-flex items-center bg-red-brand text-white text-xs font-bold px-2.5 py-1 rounded-full shadow">
                                            {{ strtr(t('site.Save_Percent', 'Save :percent%'), [':percent' => $discountPercent]) }}
                                        </span>
                                    </div>
                                @endif

                                @include('front.sections.pricing._plan_header_dynamic',
                                    compact('planTitle', 'planDesc', 'monthlyPrice', 'annualPrice',
                                            'monthlyEquiv', 'yearlySaving', 'discountPercent',
                                            'hasDiscount', 'isFeatured'))

                                @include('front.sections.pricing._plan_features',
                                    compact('features', 'featuresAnnual', 'hasAnnualFeatures'))

                                @include('front.sections.pricing._plan_cta',
                                    ['ctaUrl' => $ctaBaseUrl])

                            </div>
                        </div>

                    {{-- ── REGULAR PLAN ── --}}
                    @else

                        <div class="{{ $animClass }} relative bg-white rounded-[20px] overflow-hidden flex flex-col shadow-sm transition-all duration-300 hover:-translate-y-2">

                            {{-- Discount badge — top-right corner, only in annual mode --}}
                            @if ($hasDiscount)
                                <div class="absolute top-3 end-3 z-20"
                                     x-show="annual" x-transition.opacity.duration.300ms>
                                    <span class="inline-flex items-center bg-red-brand text-white text-xs font-bold px-2.5 py-1 rounded-full shadow">
                                        {{ strtr(t('site.Save_Percent', 'Save :percent%'), [':percent' => $discountPercent]) }}
                                    </span>
                                </div>
                            @endif

                            @include('front.sections.pricing._plan_header_dynamic',
                                compact('planTitle', 'planDesc', 'monthlyPrice', 'annualPrice',
                                        'monthlyEquiv', 'yearlySaving', 'discountPercent',
                                        'hasDiscount', 'isFeatured'))

                            @include('front.sections.pricing._plan_features',
                                compact('features', 'featuresAnnual', 'hasAnnualFeatures'))

                            @include('front.sections.pricing._plan_cta',
                                ['ctaUrl' => $ctaBaseUrl])

                        </div>

                    @endif
                @endforeach

            </div>
        @endif
        {{-- ── End Plan Cards Grid ─────────────────────────────────────── --}}

    </div>
</section>
