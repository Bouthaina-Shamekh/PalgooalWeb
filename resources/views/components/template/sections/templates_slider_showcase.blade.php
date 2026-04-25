@php
    $payload = is_array($data ?? null) ? $data : (is_array($content ?? null) ? $content : []);

    $style = isset($section) && is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-20';

    $brandPrefix = trim((string) ($payload['brand_prefix'] ?? 'PAL'));
    $brandSuffix = trim((string) ($payload['brand_suffix'] ?? 'GOALS'));
    $sectionTitle = trim((string) ($payload['title'] ?? __('TEMPLATE')));
    $sectionDescription = trim(
        (string) ($payload['description'] ?? __('Choose from a range of templates and publish them instantly')),
    );
    $buyLabel = trim((string) ($payload['buy_label'] ?? __('Buy Now')));
    $previewLabel = trim((string) ($payload['preview_label'] ?? __('Live Preview')));

    if ($buyLabel === '') {
        $buyLabel = __('Buy Now');
    }

    if ($previewLabel === '') {
        $previewLabel = __('Live Preview');
    }

    $templates = collect($payload['templates'] ?? [])
        ->map(function ($template): ?array {
            if (!$template) {
                return null;
            }

            $translation = $template->translation(app()->getLocale()) ?? $template->translations->first();
            $slug = trim((string) ($translation?->slug ?? ''));
            $name = trim((string) ($translation?->name ?? __('Template')));
            $description = trim(strip_tags((string) ($translation?->description ?? '')));
            $previewSource = trim((string) ($translation?->preview_url ?? ''));
            $image =
                is_string($template->image ?? null) && $template->image !== ''
                    ? asset('storage/' . ltrim($template->image, '/'))
                    : null;

            if ($name === '') {
                $name = __('Template');
            }

            $detailsUrl = $slug !== '' ? route('template.show', $slug, false) : '#';
            $redesignUrl = $slug !== '' ? route('template.show.redesign', $slug, false) : $detailsUrl;
            $hasValidPreviewSource =
                $previewSource !== '' &&
                (filter_var($previewSource, FILTER_VALIDATE_URL) || str_starts_with($previewSource, '//'));
            $previewUrl =
                $slug !== '' && $hasValidPreviewSource ? route('template.preview', $slug, false) : $detailsUrl;

            return [
                'id' => $template->id,
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'buy_url' => $redesignUrl,
                'preview_url' => $previewUrl,
            ];
        })
        ->filter()
        ->values();

    $sectionInstanceKey = 'templates-slider-' . ($section->id ?? uniqid());
    $sectionDomId = isset($section) && isset($section->id) ? 'templates-' . $section->id : 'templates';
    $sliderId = $sectionInstanceKey . '-track';
    $indicatorsId = $sectionInstanceKey . '-indicators';
@endphp

