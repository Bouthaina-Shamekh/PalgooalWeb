@php
    $payload = is_array($data ?? null) ? $data : (is_array($content ?? null) ? $content : []);

    $sectionId = trim((string) ($payload['section_id'] ?? 'reviews'));
    $brandPrefix = trim((string) ($payload['brand_prefix'] ?? 'PAL'));
    $brandSuffix = trim((string) ($payload['brand_suffix'] ?? 'GOALS'));
    $sectionTitle = trim((string) ($payload['title'] ?? __('Reviews')));
    $sectionDescription = trim((string) ($payload['description'] ?? __('Customer opinions drive innovation, trust, and growth.')));

    if ($sectionId === '') {
        $sectionId = 'reviews';
    }

    $reviewsItems = collect($payload['reviews_items'] ?? [])
        ->map(function ($item): ?array {
            if (! is_array($item)) {
                return null;
            }

            $name = trim((string) ($item['name'] ?? ''));
            $position = trim((string) ($item['position'] ?? ''));
            $image = trim((string) ($item['image'] ?? ''));
            $text = trim((string) ($item['text'] ?? ''));
            $rating = max(1, min(5, (int) ($item['rating'] ?? 5)));

            if ($name === '' && $text === '') {
                return null;
            }

            return [
                'name' => $name !== '' ? $name : __('Anonymous'),
                'position' => $position,
                'image' => $image,
                'rating' => $rating,
                'text' => $text,
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="{{ $sectionId }}" data-reviews-slider-root class="overflow-hidden bg-theme-muted px-4 py-16 font-theme-body sm:px-6 lg:px-12 lg:py-24">
    <div class="container mx-auto">
        <div class="mb-12 text-center">
            @if ($brandPrefix !== '' || $brandSuffix !== '')
                <p class="mb-4 text-base font-extrabold tracking-widest">
                    @if ($brandPrefix !== '')
                        <span class="text-theme-secondary">{{ $brandPrefix }}</span>
                    @endif

                    @if ($brandSuffix !== '')
                        <span class="text-theme-primary">{{ $brandSuffix }}</span>
                    @endif
                </p>
            @endif

            @if ($sectionTitle !== '')
                <h2 class="font-theme-heading text-3xl font-extrabold uppercase leading-tight text-theme-heading md:text-[40px]">
                    {{ $sectionTitle }}
                </h2>
            @endif

            @if ($sectionDescription !== '')
                <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-theme-body md:text-xl">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>

        <div
            data-reviews-slider-track
            class="scrollbar-hide flex cursor-grab snap-x snap-mandatory select-none items-stretch gap-6 overflow-x-auto pb-12"
            aria-label="{{ $sectionTitle !== '' ? $sectionTitle : __('Reviews') }}"
        >
            @forelse ($reviewsItems as $item)
                <article data-review-slide class="w-[85vw] shrink-0 snap-start rounded-theme-xl border border-theme-border bg-theme-surface p-6 shadow-theme md:w-[500px]">
                    <div class="mb-6 flex items-center gap-4">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full border border-theme-border bg-theme-muted">
                            @if ($item['image'] !== '')
                                <img
                                    src="{{ $item['image'] }}"
                                    class="h-full w-full object-cover"
                                    alt="{{ $item['name'] }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center text-2xl font-bold text-theme-heading">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 text-left rtl:text-right">
                            <h3 class="text-lg font-bold text-theme-heading md:text-xl">
                                {{ $item['name'] }}
                            </h3>

                            @if ($item['position'] !== '')
                                <p class="mt-1 text-sm font-medium text-theme-secondary">
                                    {{ $item['position'] }}
                                </p>
                            @endif

                            <div class="mt-2 flex items-center gap-1 ltr:flex-row rtl:flex-row-reverse" aria-label="{{ __('Rating :rating out of 5', ['rating' => $item['rating']]) }}">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-5 w-5 {{ $i <= $item['rating'] ? 'text-theme-primary' : 'text-theme-border' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M10 1.6l2.5 5.1 5.6.8-4 3.9.9 5.5-5-2.6-5 2.6.9-5.5-4-3.9 5.6-.8L10 1.6z" />
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>

                    @if ($item['text'] !== '')
                        <p class="text-base leading-relaxed text-theme-body md:text-lg">
                            {{ $item['text'] }}
                        </p>
                    @endif
                </article>
            @empty
                <x-front.empty-section-state
                    title="{{ __('No reviews yet') }}"
                    description="{{ __('Approved testimonials will appear here once they are available.') }}"
                />
            @endforelse
        </div>

        @if ($reviewsItems->count() > 1)
            <div class="mt-4 flex justify-center gap-2" data-reviews-slider-dots dir="ltr">
                @foreach ($reviewsItems as $index => $item)
                    <button
                        type="button"
                        data-reviews-slider-dot
                        data-slide-index="{{ $index }}"
                        class="{{ $index === 0 ? 'w-8 bg-theme-primary opacity-100 shadow-theme' : 'w-2.5 bg-theme-border opacity-60' }} h-2.5 rounded-full transition-all"
                        aria-label="{{ __('Go to review :number', ['number' => $index + 1]) }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</section>

@if ($reviewsItems->count() > 1)
    <script>
        (function() {
            function initializeReviewsSlider(root) {
                if (!root || root.dataset.reviewsSliderBound === '1') {
                    return;
                }

                const track = root.querySelector('[data-reviews-slider-track]');
                const slides = Array.from(root.querySelectorAll('[data-review-slide]'));
                const dots = Array.from(root.querySelectorAll('[data-reviews-slider-dot]'));

                if (!track || slides.length < 2) {
                    return;
                }

                root.dataset.reviewsSliderBound = '1';

                let activeIndex = 0;
                let autoTimer = null;
                let resumeTimer = null;
                let scrollRaf = null;
                let isPointerInside = false;

                function nearestSlideIndex() {
                    const trackRect = track.getBoundingClientRect();
                    const trackCenter = trackRect.left + (trackRect.width / 2);
                    let nearestIndex = 0;
                    let nearestDistance = Infinity;

                    slides.forEach(function(slide, index) {
                        const slideRect = slide.getBoundingClientRect();
                        const slideCenter = slideRect.left + (slideRect.width / 2);
                        const distance = Math.abs(slideCenter - trackCenter);

                        if (distance < nearestDistance) {
                            nearestDistance = distance;
                            nearestIndex = index;
                        }
                    });

                    return nearestIndex;
                }

                function updateDots(index) {
                    dots.forEach(function(dot, dotIndex) {
                        const isActive = dotIndex === index;

                        dot.classList.toggle('w-8', isActive);
                        dot.classList.toggle('w-2.5', !isActive);
                        dot.classList.toggle('bg-theme-primary', isActive);
                        dot.classList.toggle('bg-theme-border', !isActive);
                        dot.classList.toggle('opacity-100', isActive);
                        dot.classList.toggle('opacity-60', !isActive);
                        dot.classList.toggle('shadow-theme', isActive);
                        dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                    });
                }

                function setActiveIndex(index) {
                    activeIndex = Math.max(0, Math.min(index, slides.length - 1));
                    updateDots(activeIndex);
                }

                function goToSlide(index) {
                    const nextIndex = index < 0 ? slides.length - 1 : (index >= slides.length ? 0 : index);
                    const target = slides[nextIndex];

                    if (!target) {
                        return;
                    }

                    setActiveIndex(nextIndex);
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'start'
                    });
                }

                function startAutoScroll() {
                    stopAutoScroll();
                    autoTimer = window.setInterval(function() {
                        goToSlide(activeIndex + 1);
                    }, 3500);
                }

                function stopAutoScroll() {
                    if (autoTimer) {
                        window.clearInterval(autoTimer);
                        autoTimer = null;
                    }
                }

                function pauseAutoScroll() {
                    window.clearTimeout(resumeTimer);
                    stopAutoScroll();
                }

                function resumeAutoScroll(delay) {
                    if (!isPointerInside) {
                        return;
                    }

                    window.clearTimeout(resumeTimer);
                    resumeTimer = window.setTimeout(startAutoScroll, delay || 0);
                }

                dots.forEach(function(dot) {
                    dot.addEventListener('click', function() {
                        const targetIndex = Number.parseInt(dot.dataset.slideIndex || '0', 10);

                        pauseAutoScroll();
                        goToSlide(Number.isNaN(targetIndex) ? 0 : targetIndex);
                        resumeAutoScroll(3500);
                    });
                });

                track.addEventListener('scroll', function() {
                    if (scrollRaf) {
                        return;
                    }

                    scrollRaf = window.requestAnimationFrame(function() {
                        scrollRaf = null;
                        setActiveIndex(nearestSlideIndex());
                    });
                }, {
                    passive: true
                });

                root.addEventListener('mouseenter', function() {
                    isPointerInside = true;
                    resumeAutoScroll(0);
                });
                root.addEventListener('mouseleave', function() {
                    isPointerInside = false;
                    pauseAutoScroll();
                });
                root.addEventListener('touchstart', pauseAutoScroll, {
                    passive: true
                });
                root.addEventListener('touchend', function() {
                    pauseAutoScroll();
                }, {
                    passive: true
                });
                root.addEventListener('touchcancel', function() {
                    pauseAutoScroll();
                }, {
                    passive: true
                });

                setActiveIndex(nearestSlideIndex());
            }

            function initializeReviewsSliders() {
                document.querySelectorAll('[data-reviews-slider-root]').forEach(initializeReviewsSlider);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeReviewsSliders, {
                    once: true
                });
            } else {
                initializeReviewsSliders();
            }
        })();
    </script>
@endif
