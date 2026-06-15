@php
    $payload = is_array($data ?? null)
        ? $data
        : (is_array($content ?? null) ? $content : []);

    $style = isset($section) && is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-4';

    $breadcrumbLabel = trim((string) ($payload['breadcrumb_label'] ?? __('Templates')));
    $sectionTitle = trim((string) ($payload['title'] ?? __('TEMPLATE')));
    $sectionDescription = trim((string) ($payload['description'] ?? __('Choose from a range of templates and publish them instantly')));
    $allCategoriesLabel = trim((string) ($payload['all_categories_label'] ?? __('All Hosting')));
    $typeLabel = trim((string) ($payload['type_label'] ?? __('Type')));
    $bestSellersLabel = trim((string) ($payload['best_sellers_label'] ?? __('Best Sellers')));
    $priceLabel = trim((string) ($payload['price_label'] ?? __('Price')));
    $buyLabel = trim((string) ($payload['buy_label'] ?? __('Buy Now')));
    $previewLabel = trim((string) ($payload['preview_label'] ?? __('Live Preview')));
    $itemsPerPage = isset($payload['items_per_page']) && is_numeric($payload['items_per_page'])
        ? max(1, (int) $payload['items_per_page'])
        : 12;

    if ($breadcrumbLabel === '') {
        $breadcrumbLabel = __('Templates');
    }

    if ($sectionTitle === '') {
        $sectionTitle = __('TEMPLATE');
    }

    if ($sectionDescription === '') {
        $sectionDescription = __('Choose from a range of templates and publish them instantly');
    }

    if ($allCategoriesLabel === '') {
        $allCategoriesLabel = __('All Hosting');
    }

    if ($typeLabel === '') {
        $typeLabel = __('Type');
    }

    if ($bestSellersLabel === '') {
        $bestSellersLabel = __('Best Sellers');
    }

    if ($priceLabel === '') {
        $priceLabel = __('Price');
    }

    if ($buyLabel === '') {
        $buyLabel = __('Buy Now');
    }

    if ($previewLabel === '') {
        $previewLabel = __('Live Preview');
    }

    $templates = collect($payload['templates'] ?? [])
        ->map(function ($template): ?array {
            if (! $template) {
                return null;
            }

            $translation = $template->translation(app()->getLocale()) ?? $template->translations->first();
            $slug = trim((string) ($translation?->slug ?? ''));
            $name = trim((string) ($translation?->name ?? __('Template')));
            $previewSource = trim((string) ($translation?->preview_url ?? ''));
            $detailsUrl = $slug !== '' ? route('template.show', $slug, false) : '#';
            $redesignUrl = $slug !== '' ? route('template.show.redesign', $slug, false) : $detailsUrl;
            $hasValidPreviewSource = $previewSource !== ''
                && (filter_var($previewSource, FILTER_VALIDATE_URL) || str_starts_with($previewSource, '//'));
            $previewUrl = ($slug !== '' && $hasValidPreviewSource)
                ? route('template.preview', $slug, false)
                : $redesignUrl;

            $image = is_string($template->image ?? null) && $template->image !== ''
                ? asset('storage/' . ltrim($template->image, '/'))
                : null;

            $category = $template->categoryTemplate;
            $categoryTranslation = $category?->getTranslation(app()->getLocale())
                ?? $category?->translations?->firstWhere('locale', config('app.fallback_locale', 'ar'))
                ?? $category?->translations?->first();
            $categoryLabel = trim((string) ($categoryTranslation?->name ?? __('Other')));
            $categoryKey = trim((string) ($categoryTranslation?->slug ?? $category?->translated_slug ?? 'uncategorized'));
            $categoryKey = $categoryKey !== '' ? \Illuminate\Support\Str::lower($categoryKey) : 'uncategorized';
            $price = (float) ($template->discount_price ?? $template->price ?? 0);
            $rating = is_numeric($template->rating ?? null) ? (float) $template->rating : 0.0;

            if ($name === '') {
                $name = __('Template');
            }

            if ($categoryLabel === '') {
                $categoryLabel = __('Other');
            }

            return [
                'id' => $template->id,
                'name' => $name,
                'image' => $image,
                'buy_url' => $redesignUrl,
                'preview_url' => $previewUrl,
                'category_key' => $categoryKey,
                'category_label' => $categoryLabel,
                'price' => $price,
                'rating' => $rating,
            ];
        })
        ->filter()
        ->values();

    $categories = $templates
        ->map(fn (array $template) => [
            'key' => $template['category_key'],
            'label' => $template['category_label'],
        ])
        ->unique('key')
        ->sortBy('label')
        ->values();

    $sectionInstanceKey = 'templates-listing-' . ($section->id ?? uniqid());
    $sectionDomId = isset($section) && isset($section->id) ? 'templates-' . $section->id : 'templates';
