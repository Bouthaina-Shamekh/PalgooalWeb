{{--
    _plan_card_dynamic.blade.php
    ─────────────────────────────
    Renders a single pricing plan card (featured or regular).

    Variables expected from parent (@include passes parent scope automatically):
      $plan            \App\Models\Plan   — the plan model
      $loopIndex       int                — 0-based index within the plans collection
      $totalPlans      int                — total number of plans rendered
      $resolveFeatures Closure            — resolves features for a billing cycle

    Alpine context: `annual` (bool) from ancestor x-data="{ annual: false }"
--}}
@php
    $trans         = $plan->translation();
    $planTitle     = $plan->title;
    $planDesc      = trim((string) ($trans?->getAttributeValue('description') ?? ''));
    $isFeatured    = (bool) $plan->is_featured;
    $featuredLabel = $plan->featured_label ?? t('dashboard.Most_Popular', 'Most Popular');

    // ── Feature lists per billing cycle ──────────────────────────────────
    $featuresMonthly  = $resolveFeatures($trans, 'monthly');
    $featuresAnnual   = $resolveFeatures($trans, 'annual');
    // Use monthly as primary; fall back to annual if monthly is empty
    $features         = $featuresMonthly ?: $featuresAnnual;
    // Separate annual list only if it differs from monthly
    $hasAnnualFeatures = !empty($featuresAnnual) && $featuresAnnual !== $featuresMonthly;

    // ── Prices ────────────────────────────────────────────────────────────
    $monthlyPrice = $plan->monthly_price;
    $annualPrice  = $plan->annual_price;                        // total per year
    $monthlyEquiv = $annualPrice ? number_format($annualPrice / 12, 2) : null;
    $yearlySaving = ($monthlyPrice && $annualPrice)
        ? (int) round($monthlyPrice * 12 - $annualPrice)
        : null;

    // ── Animation class (left / right / none for centred featured) ───────
    $mid       = (int) floor($totalPlans / 2);
    $animClass = ($loopIndex === $mid && $totalPlans >= 3)
        ? ''
        : ($loopIndex < $mid ? 'animate-from-left' : 'animate-from-right');

    // ── Checkout URL ──────────────────────────────────────────────────────
    $ctaUrl = route('checkout.cart') . '?plan_id=' . $plan->id;
@endphp

@if ($isFeatured)
    {{-- ── FEATURED PLAN ── badge floats above the card ─────────────────── --}}
    <div class="plan-featured relative flex flex-col transition-all duration-300 hover:-translate-y-2 md:-translate-y-6 md:!scale-110">

        {{-- Badge OUTSIDE overflow-hidden card --}}
        <div class="absolute -top-4 left-0 right-0 flex justify-center z-10">
            <span class="inline-flex items-center gap-1.5 bg-red-brand text-white text-xs font-bold px-5 py-1.5 rounded-full uppercase tracking-widest whitespace-nowrap shadow-lg ring-2 ring-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 fill-white" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
                {{ $featuredLabel }}
            </span>
        </div>

        {{-- Inner card WITH overflow-hidden --}}
        <div class="bg-white rounded-[20px] overflow-hidden flex flex-col shadow-[0_8px_30px_rgba(36,10,55,0.18)] ring-2 ring-purple-brand flex-1">
            @include('front.sections.pricing._plan_header',
                compact('planTitle', 'planDesc', 'monthlyPrice', 'annualPrice', 'monthlyEquiv', 'yearlySaving', 'isFeatured'))
            @include('front.sections.pricing._plan_features',
                compact('features', 'featuresAnnual', 'hasAnnualFeatures'))
            @include('front.sections.pricing._plan_cta',
                compact('ctaUrl'))
        </div>

    </div>
@else
    {{-- ── REGULAR PLAN ────────────────────────────────────────────────── --}}
    <div class="{{ $animClass }} bg-white rounded-[20px] overflow-hidden flex flex-col shadow-sm transition-all duration-300 hover:-translate-y-2">
        @include('front.sections.pricing._plan_header',
            compact('planTitle', 'planDesc', 'monthlyPrice', 'annualPrice', 'monthlyEquiv', 'yearlySaving', 'isFeatured'))
        @include('front.sections.pricing._plan_features',
            compact('features', 'featuresAnnual', 'hasAnnualFeatures'))
        @include('front.sections.pricing._plan_cta',
            compact('ctaUrl'))
    </div>
@endif
