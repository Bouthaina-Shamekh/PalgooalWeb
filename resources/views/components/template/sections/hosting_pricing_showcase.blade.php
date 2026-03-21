@php
    $payload = is_array($data ?? null)
        ? $data
        : (is_array($content ?? null) ? $content : []);

    $sectionId = isset($section) ? ($section->id ?? null) : null;
    $sectionTitle = trim((string) ($payload['title'] ?? $title ?? __('HOSTING')));
    $sectionDescription = trim((string) ($payload['description'] ?? ''));
    $buttonLabel = trim((string) ($payload['button_label'] ?? __('Choose Now')));
    $buttonLabel = $buttonLabel !== '' ? $buttonLabel : __('Choose Now');
    $isRtl = current_dir() === 'rtl';
    $doneIconPath = asset('assets/imgs/icons/icon-material-done.svg');

    $categories = collect($payload['plan_categories'] ?? [])
        ->map(function ($category) use ($buttonLabel) {
            if (! $category) {
                return null;
            }

            $translation = $category->translation(app()->getLocale()) ?? $category->translations->first();
            $categoryKey = trim((string) ($translation?->slug ?? ('category-' . $category->id)));
            $categoryLabel = trim((string) ($translation?->title ?? ''));

            if ($categoryKey === '') {
                return null;
            }

            $plans = collect($category->plans ?? [])
                ->map(function ($plan) use ($buttonLabel, $categoryKey) {
                    if (! $plan) {
                        return null;
                    }

                    $translation = $plan->translation(app()->getLocale()) ?? $plan->translations->first();
                    $rawFeatures = is_array($translation?->features ?? null) ? $translation->features : [];
                    $hasBillingSplit = array_intersect(array_keys($rawFeatures), ['monthly', 'annual']) !== [];

                    $billingPeriod = $plan->monthly_price_cents !== null ? 'monthly' : 'annually';
                    $featureBucket = match (true) {
                        $hasBillingSplit && ! empty($rawFeatures['monthly']) => $rawFeatures['monthly'],
                        $hasBillingSplit && ! empty($rawFeatures['annual']) => $rawFeatures['annual'],
                        default => $rawFeatures,
                    };

                    $features = collect(is_array($featureBucket) ? $featureBucket : [])
                        ->map(function ($feature): ?array {
                            if (is_array($feature)) {
                                $text = trim((string) ($feature['text'] ?? $feature['title'] ?? $feature['label'] ?? ''));
                                $available = array_key_exists('available', $feature)
                                    ? filter_var($feature['available'], FILTER_VALIDATE_BOOLEAN)
                                    : true;
                            } else {
                                $text = trim((string) $feature);
                                $available = true;
                            }

                            if ($text === '') {
                                return null;
                            }

                            return [
                                'text' => $text,
                                'available' => (bool) $available,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all();

                    $planTitle = trim((string) ($translation?->title ?? $plan->name ?? $plan->slug ?? __('Plan')));

                    if ($planTitle === '') {
                        return null;
                    }

                    return [
                        'category' => $categoryKey,
                        'title' => $planTitle,
                        'features' => $features,
                        'button_url' => route('checkout.cart', [
                            'plan_id' => $plan->id,
                            'plan_sub_type' => $billingPeriod,
                        ], false),
                    ];
                })
                ->filter()
                ->values();

            if ($plans->isEmpty()) {
                return null;
            }

            return [
                'label' => $categoryLabel !== '' ? $categoryLabel : \Illuminate\Support\Str::headline(str_replace('-', ' ', $categoryKey)),
                'key' => $categoryKey,
                'plans' => $plans,
            ];
        })
        ->filter()
        ->values();

    $flattenedPlans = $categories
        ->flatMap(fn ($category) => $category['plans'])
        ->values();

    $activeCategoryKey = $categories->first()['key'] ?? null;
    $sectionInstanceKey = 'hosting-pricing-' . ($sectionId ?? uniqid());
    $tabsWrapperId = $sectionInstanceKey . '-tabs';
    $gridId = $sectionInstanceKey . '-grid';
    $indicatorId = $sectionInstanceKey . '-indicator';
@endphp

{{-- Plans Section --}}
<section id="price" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="bg-gray-light py-12 lg:py-20 px-4 sm:px-6 lg:px-12">
    {{-- Section Header --}}
    <div class="text-center mb-12">
        @if ($sectionTitle !== '')
            <h3 class="text-4xl font-extrabold text-purple-brand md:text-5xl">{{ $sectionTitle }}</h3>
        @endif

        @if ($sectionDescription !== '')
            <p class="text-gray-500 text-base md:text-lg mb-8">
                {{ $sectionDescription }}
            </p>
        @endif

        @if ($categories->isNotEmpty())
            {{-- Category Filter Tabs --}}
            <div class="flex justify-center mb-8">
                <div
                    id="{{ $tabsWrapperId }}"
                    data-hosting-pricing-tabs
                    class="relative flex flex-wrap justify-center gap-6 md:gap-12 text-lg md:text-xl"
                >
                    @foreach ($categories as $category)
                        <button
                            type="button"
                            data-pricing-category-tab
                            data-category="{{ $category['key'] }}"
                            class="{{ $category['key'] === $activeCategoryKey ? 'text-purple-brand border-purple-brand font-bold' : 'text-gray-400 border-transparent hover:text-purple-brand' }} category-tab relative pb-2 cursor-pointer transition-colors duration-300 border-b-2 md:border-b-0 hover:font-bold"
                        >
                            {{ $category['label'] }}
                        </button>
                    @endforeach

                    {{-- Animated underline indicator (desktop only) --}}
                    <span
                        id="{{ $indicatorId }}"
                        data-pricing-tab-indicator
                        class="hidden md:block absolute bottom-0 h-1 bg-red-brand transition-all duration-300 ease-out rounded-full"
                    ></span>
                </div>
            </div>
        @endif
    </div>

    {{-- Plans Grid --}}
    <div id="{{ $gridId }}" data-pricing-plans-grid class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto container">
        @forelse ($flattenedPlans as $plan)
            {{-- Pricing Card --}}
            <article
                data-pricing-plan-card
                data-category="{{ $plan['category'] }}"
                class="pricing-card bg-white rounded-[24px] overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 {{ $plan['category'] !== $activeCategoryKey ? 'hidden ' : '' }}flex flex-col"
            >
                <div class="bg-purple-brand py-5 text-center text-2xl font-bold uppercase tracking-wide text-white">
                    {{ $plan['title'] }}
                </div>

                <div class="px-7 md:px-8 pt-6 pb-7 flex-1 {{ $isRtl ? 'text-right' : 'text-left' }}">
                    <ul class="space-y-4 text-[#3f3155] text-[19px] leading-[1.25] mb-7">
                        @foreach ($plan['features'] as $feature)
                            <li class="flex items-start gap-3">
                                <img
                                    src="{{ $doneIconPath }}"
                                    class="w-5 mt-0.5 flex-shrink-0 {{ ! empty($feature['available']) ? '' : 'opacity-35 grayscale' }}"
                                    alt="Done"
                                >
                                <span dir="auto" style="unicode-bidi: plaintext;" class="flex-1 {{ $isRtl ? 'text-right' : 'text-left' }} {{ ! empty($feature['available']) ? '' : 'text-slate-400 line-through' }}">
                                    {{ $feature['text'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <a
                        href="{{ $plan['button_url'] }}"
                        class="flex w-fit items-center justify-center mx-auto min-w-[190px] bg-red-brand text-white py-3.5 px-8 rounded-xl text-lg md:text-2xl leading-none font-medium hover:bg-opacity-90 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        {{ $buttonLabel }}
                    </a>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
                {{ __('No active hosting plans available yet.') }}
            </div>
        @endforelse
    </div>
</section>

@if ($categories->count() > 1 && $flattenedPlans->isNotEmpty())
    <script>
        (function () {
            const tabsWrapper = document.getElementById(@json($tabsWrapperId));
            const plansGrid = document.getElementById(@json($gridId));
            const indicator = document.getElementById(@json($indicatorId));

            if (!tabsWrapper || !plansGrid || tabsWrapper.dataset.hostingPricingBound === '1') {
                return;
            }

            tabsWrapper.dataset.hostingPricingBound = '1';

            const tabs = Array.from(tabsWrapper.querySelectorAll('[data-pricing-category-tab]'));
            const cards = Array.from(plansGrid.querySelectorAll('[data-pricing-plan-card]'));
            let activeCategory = @json($activeCategoryKey);

            const updateIndicator = () => {
                if (!indicator) {
                    return;
                }

                const activeTab = tabs.find((tab) => tab.dataset.category === activeCategory);

                if (!activeTab) {
                    indicator.style.width = '0px';
                    return;
                }

                const wrapperRect = tabsWrapper.getBoundingClientRect();
                const activeRect = activeTab.getBoundingClientRect();

                indicator.style.left = (activeRect.left - wrapperRect.left) + 'px';
                indicator.style.width = activeRect.width + 'px';
            };

            const applyCategory = (categoryKey) => {
                activeCategory = categoryKey;

                tabs.forEach((tab) => {
                    const isActive = tab.dataset.category === categoryKey;

                    tab.classList.toggle('text-purple-brand', isActive);
                    tab.classList.toggle('font-bold', isActive);
                    tab.classList.toggle('border-purple-brand', isActive);
                    tab.classList.toggle('text-gray-400', !isActive);
                    tab.classList.toggle('border-transparent', !isActive);
                });

                cards.forEach((card) => {
                    card.classList.toggle('hidden', card.dataset.category !== categoryKey);
                });

                window.requestAnimationFrame(updateIndicator);
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', function () {
                    applyCategory(tab.dataset.category || '');
                });
            });

            window.addEventListener('resize', updateIndicator);
            applyCategory(activeCategory);
        })();
    </script>
@endif