@endphp

<section
    id="{{ $sectionDomId }}"
    data-templates-listing-root
    data-items-per-page="{{ $itemsPerPage }}"
    class="bg-[#F2F2F2] px-4 sm:px-6 lg:px-12 {{ $paddingY }}"
>
    <div class="container mx-auto">
        <p class="animate-from-left mb-10 flex items-center gap-2 text-base capitalize text-gray-dark">
            <a href="{{ url('/') }}" class="flex items-center gap-1 transition-colors hover:text-purple-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="14.056" height="11.948" viewBox="0 0 14.056 11.948" aria-hidden="true">
                    <path
                        d="M8.622,16.448V12.231h2.811v4.217h3.514V10.825h2.108L10.028,4.5,3,10.825H5.108v5.622Z"
                        transform="translate(-3 -4.5)"
                    />
                </svg>
                <span>{{ __('Home') }}</span>
            </a>
            <span>/ {{ $breadcrumbLabel }}</span>
        </p>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
            <div class="animate-from-right">
                <h2 class="mb-2 text-3xl font-extrabold uppercase text-purple-brand md:text-4xl lg:text-5xl">
                    {{ $sectionTitle }}
                </h2>

                <p class="max-w-2xl text-base text-gray-dark">
                    {{ $sectionDescription }}
                </p>
            </div>

            <div class="animate-from-left relative z-20 flex flex-wrap gap-4 sm:items-center sm:justify-between">
                <div class="relative z-[100]" data-templates-dropdown>
                    <button
                        type="button"
                        data-templates-dropdown-button
                        class="flex min-w-[195px] items-center justify-between gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-base font-medium text-purple-brand transition-all hover:shadow-md"
                    >
                        <span data-templates-dropdown-label>{{ $allCategoriesLabel }}</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        data-templates-dropdown-menu
                        class="absolute top-full z-[100] mt-2 hidden w-full min-w-[195px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg ltr:left-0 rtl:right-0"
                    >
                        <button
                            type="button"
                            data-hosting-option="all"
                            data-label="{{ $allCategoriesLabel }}"
                            class="block w-full px-4 py-3 text-purple-brand transition-colors hover:bg-gray-100 ltr:text-left rtl:text-right"
                        >
                            {{ $allCategoriesLabel }}
                        </button>

                        @foreach ($categories as $category)
                            <button
                                type="button"
                                data-hosting-option="{{ $category['key'] }}"
                                data-label="{{ $category['label'] }}"
                                class="block w-full px-4 py-3 text-purple-brand transition-colors hover:bg-gray-100 ltr:text-left rtl:text-right"
                            >
                                {{ $category['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2 rounded-xl bg-[#E9E9E9] p-1.5 text-lg md:text-xl">
                    <button
                        type="button"
                        data-sort="type"
                        class="filter-sort-btn rounded-xl bg-purple-brand px-4 py-1 text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-purple-brand"
                    >
                        {{ $typeLabel }}
                    </button>
                    <button
                        type="button"
                        data-sort="best-sellers"
                        class="filter-sort-btn rounded-xl border border-gray-200 px-4 py-1 text-purple-brand transition-all duration-300 hover:-translate-y-0.5 hover:bg-purple-brand hover:text-white"
                    >
                        {{ $bestSellersLabel }}
                    </button>
                    <button
                        type="button"
                        data-sort="price"
                        class="filter-sort-btn rounded-xl border border-gray-200 px-4 py-1 text-purple-brand transition-all duration-300 hover:-translate-y-0.5 hover:bg-purple-brand hover:text-white"
                    >
                        {{ $priceLabel }}
                    </button>
                </div>
            </div>
        </div>

        <div data-templates-grid class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:gap-8">
            @forelse ($templates as $template)
                <div
                    data-template-card
                    class="template-card animate-card overflow-hidden rounded-[20px] bg-white p-4 transition-all duration-300 hover:-translate-y-2 hover:shadow-lg md:p-6"
                    data-hosting="{{ $template['category_key'] }}"
                    data-category-label="{{ $template['category_label'] }}"
                    data-name="{{ $template['name'] }}"
                    data-price="{{ $template['price'] }}"
                    data-rating="{{ $template['rating'] }}"
                >
                    <div class="mb-4">
                        @if ($template['image'])
                            <img
                                src="{{ $template['image'] }}"
                                class="h-auto w-full rounded-xl object-cover md:h-[165px] md:rounded-[20px] lg:h-[300px]"
                                alt="{{ $template['name'] }}"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-[220px] w-full items-center justify-center rounded-xl bg-slate-100 px-6 text-center text-lg font-semibold text-slate-500 md:rounded-[20px] lg:h-[300px]">
                                {{ $template['name'] }}
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <h3 class="text-lg text-purple-brand md:text-xl">
                            {{ $template['name'] }}
                        </h3>

                        <div class="flex flex-wrap gap-3">
                            <a
                                href="{{ $template['buy_url'] }}"
                                class="rounded-xl bg-purple-brand px-6 py-2.5 text-base text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-opacity-90 hover:shadow-lg"
                            >
                                {{ $buyLabel }}
                            </a>

                            <a
                                href="{{ $template['preview_url'] }}"
                                class="rounded-xl bg-gray-200 px-6 py-2.5 text-base text-purple-brand transition-all duration-300 hover:-translate-y-0.5 hover:bg-gray-300"
                            >
                                {{ $previewLabel }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[20px] border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500 md:col-span-2">
                    {{ __('No templates available yet.') }}
                </div>
            @endforelse
        </div>

        @if ($templates->isNotEmpty())
            <div
                data-empty-state
                class="mt-8 hidden rounded-[20px] border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500"
            >
                {{ __('No templates matched the selected filters.') }}
            </div>

            <div
                data-pagination
                class="animate-from-up mt-12 flex flex-wrap items-center justify-center gap-2 md:mt-16"
            >
                <button
                    type="button"
                    data-pagination-prev
                    class="flex h-10 w-10 items-center justify-center rounded-xl text-purple-brand transition-all hover:bg-white hover:shadow-md disabled:pointer-events-none disabled:opacity-50"
                >
                    <svg class="h-5 w-5 ltr:rotate-180 rtl:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div data-pagination-numbers class="flex items-center gap-2"></div>

                <button
                    type="button"
                    data-pagination-next
                    class="flex h-10 w-10 items-center justify-center rounded-xl text-purple-brand transition-all hover:bg-white hover:shadow-md disabled:pointer-events-none disabled:opacity-50"
                >
                    <svg class="h-5 w-5 ltr:rotate-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        @endif
    </div>
</section>

@if ($templates->isNotEmpty())
    <script>
        (function () {
            const root = document.getElementById(@json($sectionDomId));

            if (!root || root.dataset.templatesListingBound === '1') {
                return;
            }

            root.dataset.templatesListingBound = '1';

            const grid = root.querySelector('[data-templates-grid]');
            const cards = Array.from(root.querySelectorAll('[data-template-card]'));
            const dropdown = root.querySelector('[data-templates-dropdown]');
            const dropdownButton = root.querySelector('[data-templates-dropdown-button]');
            const dropdownMenu = root.querySelector('[data-templates-dropdown-menu]');
            const dropdownLabel = root.querySelector('[data-templates-dropdown-label]');
            const categoryButtons = Array.from(root.querySelectorAll('[data-hosting-option]'));
            const sortButtons = Array.from(root.querySelectorAll('[data-sort]'));
            const pagination = root.querySelector('[data-pagination]');
            const prevButton = root.querySelector('[data-pagination-prev]');
            const nextButton = root.querySelector('[data-pagination-next]');
            const pageNumbers = root.querySelector('[data-pagination-numbers]');
            const emptyState = root.querySelector('[data-empty-state]');
            const itemsPerPage = Math.max(1, Number(root.dataset.itemsPerPage || 12));
            const collator = new Intl.Collator(document.documentElement.lang || undefined, {
                numeric: true,
                sensitivity: 'base',
            });

            const state = {
                category: 'all',
                sort: 'type',
                page: 1,
            };

            function closeDropdown() {
                if (dropdownMenu) {
                    dropdownMenu.classList.add('hidden');
                }
            }

            function updateSortButtons() {
                sortButtons.forEach((button) => {
                    const isActive = button.dataset.sort === state.sort;

                    button.classList.toggle('bg-purple-brand', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('border-transparent', isActive);
                    button.classList.toggle('border', !isActive);
                    button.classList.toggle('border-gray-200', !isActive);
                    button.classList.toggle('text-purple-brand', !isActive);
                });
            }

            function getVisibleCards() {
                const filteredCards = cards.filter((card) => {
                    return state.category === 'all' || card.dataset.hosting === state.category;
                });

                return filteredCards.sort((a, b) => {
                    const nameA = a.dataset.name || '';
                    const nameB = b.dataset.name || '';

                    if (state.sort === 'price') {
                        const priceA = Number(a.dataset.price || 0);
                        const priceB = Number(b.dataset.price || 0);

                        return priceA - priceB || collator.compare(nameA, nameB);
                    }

                    if (state.sort === 'best-sellers') {
                        const ratingA = Number(a.dataset.rating || 0);
                        const ratingB = Number(b.dataset.rating || 0);

                        return ratingB - ratingA || collator.compare(nameA, nameB);
                    }

                    const categoryA = a.dataset.categoryLabel || '';
                    const categoryB = b.dataset.categoryLabel || '';

                    return collator.compare(categoryA, categoryB) || collator.compare(nameA, nameB);
                });
            }

            function renderPagination(totalPages) {
                if (!pagination || !pageNumbers || !prevButton || !nextButton) {
                    return;
                }

                pagination.classList.toggle('hidden', totalPages <= 1);
                pageNumbers.innerHTML = '';

                prevButton.disabled = state.page <= 1;
                nextButton.disabled = state.page >= totalPages;

                for (let page = 1; page <= totalPages; page += 1) {
                    const button = document.createElement('button');
                    const isActive = page === state.page;

                    button.type = 'button';
                    button.textContent = String(page);
                    button.className = isActive
                        ? 'flex h-10 min-w-10 items-center justify-center rounded-xl bg-purple-brand px-3 text-sm font-semibold text-white shadow-md'
                        : 'flex h-10 min-w-10 items-center justify-center rounded-xl bg-white px-3 text-sm font-semibold text-purple-brand transition-all hover:shadow-md';

                    button.addEventListener('click', function () {
                        state.page = page;
                        render();
                    });

                    pageNumbers.appendChild(button);
                }
            }

            function render() {
                const orderedCards = getVisibleCards();
                const totalPages = orderedCards.length > 0
                    ? Math.ceil(orderedCards.length / itemsPerPage)
                    : 0;

                if (totalPages > 0 && state.page > totalPages) {
                    state.page = totalPages;
                }

                orderedCards.forEach((card) => grid.appendChild(card));

                const hiddenCards = cards.filter((card) => !orderedCards.includes(card));
                hiddenCards.forEach((card) => grid.appendChild(card));

                const startIndex = Math.max(0, (state.page - 1) * itemsPerPage);
                const currentPageCards = new Set(orderedCards.slice(startIndex, startIndex + itemsPerPage));

                cards.forEach((card) => {
                    card.classList.toggle('hidden', !currentPageCards.has(card));
                });

                if (emptyState) {
                    emptyState.classList.toggle('hidden', orderedCards.length !== 0);
                }

                renderPagination(totalPages);
            }

            dropdownButton?.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropdownMenu?.classList.toggle('hidden');
            });

            categoryButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    state.category = button.dataset.hostingOption || 'all';
                    state.page = 1;

                    if (dropdownLabel) {
                        dropdownLabel.textContent = button.dataset.label || button.textContent.trim();
                    }

                    closeDropdown();
                    render();
                });
            });

            sortButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const nextSort = button.dataset.sort || 'type';

                    if (state.sort === nextSort) {
                        return;
                    }

                    state.sort = nextSort;
                    state.page = 1;
                    updateSortButtons();
                    render();
                });
            });

            prevButton?.addEventListener('click', function () {
                if (state.page <= 1) {
                    return;
                }

                state.page -= 1;
                render();
            });

            nextButton?.addEventListener('click', function () {
                const totalPages = Math.ceil(getVisibleCards().length / itemsPerPage);

                if (state.page >= totalPages) {
                    return;
                }

                state.page += 1;
                render();
            });

            document.addEventListener('click', function (event) {
                if (!dropdown || dropdown.contains(event.target)) {
                    return;
                }

                closeDropdown();
            });

            updateSortButtons();
            render();
        })();
    </script>
@endif
