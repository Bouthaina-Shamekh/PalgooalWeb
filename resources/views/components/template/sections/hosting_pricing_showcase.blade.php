@php
    $payload = is_array($data ?? null)
        ? $data
        : (is_array($content ?? null) ? $content : []);

    $sectionId = isset($section) ? ($section->id ?? null) : null;
    $sectionTitle = trim((string) ($payload['title'] ?? $title ?? __('HOSTING')));
    $sectionDescription = trim((string) ($payload['description'] ?? ''));
    $buttonLabel = trim((string) ($payload['button_label'] ?? __('Choose Now')));
    $buttonLabel = $buttonLabel !== '' ? $buttonLabel : __('Choose Now');

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
                    $features = collect(is_array($translation?->features ?? null) ? $translation->features : [])
                        ->map(function ($feature) {
                            if (is_array($feature)) {
                                return trim((string) ($feature['text'] ?? $feature['title'] ?? $feature['label'] ?? ''));
                            }

                            return trim((string) $feature);
                        })
                        ->filter()
                        ->values()
                        ->all();

                    $billingPeriod = $plan->monthly_price_cents !== null ? 'monthly' : 'annually';
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

{{-- Hosting pricing section --}}
<section id="price" class="bg-gray-light px-4 py-12 sm:px-6 lg:px-12 lg:py-20">
    {{-- Section header --}}
    <div class="mb-12 text-center">
        @if ($sectionTitle !== '')
            <h3 class="text-4xl font-extrabold text-purple-brand md:text-5xl">{{ $sectionTitle }}</h3>
        @endif

        @if ($sectionDescription !== '')
            <p class="mb-8 text-base text-gray-500 md:text-lg">
                {{ $sectionDescription }}
            </p>
        @endif

        @if ($categories->isNotEmpty())
            {{-- Category filter tabs --}}
            <div class="mb-8 flex justify-center">
                <div
                    id="{{ $tabsWrapperId }}"
                    data-hosting-pricing-tabs
                    class="relative flex flex-wrap justify-center gap-6 text-lg md:gap-12 md:text-xl"
                >
                    @foreach ($categories as $category)
                        <button
                            type="button"
                            data-pricing-category-tab
                            data-category="{{ $category['key'] }}"
                            class="{{ $category['key'] === $activeCategoryKey ? 'border-purple-brand text-purple-brand font-bold' : 'border-transparent text-gray-400 hover:text-purple-brand' }} relative border-b-2 pb-2 transition-colors duration-300 hover:font-bold md:border-b-0"
                        >
                            {{ $category['label'] }}
                        </button>
                    @endforeach

                    {{-- Animated underline indicator (desktop only) --}}
                    <span
                        id="{{ $indicatorId }}"
                        data-pricing-tab-indicator
                        class="absolute bottom-0 hidden h-1 rounded-full bg-red-brand transition-all duration-300 ease-out md:block"
                    ></span>
                </div>
            </div>
        @endif
    </div>

    {{-- Plans grid --}}
    <div id="{{ $gridId }}" data-pricing-plans-grid class="container mx-auto grid max-w-7xl grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($flattenedPlans as $plan)
            {{-- Pricing card --}}
            <article
                data-pricing-plan-card
                data-category="{{ $plan['category'] }}"
                class="pricing-card {{ $plan['category'] !== $activeCategoryKey ? 'hidden ' : '' }}flex flex-col overflow-hidden rounded-[24px] bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
            >
                <div class="bg-purple-brand py-5 text-center text-2xl font-bold uppercase tracking-wide text-white">
                    {{ $plan['title'] }}
                </div>

                <div class="flex-1 px-7 pb-7 pt-6 md:px-8 ltr:text-left rtl:text-right">
                    <ul class="mb-7 space-y-4 text-[19px] leading-[1.25] text-[#3f3155]">
                        @foreach ($plan['features'] as $feature)
                            <li class="flex items-start gap-3 rtl:flex-row-reverse">
                                <svg class="mt-0.5 w-5 flex-shrink-0" viewBox="0 0 27 21" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="#BA112C" />
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a
                        href="{{ $plan['button_url'] }}"
                        class="mx-auto block min-w-[190px] rounded-xl bg-red-brand px-8 py-3.5 text-center text-lg font-medium leading-none text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-opacity-90 hover:shadow-lg md:text-2xl"
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
