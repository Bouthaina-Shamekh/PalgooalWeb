@php
    $payload = is_array($data ?? null)
        ? $data
        : (is_array($content ?? null) ? $content : []);

    $style = isset($section) && is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-20';

    $brandPrefix = trim((string) ($payload['brand_prefix'] ?? 'PAL'));
    $brandSuffix = trim((string) ($payload['brand_suffix'] ?? 'GOALS'));
    $sectionTitle = trim((string) ($payload['title'] ?? __('TEMPLATE')));
    $sectionDescription = trim((string) ($payload['description'] ?? __('Choose from a range of templates and publish them instantly')));
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
            if (! $template) {
                return null;
            }

            $translation = $template->translation(app()->getLocale()) ?? $template->translations->first();
            $slug = trim((string) ($translation?->slug ?? ''));
            $name = trim((string) ($translation?->name ?? __('Template')));
            $previewSource = trim((string) ($translation?->preview_url ?? ''));
            $image = is_string($template->image ?? null) && $template->image !== ''
                ? asset('storage/' . ltrim($template->image, '/'))
                : null;

            if ($name === '') {
                $name = __('Template');
            }

            $detailsUrl = $slug !== '' ? route('template.show', $slug, false) : '#';
            $previewUrl = ($slug !== '' && $previewSource !== '')
                ? route('template.preview', $slug, false)
                : $detailsUrl;

            return [
                'id' => $template->id,
                'name' => $name,
                'image' => $image,
                'buy_url' => route('checkout.cart', ['template_id' => $template->id], false),
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
<section id="{{ $sectionDomId }}" class="bg-gray-light {{ $paddingY }}">
    <div class="container mx-auto">
        {{-- Section Header --}}
        <div class="mb-12 px-4 text-center">
            <p class="mb-2 text-lg md:text-xl">
                <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
            </p>

            @if ($sectionTitle !== '')
                <h2 class="mb-2 text-4xl font-extrabold uppercase text-purple-brand md:text-5xl">
                    {{ $sectionTitle }}
                </h2>
            @endif

            @if ($sectionDescription !== '')
                <p class="mx-auto max-w-2xl text-base text-gray-dark">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>

        {{-- Templates Slider Container --}}
        <div class="relative">
            {{-- Slider Wrapper with Scroll Snap --}}
            <div
                id="{{ $sliderId }}"
                data-templates-slider
                class="scrollbar-hide flex select-none gap-6 overflow-x-auto scroll-smooth snap-x snap-proximity px-4 py-2 md:px-12 lg:px-24"
            >
                @forelse ($templates as $template)
                    {{-- Template Card --}}
                    <div data-template-slide class="w-[85vw] flex-shrink-0 snap-center md:w-[60vw] lg:w-[45vw] xl:w-[38vw]">
                        <div class="overflow-hidden rounded-3xl bg-white p-6 shadow-md transition-all duration-300 hover:-translate-y-2 hover:shadow-lg">
                            {{-- Card Image --}}
                            <div class="relative mb-4">
                                <div class="absolute inset-0 z-10"></div>

                                @if ($template['image'])
                                    <img
                                        src="{{ $template['image'] }}"
                                        class="h-40 w-full rounded-2xl object-cover md:h-66"
                                        alt="{{ $template['name'] }}"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex h-40 w-full items-center justify-center rounded-2xl bg-slate-100 px-6 text-center text-lg font-semibold text-slate-500 md:h-66">
                                        {{ $template['name'] }}
                                    </div>
                                @endif
                            </div>

                            {{-- Card Content --}}
                            <div class="flex flex-wrap items-center justify-between gap-4 md:flex-nowrap">
                                <h3 class="text-center text-lg text-purple-brand md:text-xl lg:text-start">
                                    {{ $template['name'] }}
                                </h3>

                                <div class="flex flex-auto justify-between gap-4 text-base md:flex-none lg:justify-start">
                                    <a
                                        href="{{ $template['buy_url'] }}"
                                        class="rounded-xl bg-purple-brand px-4 py-2 font-bold text-white transition-all duration-300 hover:-translate-y-1 hover:bg-opacity-90 hover:shadow-lg md:px-8 md:py-3"
                                    >
                                        {{ $buyLabel }}
                                    </a>

                                    <a
                                        href="{{ $template['preview_url'] }}"
                                        class="rounded-xl bg-gray-200 px-4 py-2 font-bold text-purple-brand transition-all duration-300 hover:-translate-y-1 hover:bg-gray-300 hover:shadow-lg md:px-8 md:py-3"
                                    >
                                        {{ $previewLabel }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="mx-4 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
                        {{ __('No templates available yet.') }}
                    </div>
                @endforelse

                @if ($templates->isNotEmpty())
                    {{-- Spacer to allow last card to scroll into view --}}
                    <div aria-hidden="true" class="w-[40vw] flex-shrink-0 md:w-[30vw] lg:w-[20vw]"></div>
                @endif
            </div>
        </div>

        @if ($templates->count() > 1)
            {{-- Slider Indicators --}}
            <div id="{{ $indicatorsId }}" class="mt-12 flex justify-center gap-2" dir="ltr"></div>
        @endif
    </div>
</section>

@if ($templates->count() > 1)
    <script>
        (function () {
            const slider = document.getElementById(@json($sliderId));
            const indicatorsContainer = document.getElementById(@json($indicatorsId));

            if (!slider || !indicatorsContainer || slider.dataset.templatesSliderBound === '1') {
                return;
            }

            slider.dataset.templatesSliderBound = '1';

            const cards = Array.from(slider.querySelectorAll('[data-template-slide]'));

            if (cards.length < 2) {
                return;
            }

            const isRtl = document.documentElement.dir === 'rtl'
                || window.getComputedStyle(slider).direction === 'rtl';
            const rtlScrollType = isRtl ? detectRtlScrollType() : 'default';
            const dragSensitivity = 1.05;

            let scrollRaf = null;
            let isDown = false;
            let startX = 0;
            let startScrollLeft = 0;
            let hasDragged = false;
            let suppressClick = false;

            slider.style.scrollBehavior = 'auto';
            slider.style.touchAction = 'pan-y';
            slider.classList.add('cursor-grab');

            function detectRtlScrollType() {
                const probe = document.createElement('div');
                const child = document.createElement('div');

                probe.dir = 'rtl';
                probe.style.position = 'absolute';
                probe.style.top = '-9999px';
                probe.style.width = '4px';
                probe.style.height = '1px';
                probe.style.overflow = 'scroll';
                probe.style.visibility = 'hidden';

                child.style.width = '8px';
                child.style.height = '1px';

                probe.appendChild(child);
                document.body.appendChild(probe);

                let type = 'reverse';

                if (probe.scrollLeft > 0) {
                    type = 'default';
                } else {
                    probe.scrollLeft = 1;

                    if (probe.scrollLeft === 0) {
                        type = 'negative';
                    }
                }

                document.body.removeChild(probe);

                return type;
            }

            function getMaxScroll() {
                return Math.max(0, slider.scrollWidth - slider.clientWidth);
            }

            function getNormalizedScrollLeft() {
                const raw = slider.scrollLeft;

                if (!isRtl) {
                    return raw;
                }

                if (rtlScrollType === 'negative') {
                    return Math.abs(raw);
                }

                if (rtlScrollType === 'reverse') {
                    return getMaxScroll() - raw;
                }

                return raw;
            }

            function toRawScrollLeft(normalized) {
                const clamped = Math.max(0, Math.min(normalized, getMaxScroll()));

                if (!isRtl) {
                    return clamped;
                }

                if (rtlScrollType === 'negative') {
                    return -clamped;
                }

                if (rtlScrollType === 'reverse') {
                    return getMaxScroll() - clamped;
                }

                return clamped;
            }

            function setNormalizedScrollLeft(normalized) {
                slider.scrollLeft = toRawScrollLeft(normalized);
            }

            function smoothScrollToNormalized(normalized) {
                slider.scrollTo({
                    left: toRawScrollLeft(normalized),
                    behavior: 'smooth'
                });
            }

            function rebuildIndicators() {
                indicatorsContainer.innerHTML = '';

                cards.forEach((card, index) => {
                    const indicator = document.createElement('button');
                    indicator.type = 'button';
                    indicator.dataset.index = String(index);
                    indicator.className = 'indicator-dot h-2 rounded-full transition-all duration-300 cursor-pointer w-12 bg-gray-300';
                    indicator.setAttribute('aria-label', `Go to template ${index + 1}`);

                    indicator.addEventListener('click', function () {
                        scrollToCard(index);
                    });

                    indicatorsContainer.appendChild(indicator);
                });
            }

            function getClosestIndex() {
                const sliderRect = slider.getBoundingClientRect();
                const sliderCenter = sliderRect.left + (sliderRect.width / 2);

                let closestIndex = 0;
                let minDistance = Infinity;

                cards.forEach((card, index) => {
                    const cardRect = card.getBoundingClientRect();
                    const cardCenter = cardRect.left + (cardRect.width / 2);
                    const distance = Math.abs(cardCenter - sliderCenter);

                    if (distance < minDistance) {
                        minDistance = distance;
                        closestIndex = index;
                    }
                });

                return closestIndex;
            }

            function updateActiveIndicator() {
                const indicators = indicatorsContainer.querySelectorAll('.indicator-dot');
                const activeIndex = getClosestIndex();

                indicators.forEach((indicator, index) => {
                    if (index === activeIndex) {
                        indicator.classList.remove('w-12', 'bg-gray-300');
                        indicator.classList.add('w-32', 'bg-purple-brand');
                    } else {
                        indicator.classList.remove('w-32', 'bg-purple-brand');
                        indicator.classList.add('w-12', 'bg-gray-300');
                    }
                });
            }

            function scrollToCard(index) {
                const targetCard = cards[index];
                if (!targetCard) {
                    return;
                }

                const sliderRect = slider.getBoundingClientRect();
                const cardRect = targetCard.getBoundingClientRect();
                const delta = (cardRect.left + (cardRect.width / 2)) - (sliderRect.left + (sliderRect.width / 2));

                smoothScrollToNormalized(getNormalizedScrollLeft() + delta);
                window.setTimeout(updateActiveIndicator, 320);
            }

            function onPointerUp(event) {
                if (!isDown) {
                    return;
                }

                isDown = false;
                slider.classList.remove('cursor-grabbing');
                slider.style.scrollSnapType = '';
                slider.style.scrollBehavior = 'auto';

                if (event?.pointerId != null && slider.releasePointerCapture) {
                    try {
                        slider.releasePointerCapture(event.pointerId);
                    } catch (error) {
                    }
                }

                if (hasDragged) {
                    suppressClick = true;
                    scrollToCard(getClosestIndex());
                    window.setTimeout(function () {
                        suppressClick = false;
                    }, 120);
                }
            }

            slider.addEventListener('scroll', function () {
                if (scrollRaf !== null) {
                    return;
                }

                scrollRaf = window.requestAnimationFrame(function () {
                    scrollRaf = null;
                    updateActiveIndicator();
                });
            }, { passive: true });

            slider.addEventListener('pointerdown', function (event) {
                if (event.pointerType === 'mouse' && event.button !== 0) {
                    return;
                }

                isDown = true;
                hasDragged = false;
                slider.classList.add('cursor-grabbing');
                slider.style.scrollBehavior = 'auto';
                slider.style.scrollSnapType = 'none';

                startX = event.clientX;
                startScrollLeft = getNormalizedScrollLeft();

                if (slider.setPointerCapture) {
                    slider.setPointerCapture(event.pointerId);
                }
            });

            slider.addEventListener('pointermove', function (event) {
                if (!isDown) {
                    return;
                }

                event.preventDefault();
                const deltaX = event.clientX - startX;

                if (!hasDragged && Math.abs(deltaX) > 3) {
                    hasDragged = true;
                }

                setNormalizedScrollLeft(startScrollLeft - (deltaX * dragSensitivity));
            });

            slider.addEventListener('pointerup', onPointerUp);
            slider.addEventListener('pointercancel', onPointerUp);

            slider.addEventListener('click', function (event) {
                if (!suppressClick) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
            }, true);

            window.addEventListener('resize', updateActiveIndicator);

            rebuildIndicators();
            updateActiveIndicator();
        })();
    </script>
@endif
