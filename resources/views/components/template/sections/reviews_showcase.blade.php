@php
    $data = $data ?? [];

    $brandPrefix = $data['brand_prefix'] ?? 'PAL';
    $brandSuffix = $data['brand_suffix'] ?? 'GOALS';
    $sectionTitle = $data['title'] ?? '';
    $sectionDescription = $data['description'] ?? '';

    $resolveMediaUrl = static function ($value): ?string {
        if (is_numeric($value)) {
            $media = \App\Models\Media::find((int) $value);

            return $media?->url ?? ($media?->file_url ?? null);
        }

        if (is_string($value) && $value !== '') {
            return \Illuminate\Support\Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])
                ? $value
                : asset($value);
        }

        return null;
    };

    $dbReviews = collect($data['testimonials'] ?? [])
        ->map(function ($testimonial): ?array {
            if (! $testimonial) {
                return null;
            }

            $translation = $testimonial->translations->firstWhere('locale', app()->getLocale())
                ?? $testimonial->translations->first();

            if (! $translation) {
                return null;
            }

            return [
                'name' => $translation->name ?: __('Anonymous'),
                'text' => $translation->feedback ?? '',
                'rating' => max(1, min(5, (int) ($testimonial->star ?? 5))),
                'avatar_url' => $testimonial->image?->url,
            ];
        })
        ->filter()
        ->values();

    $fallbackReviews = collect(is_array($data['reviews'] ?? null) ? $data['reviews'] : [])
        ->map(function ($review) use ($resolveMediaUrl): ?array {
            if (! is_array($review)) {
                return null;
            }

            $name = trim((string) ($review['name'] ?? ''));
            $text = trim((string) ($review['text'] ?? ''));
            if ($name === '' && $text === '') {
                return null;
            }

            return [
                'name' => $name !== '' ? $name : __('Anonymous'),
                'text' => $text,
                'rating' => max(1, min(5, (int) ($review['rating'] ?? 5))),
                'avatar_url' => $resolveMediaUrl($review['avatar'] ?? null),
            ];
        })
        ->filter()
        ->values();

    $reviews = $dbReviews->isNotEmpty() ? $dbReviews : $fallbackReviews;
    $reviewsInstanceKey = 'reviews-showcase-' . ($section->id ?? uniqid());
    $reviewsSliderId = $reviewsInstanceKey . '-slider';
    $reviewsIndicatorsId = $reviewsInstanceKey . '-indicators';
@endphp