{{-- Templates Section --}}
<section id="{{ $sectionDomId }}" class="relative overflow-hidden bg-gray-light {{ $paddingY }}">
    <div aria-hidden="true"
        class="pointer-events-none absolute -top-24 -right-16 h-72 w-72 rounded-full bg-purple-brand/5 blur-3xl"></div>
    <div aria-hidden="true"
        class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-red-brand/5 blur-3xl"></div>

    <div class="container relative mx-auto">
        {{-- Section Header --}}
        <div class="mx-auto mb-16 max-w-5xl px-4 text-center md:mb-20">
            <p class="mb-4 inline-flex items-center gap-1 text-base font-extrabold tracking-[0.3em] md:text-xl">
                <span class="text-red-brand">{{ $brandPrefix }}</span>
                <span class="text-purple-brand">{{ $brandSuffix }}</span>
            </p>

            @if ($sectionTitle !== '')
                <h2 class="text-3xl font-extrabold leading-tight text-slate-950 md:text-4xl lg:whitespace-nowrap lg:text-[3.25rem]">
                    {{ $sectionTitle }}
                </h2>
            @endif

            @if ($sectionDescription !== '')
                <p class="mx-auto mt-4 max-w-2xl text-base leading-8 text-slate-500 md:text-lg">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>

        {{-- Templates Slider Container --}}
        <div class="relative">
            @if ($templates->count() > 1)
                <div
                    class="pointer-events-none absolute inset-y-1/2 z-20 hidden w-full -translate-y-1/2 items-center justify-between px-2 lg:flex xl:-left-10 xl:w-[calc(100%+5rem)]">
                    <button type="button" data-templates-slider-prev
                        class="pointer-events-auto inline-flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition-all duration-300 hover:-translate-x-1 hover:bg-slate-50 hover:text-slate-900"
                        aria-label="{{ __('Previous template') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" class="h-6 w-6 transition-transform rtl:rotate-180">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
                        </svg>
                    </button>

                    <button type="button" data-templates-slider-next
                        class="pointer-events-auto inline-flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition-all duration-300 hover:translate-x-1 hover:bg-slate-50 hover:text-slate-900"
                        aria-label="{{ __('Next template') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" class="h-6 w-6 transition-transform rtl:rotate-180">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- Slider Wrapper with Scroll Snap --}}
            <div id="{{ $sliderId }}" data-templates-slider dir="ltr"
                class="scrollbar-hide flex select-none gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory px-4 pb-6 pt-2 md:gap-6 md:px-6 lg:px-12">
                @forelse ($templates as $template)
                    {{-- Template Card --}}
                    <div data-template-slide dir="rtl"
                        class="w-[85vw] flex-shrink-0 snap-center snap-always md:w-[60vw] lg:w-[45vw] xl:w-[38vw]">
                        <div
                            class="group flex h-full flex-col overflow-hidden rounded-[2rem] border border-slate-100 bg-white p-5 transition-all duration-500 hover:-translate-y-2 hover:scale-[1.01] md:p-6">
                            {{-- Card Image --}}
                            <div class="relative mb-5 overflow-hidden rounded-[1.5rem]">
                                @if ($template['image'])
                                    <img src="{{ $template['image'] }}"
                                        class="h-56 w-full rounded-[1.5rem] object-cover transition duration-500 group-hover:scale-[1.03] md:h-72"
                                        alt="{{ $template['name'] }}" loading="lazy">
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded-[1.5rem] bg-gradient-to-t from-slate-950/15 via-transparent to-transparent">
                                    </div>
                                @else
                                    <div
                                        class="flex h-56 w-full items-center justify-center rounded-[1.5rem] bg-slate-100 px-6 text-center text-lg font-semibold text-slate-500 md:h-72">
                                        {{ $template['name'] }}
                                    </div>
                                @endif
                            </div>

                            {{-- Card Content --}}
                            <div class="flex flex-1 flex-col">
                                <h3 class="truncate text-xl font-extrabold leading-snug text-slate-950 md:text-2xl">
                                    {{ $template['name'] }}
                                </h3>

                                @if (!empty($template['description']))
                                    <p class="mt-3 truncate text-sm text-slate-500 md:text-base">
                                        {{ $template['description'] }}
                                    </p>
                                @endif

                                <div class="mt-6 flex flex-col gap-3 text-base sm:flex-row">
                                    <a href="{{ $template['buy_url'] }}"
                                        class="inline-flex flex-1 items-center justify-center rounded-2xl bg-red-brand px-5 py-3 text-sm font-extrabold text-white transition-all duration-300 hover:-translate-y-1 hover:bg-red-brand/90 md:px-7 md:py-3.5 md:text-base">
                                        {{ $buyLabel }}
                                    </a>

                                    <a href="{{ $template['preview_url'] }}"
                                        class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-bold text-slate-700 transition-all duration-300 hover:-translate-y-1 hover:bg-slate-100 md:px-7 md:py-3.5 md:text-base">
                                        {{ $previewLabel }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="mx-4 rounded-[2rem] border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
                        {{ __('No templates available yet.') }}
                    </div>
                @endforelse

            </div>
        </div>

        @if ($templates->count() > 1)
            {{-- Slider Indicators --}}
            <div id="{{ $indicatorsId }}" class="mt-12 flex justify-center gap-3 md:mt-16" dir="ltr"></div>
        @endif
    </div>
</section>

@if ($templates->count() > 1)
    <script>
        (function() {
            const slider = document.querySelector(@json('#' . $sliderId));
            const indicatorsContainer = document.querySelector(@json('#' . $indicatorsId));
            const sectionRoot = slider?.closest('section');
            const prevButton = sectionRoot?.querySelector('[data-templates-slider-prev]');
            const nextButton = sectionRoot?.querySelector('[data-templates-slider-next]');

            if (!slider || !indicatorsContainer || slider.dataset.templatesSliderBound === '1') {
                return;
            }

            slider.dataset.templatesSliderBound = '1';

            const cards = Array.from(slider.querySelectorAll('[data-template-slide]'));

            if (cards.length < 2) {
                return;
            }

            let currentIndex = 0;
            let scrollTimeout = null;
            let resizeTimeout = null;

            function clampScrollOffset(offset) {
                const maxScrollLeft = Math.max(0, slider.scrollWidth - slider.clientWidth);

                return Math.max(0, Math.min(offset, maxScrollLeft));
            }

            function getNormalizedScrollLeft(el) {
                const style = window.getComputedStyle(el);

                if (style.direction === 'rtl') {
                    return el.scrollWidth - el.clientWidth - el.scrollLeft;
                }

                return el.scrollLeft;
            }

            function updateButtonStates() {
                if (!prevButton || !nextButton) {
                    return;
                }

                const isFirst = currentIndex === 0;
                const isLast = currentIndex === cards.length - 1;

                prevButton.disabled = isFirst;
                nextButton.disabled = isLast;
                prevButton.classList.toggle('opacity-40', isFirst);
                prevButton.classList.toggle('cursor-not-allowed', isFirst);
                nextButton.classList.toggle('opacity-40', isLast);
                nextButton.classList.toggle('cursor-not-allowed', isLast);
            }

            function syncCarouselLayout() {
                const referenceCard = cards[0];

                if (!referenceCard) {
                    return;
                }

                const trackRect = slider.getBoundingClientRect();
                const nextButtonRect = nextButton?.getBoundingClientRect();
                const buttonEdgePadding = nextButtonRect
                    ? Math.round(nextButtonRect.right - trackRect.left) + 8
                    : 72;
                const sidePadding = Math.max(0, buttonEdgePadding);

                slider.style.paddingLeft = `${sidePadding}px`;
                slider.style.paddingRight = `${sidePadding}px`;
                slider.style.scrollPaddingLeft = `${sidePadding}px`;
                slider.style.scrollPaddingRight = `${sidePadding}px`;
            }

            function renderIndicators() {
                indicatorsContainer.innerHTML = '';

                cards.forEach((_, index) => {
                    const indicator = document.createElement('button');

                    indicator.type = 'button';
                    indicator.dataset.index = String(index);
                    indicator.className =
                        'indicator-dot h-2.5 w-2.5 rounded-full bg-slate-300 transition-all duration-500 cursor-pointer hover:bg-slate-400';
                    indicator.setAttribute('aria-label', `Go to template ${index + 1}`);

                    indicator.addEventListener('click', function() {
                        scrollToSlide(index);
                    });

                    indicatorsContainer.appendChild(indicator);
                });
            }

            function updateIndicators() {
                const indicators = indicatorsContainer.querySelectorAll('.indicator-dot');

                indicators.forEach((indicator, index) => {
                    const isActive = index === currentIndex;

                    indicator.classList.toggle('w-12', isActive);
                    indicator.classList.toggle('bg-purple-brand', isActive);
                    indicator.classList.toggle('w-2.5', !isActive);
                    indicator.classList.toggle('bg-slate-300', !isActive);
                });

                updateButtonStates();
            }

            function scrollToSlide(index, behavior = 'smooth') {
                const card = cards[index];

                if (!card) {
                    return;
                }

                currentIndex = index;
                updateIndicators();
                updateButtonStates();

                const sliderRect = slider.getBoundingClientRect();
                const cardRect = card.getBoundingClientRect();
                const scrollOffset = slider.scrollLeft +
                    (cardRect.left - sliderRect.left) -
                    ((slider.clientWidth - cardRect.width) / 2);

                slider.scrollTo({
                    left: clampScrollOffset(scrollOffset),
                    behavior
                });
            }

            function scrollSlider(direction) {
                let nextIndex = direction === 'next' ? currentIndex + 1 : currentIndex - 1;
                nextIndex = Math.max(0, Math.min(nextIndex, cards.length - 1));

                scrollToSlide(nextIndex);
            }

            function syncCurrentIndexFromScroll() {
                const sliderRect = slider.getBoundingClientRect();
                const normalizedScrollLeft = getNormalizedScrollLeft(slider);
                const sliderCenter = normalizedScrollLeft + (slider.clientWidth / 2);
                let closestIndex = 0;
                let minDistance = Infinity;

                cards.forEach((card, index) => {
                    const cardRect = card.getBoundingClientRect();
                    const cardCenter = normalizedScrollLeft +
                        (cardRect.left - sliderRect.left) +
                        (cardRect.width / 2);
                    const distance = Math.abs(cardCenter - sliderCenter);

                    if (distance < minDistance) {
                        minDistance = distance;
                        closestIndex = index;
                    }
                });

                if (closestIndex !== currentIndex) {
                    currentIndex = closestIndex;
                    updateIndicators();
                } else {
                    updateButtonStates();
                }
            }

            slider.addEventListener('scroll', function() {
                window.clearTimeout(scrollTimeout);
                scrollTimeout = window.setTimeout(syncCurrentIndexFromScroll, 100);
            }, {
                passive: true
            });

            if (prevButton) {
                prevButton.addEventListener('click', function() {
                    scrollSlider('prev');
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function() {
                    scrollSlider('next');
                });
            }

            function syncLayoutAndPosition() {
                syncCarouselLayout();
                scrollToSlide(currentIndex, 'auto');
            }

            window.addEventListener('resize', function() {
                window.clearTimeout(resizeTimeout);
                resizeTimeout = window.setTimeout(syncLayoutAndPosition, 120);
            });

            renderIndicators();
            const initializeCarousel = function() {
                syncLayoutAndPosition();
                syncCurrentIndexFromScroll();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeCarousel, {
                    once: true
                });
            } else {
                requestAnimationFrame(initializeCarousel);
            }
        })();
    </script>
@endif
