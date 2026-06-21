@php
    // ── Section fields ────────────────────────────────────────────────────
    $section_id    = trim((string) ($data['section_id']    ?? ''));
    $title         = trim((string) ($data['title']         ?? ''));
    $subtitle      = trim((string) ($data['subtitle']      ?? ''));
    $monthly_label = trim((string) ($data['monthly_label'] ?? ''));
    $annual_label  = trim((string) ($data['annual_label']  ?? ''));

    // ── Category filter (optional, shared) ───────────────────────────────
    // Empty / missing → no filter → all active plans are shown.
    $plan_category_id = is_numeric($data['plan_category_id'] ?? null)
        ? (int) $data['plan_category_id']
        : null;

    // ── Design tokens via Registry ────────────────────────────────────────
    $background_token = trim((string) ($data['background_token'] ?? 'muted'));
    $backgroundClass  = \App\Support\Sections\DesignTokenRegistry::resolveClass('background_token', $background_token);

    $text_token = trim((string) ($data['text_token'] ?? 'heading'));
    $textClass  = \App\Support\Sections\DesignTokenRegistry::resolveClass('text_token', $text_token);

    // ── Fetch active plans with translations ──────────────────────────────
    $allPlans = \App\Models\Plan::with(['translations'])
        ->active()
        ->when($plan_category_id, fn ($q) => $q->where('plan_category_id', $plan_category_id))
        ->orderBy('monthly_price_cents', 'asc')
        ->get();

    // ── Put featured plan in the middle slot ──────────────────────────────
    $featured = $allPlans->where('is_featured', true)->values();
    $regular  = $allPlans->where('is_featured', false)->values();

    $plans = collect();
    if ($regular->isNotEmpty()) {
        $plans->push($regular->get(0));
    }
    if ($featured->isNotEmpty()) {
        $plans->push($featured->first());
    }
    $regular->slice(1)->each(fn ($p) => $plans->push($p));
    $plans = $plans->filter()->values();

    // ── Max annual saving across all plans (for the hint line) ───────────
    $maxSaving = (int) $allPlans
        ->map(function ($p) {
            if (! $p->monthly_price || ! $p->annual_price) {
                return 0;
            }
            return round($p->monthly_price * 12 - $p->annual_price);
        })
        ->max();

    // ── Helper: extract flat features list from a PlanTranslation ────────
    // Features are stored as {"monthly":[{text,available},...], "annual":[...]}
    // OR as a flat array [] if no features were entered.
    $resolveFeatures = function ($trans, string $billing = 'monthly'): array {
        $raw = $trans?->features ?? [];
        if (! is_array($raw)) {
            return [];
        }

        // Nested format: {monthly:[...], annual:[...]}
        if (isset($raw[$billing]) && is_array($raw[$billing])) {
            $items = $raw[$billing];
        } elseif (isset($raw['monthly']) && is_array($raw['monthly'])) {
            $items = $raw['monthly'];
        } elseif (isset($raw['annual']) && is_array($raw['annual'])) {
            $items = $raw['annual'];
        } else {
            // Flat format (legacy / plain strings)
            $items = array_values($raw);
        }

        // Keep only items that are available; normalise to plain strings
        return array_values(
            array_filter(
                array_map(function ($item) {
                    if (is_array($item)) {
                        if (! ($item['available'] ?? true)) {
                            return null;
                        }
                        return trim((string) ($item['text'] ?? ''));
                    }
                    return trim((string) $item);
                }, $items),
                fn ($v) => $v !== null && $v !== '',
            ),
        );
    };

    $totalPlans = $plans->count();
@endphp

<section id="{{ $section_id }}"
         class="{{ $backgroundClass }} py-16 md:py-24 px-4 sm:px-6 lg:px-12"
         x-data="{ annual: false }">
    <div class="container mx-auto">

        @if ($title)
            <h2 class="{{ $textClass }} font-extrabold text-3xl md:text-4xl lg:text-[40px] text-center uppercase mb-4 animate-from-up">
                {{ $title }}
            </h2>
        @endif

        @if ($subtitle)
            <p class="text-[#555] text-base md:text-lg text-center max-w-[800px] mx-auto mb-10 animate-from-up">
                {!! $subtitle !!}
            </p>
        @endif

        {{-- ── Billing Toggle — Segmented Pill ────────────────────────────── --}}
        <div class="flex flex-col items-center gap-3 mb-20 animate-from-up">

            <div class="inline-flex items-center bg-white border border-[#e2e8f0] rounded-full p-1.5 shadow-[0_2px_12px_rgba(0,0,0,0.08)]">

                {{-- Monthly --}}
                @if ($monthly_label)
                    <button @click="annual = false" type="button"
                            :class="!annual
                                ? 'bg-purple-brand text-white shadow-[0_2px_8px_rgba(36,10,55,0.35)]'
                                : 'text-[#888] hover:text-purple-brand'"
                            class="px-7 py-2.5 rounded-full text-sm font-semibold transition-all duration-250 select-none focus:outline-none">
                        {{ $monthly_label }}
                    </button>
                @endif

                {{-- Annual --}}
                @if ($annual_label)
                    <button @click="annual = true" type="button"
                            :class="annual
                                ? 'bg-purple-brand text-white shadow-[0_2px_8px_rgba(36,10,55,0.35)]'
                                : 'text-[#888] hover:text-purple-brand'"
                            class="relative px-7 py-2.5 rounded-full text-sm font-semibold transition-all duration-250 select-none focus:outline-none flex items-center gap-2">
                        {{ $annual_label }}
                        <span class="inline-flex items-center bg-red-brand text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide leading-none">
                            −20%
                        </span>
                    </button>
                @endif

            </div>

            {{-- Savings hint — visible only in annual mode --}}
            @if ($maxSaving > 0)
                <p class="text-sm text-[#666] transition-all duration-300"
                   :class="annual ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1 pointer-events-none'">
                    💡 {{ t('site.You_Save_Up_To', 'You save up to') }}
                    <span class="font-bold text-red-brand">${{ $maxSaving }}/year</span>
                    {{ t('site.With_Annual_Billing', 'with annual billing') }}
                </p>
            @endif

        </div>
        {{-- ── End Toggle ──────────────────────────────────────────────────── --}}

        @if ($plans->isEmpty())
            <p class="text-center text-[#888] py-12">{{ t('site.No_Plans_Available', 'No plans available.') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-{{ $totalPlans }} gap-6 lg:gap-10 items-stretch pt-5 md:pt-6">

                @foreach ($plans as $loopIndex => $plan)
                    @include('front.sections.pricing._plan_card_dynamic', [
                        'loopIndex'  => $loopIndex,
                        'totalPlans' => $totalPlans,
                    ])
                @endforeach

            </div>
        @endif

    </div>
</section>