{{-- Reviews section --}}
<section id="reviews" class="py-16 lg:py-24 bg-gray-50 overflow-hidden relative px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        {{-- Header --}}
        <div class="mb-12 text-center">
            <p class="text-lg md:text-xl">
                <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
            </p>

            @if ($sectionTitle)
                <h2 class="text-3xl font-extrabold uppercase leading-tight text-purple-brand md:text-[40px]">
                    {{ $sectionTitle }}
                </h2>
            @endif

            @if ($sectionDescription)
                <p class="mx-auto max-w-xl text-lg leading-relaxed text-gray-dark md:text-xl">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>

        {{-- Slider container --}}
        <div id="{{ $reviewsSliderId }}" class="scrollbar-hide flex cursor-grab snap-x snap-mandatory select-none items-stretch gap-6 overflow-x-auto pb-12">
            @forelse ($reviews as $review)
                {{-- Review card --}}
                <div class="group w-[85vw] shrink-0 snap-start rounded-[32px] border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-lg md:w-[500px]">
                    <div class="mb-6 flex items-center gap-4">
                        <div class="relative h-24 w-24 flex-shrink-0 overflow-hidden rounded-full border-2 border-purple-brand/10">
                            <div class="absolute inset-0 z-10"></div>
                            @if ($review['avatar_url'])
                                <img src="{{ $review['avatar_url'] }}" class="h-full w-full object-cover" alt="{{ $review['name'] }}" loading="lazy">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-slate-100 text-2xl font-semibold text-slate-500">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($review['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="ltr:text-left rtl:text-right">
                            <h3 class="text-lg font-bold text-purple-brand md:text-xl">{{ $review['name'] }}</h3>
                            <div class="mt-1 flex items-center gap-1 ltr:flex-row rtl:flex-row-reverse">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-6 w-6" viewBox="0 0 27 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path
                                            d="M13.2738 20.2691L21.477 25.2202L19.3001 15.8887L26.5476 9.61022L17.0037 8.80052L13.2738 0L9.54385 8.80052L0 9.61022L7.24749 15.8887L5.07059 25.2202L13.2738 20.2691Z"
                                            fill="{{ $i <= $review['rating'] ? '#FFBC00' : '#A8A8A8' }}"
                                        />
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>

                    @if ($review['text'])
                        <p class="text-base leading-relaxed text-gray-dark md:text-xl">
                            {{ $review['text'] }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
                    {{ __('No approved testimonials available yet.') }}
                </div>
            @endforelse
        </div>

        @if ($reviews->count() > 1)
            {{-- Slider indicators --}}
            <div class="mt-4 flex justify-center gap-2" id="{{ $reviewsIndicatorsId }}" dir="ltr">
                @foreach ($reviews as $index => $review)
                    <button
                        type="button"
                        data-review-indicator
                        data-review-index="{{ $index }}"
                        class="h-2.5 rounded-full transition-all duration-300 {{ $index === 0 ? 'w-8 bg-purple-brand' : 'w-2.5 bg-slate-300' }}"
                        aria-label="{{ __('Go to review :number', ['number' => $index + 1]) }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</section>

@if ($reviews->count() > 1)
    <script>
        (function () {
            const slider = document.querySelector(@json('#' . $reviewsSliderId));
            const indicatorsContainer = document.querySelector(@json('#' . $reviewsIndicatorsId));

            if (!slider || !indicatorsContainer || slider.dataset.reviewsBound === '1') {
                return;
            }

            slider.dataset.reviewsBound = '1';

            const cards = Array.from(slider.children);
            const isRtl = document.documentElement.dir === 'rtl'
                || window.getComputedStyle(slider).direction === 'rtl';
            const dragSensitivity = 1.05;
            const rtlScrollType = isRtl ? detectRtlScrollType() : 'default';

            let scrollRaf = null;
            let isDown = false;
            let startX = 0;
            let startScrollLeft = 0;
            let hasDragged = false;

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

                const max = getMaxScroll();

                if (rtlScrollType === 'negative') {
                    return -raw;
                }

                if (rtlScrollType === 'reverse') {
                    return max - raw;
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

            function toDisplayIndex(cardIndex) {
                return isRtl ? (cards.length - 1 - cardIndex) : cardIndex;
            }

            function toCardIndex(displayIndex) {
                return isRtl ? (cards.length - 1 - displayIndex) : displayIndex;
            }

            function rebuildIndicators() {
                indicatorsContainer.innerHTML = '';

                cards.forEach((card, displayIndex) => {
                    const cardIndex = toCardIndex(displayIndex);
                    const indicator = document.createElement('button');
                    indicator.type = 'button';
                    indicator.dataset.index = String(cardIndex);
                    indicator.dataset.displayIndex = String(displayIndex);
                    indicator.setAttribute('aria-label', `Go to review ${displayIndex + 1}`);
                    indicator.className = 'indicator-dot h-2 rounded-full transition-all duration-300 cursor-pointer w-12 bg-gray-300';

                    indicator.addEventListener('click', function () {
                        scrollToCard(cardIndex);
                    });

                    indicatorsContainer.appendChild(indicator);
                });
            }

            function getClosestIndex() {
                if (!cards.length) {
                    return 0;
                }

                const sliderRect = slider.getBoundingClientRect();
                const edge = sliderRect.left;

                let closestIndex = 0;
                let minDistance = Infinity;

                cards.forEach((card, index) => {
                    const cardRect = card.getBoundingClientRect();
                    const distance = Math.abs(cardRect.left - edge);

                    if (distance < minDistance) {
                        minDistance = distance;
                        closestIndex = index;
                    }
                });

                return closestIndex;
            }

            function updateActiveIndicator() {
                const indicators = indicatorsContainer.querySelectorAll('.indicator-dot');
                const closestIndex = getClosestIndex();
                const activeDisplayIndex = toDisplayIndex(closestIndex);

                indicators.forEach((indicator, index) => {
                    if (index === activeDisplayIndex) {
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
                const delta = cardRect.left - sliderRect.left;

                smoothScrollToNormalized(getNormalizedScrollLeft() + delta);

                window.setTimeout(updateActiveIndicator, 300);
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
                    scrollToCard(getClosestIndex());
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

            slider.addEventListener('mouseleave', onPointerUp);
            slider.addEventListener('pointerup', onPointerUp);
            slider.addEventListener('pointercancel', onPointerUp);
            window.addEventListener('resize', updateActiveIndicator);

            rebuildIndicators();
            updateActiveIndicator();
        })();
    </script>
@endif
