@php
    $payload = is_array($data ?? null) ? $data : (is_array($content ?? null) ? $content : []);

    $sectionId = trim((string) ($payload['section_id'] ?? 'our-work'));
    $brandPrefix = trim((string) ($payload['brand_prefix'] ?? 'PAL'));
    $brandSuffix = trim((string) ($payload['brand_suffix'] ?? 'GOALS'));
    $sectionTitle = trim((string) ($payload['title'] ?? __('Our Work')));
    $sectionDescription = trim((string) ($payload['description'] ?? __('Customer opinions drive innovation, trust, and growth.')));
    $defaultButtonLabel = trim((string) ($payload['button_label'] ?? __('Visit')));

    if ($sectionId === '') {
        $sectionId = 'our-work';
    }

    if ($defaultButtonLabel === '') {
        $defaultButtonLabel = __('Visit');
    }

    $portfolioItems = collect($payload['portfolio_items'] ?? [])
        ->map(function ($item) use ($defaultButtonLabel): ?array {
            if (! is_array($item)) {
                return null;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $subtitle = trim((string) ($item['subtitle'] ?? ''));
            $image = trim((string) ($item['image'] ?? ''));
            $url = trim((string) ($item['url'] ?? '#'));
            $buttonLabel = trim((string) ($item['button_label'] ?? $defaultButtonLabel));

            if ($title === '') {
                $title = __('Project');
            }

            if ($url === '') {
                $url = '#';
            }

            if ($buttonLabel === '') {
                $buttonLabel = $defaultButtonLabel;
            }

            return [
                'title' => $title,
                'subtitle' => $subtitle,
                'image' => $image,
                'url' => $url,
                'button_label' => $buttonLabel,
                'external' => str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//'),
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="{{ $sectionId }}" data-portfolio-slider class="relative overflow-hidden bg-theme-surface font-theme-body px-4 py-16 sm:px-6 lg:px-12 lg:py-24">
    <div class="container mx-auto">
        <div class="mb-6 text-center">
            <p class="mb-4 text-base font-extrabold tracking-widest">
                <span class="text-theme-secondary">{{ $brandPrefix }}</span>
                <span class="text-theme-primary">{{ $brandSuffix }}</span>
            </p>

            @if ($sectionTitle !== '')
                <h2 class="font-theme-heading text-3xl font-extrabold uppercase leading-tight text-theme-heading md:text-[40px]">
                    {{ $sectionTitle }}
                </h2>
            @endif

            @if ($sectionDescription !== '')
                <p class="mx-auto max-w-xl text-lg leading-relaxed text-theme-body md:text-xl">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>

        <div class="relative">
            @if ($portfolioItems->count() > 1)
                <div class="pointer-events-none absolute inset-y-0 z-20 hidden items-center justify-between md:flex md:w-full">
                    <button
                        type="button"
                        data-portfolio-slider-prev
                        class="pointer-events-auto inline-flex h-12 w-12 items-center justify-center rounded-full border border-theme-border bg-theme-surface text-theme-heading shadow-theme transition hover:bg-theme-muted"
                        aria-label="{{ __('Previous project') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        data-portfolio-slider-next
                        class="pointer-events-auto inline-flex h-12 w-12 items-center justify-center rounded-full border border-theme-border bg-theme-surface text-theme-heading shadow-theme transition hover:bg-theme-muted"
                        aria-label="{{ __('Next project') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                        </svg>
                    </button>
                </div>
            @endif

            <div
                id="{{ $sectionId }}-slider"
                data-portfolio-slider-track
                class="scrollbar-hide flex cursor-grab snap-x snap-mandatory select-none items-stretch gap-6 overflow-x-auto pb-12 transition-all duration-300 md:px-0"
                aria-label="{{ $sectionTitle !== '' ? $sectionTitle : __('Portfolio') }}"
            >
                @forelse ($portfolioItems as $item)
                    <article data-portfolio-slide class="group w-[80vw] shrink-0 snap-start rounded-theme-xl border border-theme-border bg-theme-surface p-4 shadow-theme transition-all duration-300 hover:-translate-y-1 md:w-[350px] lg:w-[400px]">
                        <div class="relative h-56 w-full overflow-hidden rounded-theme-xl shadow-theme lg:h-64">
                            @if ($item['image'] !== '')
                                <img
                                    src="{{ $item['image'] }}"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    alt="{{ $item['title'] }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-theme-surface px-6 text-center text-theme-body">
                                    {{ $item['title'] }}
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between gap-4 px-2 pb-2 pt-5">
                            <div class="min-w-0 text-left ltr:text-left rtl:text-right">
                                <h3 class="mb-1 truncate text-xl font-bold text-theme-heading transition-colors group-hover:text-theme-primary md:text-2xl">
                                    {{ $item['title'] }}
                                </h3>

                                @if ($item['subtitle'] !== '')
                                    <p class="truncate text-sm font-medium text-theme-body md:text-base">
                                        {{ $item['subtitle'] }}
                                    </p>
                                @endif
                            </div>

                            <a
                                href="{{ $item['url'] }}"
                                @if ($item['external']) target="_blank" rel="noopener" @endif
                                class="btn-theme-primary shrink-0 whitespace-nowrap px-6 py-3 text-base"
                            >
                                {{ $item['button_label'] }}
                            </a>
                        </div>
                    </article>
                @empty
                    <x-front.empty-section-state
                        title="{{ __('No portfolio items yet') }}"
                        description="{{ __('Add portfolio items from the dashboard to populate this section.') }}"
                    />
                    
                @endforelse
            </div>
        </div>

        @if ($portfolioItems->count() > 1)
            <div class="mt-4 flex justify-center gap-2" data-portfolio-slider-indicators>
                @foreach ($portfolioItems as $index => $item)
                    <button
                        type="button"
                        data-portfolio-slider-dot
                        data-slide-index="{{ $index }}"
                        class="{{ $index === 0 ? 'w-8 opacity-100 shadow-theme' : 'w-2.5 opacity-60' }} h-2.5 rounded-full border border-theme-border bg-theme-muted transition-all"
                        aria-label="{{ __('Go to project :number', ['number' => $index + 1]) }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</section>

@if ($portfolioItems->count() > 1)
    <script>
        (function() {
            function initializePortfolioSlider(root) {
                if (!root || root.dataset.portfolioSliderBound === '1') {
                    return;
                }

                const track = root.querySelector('[data-portfolio-slider-track]');
                const slides = Array.from(root.querySelectorAll('[data-portfolio-slide]'));
                const dots = Array.from(root.querySelectorAll('[data-portfolio-slider-dot]'));
                const prevButton = root.querySelector('[data-portfolio-slider-prev]');
                const nextButton = root.querySelector('[data-portfolio-slider-next]');

                if (!track || slides.length < 2) {
                    return;
                }

                root.dataset.portfolioSliderBound = '1';

                let activeIndex = 0;
                let autoTimer = null;
                let resumeTimer = null;
                let scrollRaf = null;
                let isPointerInside = false;

                function isRtl() {
                    return window.getComputedStyle(track).direction === 'rtl';
                }

                function clampIndex(index) {
                    if (index < 0) {
                        return slides.length - 1;
                    }

                    if (index >= slides.length) {
                        return 0;
                    }

                    return index;
                }

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

                function updateIndicators(index) {
                    dots.forEach(function(dot, dotIndex) {
                        const isActive = dotIndex === index;

                        dot.classList.toggle('w-8', isActive);
                        dot.classList.toggle('w-2.5', !isActive);
                        dot.classList.toggle('opacity-100', isActive);
                        dot.classList.toggle('opacity-60', !isActive);
                        dot.classList.toggle('shadow-theme', isActive);
                        dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                    });
                }

                function setActiveIndex(index) {
                    activeIndex = clampIndex(index);
                    updateIndicators(activeIndex);
                }

                function goToSlide(index) {
                    const targetIndex = clampIndex(index);
                    const targetSlide = slides[targetIndex];

                    if (!targetSlide) {
                        return;
                    }

                    setActiveIndex(targetIndex);

                    targetSlide.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'start',
                    });
                }

                function step(direction) {
                    const delta = direction === 'previous' ? -1 : 1;

                    goToSlide(activeIndex + delta);
                }

                function startAutoScroll() {
                    stopAutoScroll();
                    autoTimer = window.setInterval(function() {
                        step('next');
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

                function handleScroll() {
                    if (scrollRaf) {
                        return;
                    }

                    scrollRaf = window.requestAnimationFrame(function() {
                        scrollRaf = null;
                        setActiveIndex(nearestSlideIndex());
                    });
                }

                if (prevButton) {
                    prevButton.addEventListener('click', function() {
                        pauseAutoScroll();
                        step(isRtl() ? 'next' : 'previous');
                        resumeAutoScroll(3500);
                    });
                }

                if (nextButton) {
                    nextButton.addEventListener('click', function() {
                        pauseAutoScroll();
                        step(isRtl() ? 'previous' : 'next');
                        resumeAutoScroll(3500);
                    });
                }

                dots.forEach(function(dot) {
                    dot.addEventListener('click', function() {
                        const targetIndex = Number.parseInt(dot.dataset.slideIndex || '0', 10);

                        pauseAutoScroll();
                        goToSlide(Number.isNaN(targetIndex) ? 0 : targetIndex);
                        resumeAutoScroll(3500);
                    });
                });

                track.addEventListener('scroll', handleScroll, {
                    passive: true,
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
                    passive: true,
                });
                root.addEventListener('touchend', function() {
                    pauseAutoScroll();
                }, {
                    passive: true,
                });
                root.addEventListener('touchcancel', function() {
                    pauseAutoScroll();
                }, {
                    passive: true,
                });

                setActiveIndex(nearestSlideIndex());
            }

            function initializePortfolioSliders() {
                document.querySelectorAll('[data-portfolio-slider]').forEach(initializePortfolioSlider);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializePortfolioSliders, {
                    once: true,
                });
            } else {
                initializePortfolioSliders();
            }
        })();
    </script>
@endif
