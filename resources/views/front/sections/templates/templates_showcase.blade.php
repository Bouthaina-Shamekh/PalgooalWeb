@php
    $payload = is_array($data ?? null) ? $data : (is_array($content ?? null) ? $content : []);

    $sectionId = trim((string) ($payload['section_id'] ?? 'templates-showcase'));

    $brandPrefix = trim((string) ($payload['brand_prefix'] ?? 'PAL'));
    $brandSuffix = trim((string) ($payload['brand_suffix'] ?? 'GOALS'));

    $sectionTitle = trim((string) ($payload['title'] ?? 'Templates'));
    $sectionDescription = trim((string) ($payload['description'] ?? ''));

    $buyLabel = trim((string) ($payload['buy_label'] ?? 'Buy Now'));
    $previewLabel = trim((string) ($payload['preview_label'] ?? 'Live Preview'));

    $templates = collect($payload['templates'] ?? [])
        ->map(function ($template): ?array {
            if (!$template) {
                return null;
            }

            $translation = $template->translation(app()->getLocale()) ?? $template->translations->first();

            $slug = trim((string) ($translation?->slug ?? ''));
            $name = trim((string) ($translation?->name ?? 'Template'));
            $description = trim(strip_tags((string) ($translation?->description ?? '')));

            $image = $template->image ? asset('storage/' . ltrim($template->image, '/')) : null;

            return [
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'buy_url' => $slug ? route('template.show.redesign', $slug, false) : '#',
                'preview_url' => $slug ? route('template.preview', $slug, false) : '#',
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="{{ $sectionId }}" class="bg-theme-muted font-theme-body py-20 overflow-hidden">

    <div class="container mx-auto">

        <!-- Header -->
        <div class="text-center mb-16 max-w-4xl mx-auto">
            <p class="mb-4 text-base font-extrabold tracking-widest">
                <span class="text-theme-secondary">{{ $brandPrefix }}</span>
                <span class="text-theme-primary">{{ $brandSuffix }}</span>
            </p>

            <h2 class="font-theme-heading text-theme-heading text-3xl md:text-4xl font-extrabold">
                {{ $sectionTitle }}
            </h2>

            @if ($sectionDescription !== '')
                <p class="mt-4 text-theme-body text-lg">
                    {{ $sectionDescription }}
                </p>
            @endif
        </div>


        <!-- Slider -->
        <div class="overflow-hidden">
            <div data-templates-slider
                class="scrollbar-hide flex select-none gap-5 overflow-x-auto scroll-smooth snap-x snap-mandatory px-4 pb-8 pt-2 md:gap-6 lg:px-0">

                @forelse ($templates as $template)
                    <div data-template-slide class="snap-start shrink-0 basis-[82vw] md:basis-[42vw] lg:basis-[31.6%]">

                        <div
                            class="h-full bg-theme-surface rounded-theme-xl shadow-theme overflow-hidden group transition-all duration-300 hover:-translate-y-2">

                            <!-- Image -->
                            <div class="relative overflow-hidden">
                                @if ($template['image'])
                                    <img src="{{ $template['image'] }}"
                                        class="w-full h-56 md:h-64 object-cover object-top transition-transform duration-500 group-hover:scale-105"
                                        alt="{{ $template['name'] }}" loading="lazy">
                                @else
                                    <div
                                        class="flex h-56 md:h-64 items-center justify-center bg-theme-muted text-theme-body">
                                        {{ $template['name'] }}
                                    </div>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="p-6 flex flex-col min-h-[210px]">

                                <h3
                                    class="font-theme-heading text-theme-heading text-xl md:text-2xl font-bold truncate">
                                    {{ $template['name'] }}
                                </h3>

                                @if ($template['description'])
                                    <p class="text-theme-body mt-3 text-sm md:text-base line-clamp-2 min-h-[3rem]">
                                        {{ $template['description'] }}
                                    </p>
                                @endif

                                <!-- Buttons -->
                                <div class="mt-auto pt-6 grid grid-cols-2 gap-3">
                                    <a href="{{ $template['preview_url'] }}"
                                        class="inline-flex items-center justify-center rounded-theme-md border border-theme-border text-theme-heading bg-theme-surface hover:bg-theme-muted px-4 py-3 text-sm md:text-base transition">
                                        {{ $previewLabel }}
                                    </a>

                                    <a href="{{ $template['buy_url'] }}"
                                        class="btn-theme-primary inline-flex items-center justify-center px-4 py-3 text-sm md:text-base">
                                        {{ $buyLabel }}
                                    </a>
                                </div>

                            </div>
                        </div>

                    </div>
                @empty
                    <x-front.empty-section-state
                        title="{{ __('No templates yet') }}"
                        description="{{ __('Published templates will appear here once they are available.') }}"
                    />
                @endforelse

            </div>
        </div>

    </div>

</section>
@if ($templates->count() > 1)
    <script>
        (function() {
            function getRtlScrollType() {
                if (window.__palgoalsRtlScrollType) {
                    return window.__palgoalsRtlScrollType;
                }

                const outer = document.createElement('div');
                const inner = document.createElement('div');

                outer.dir = 'rtl';
                outer.style.cssText =
                    'position:absolute;top:-9999px;width:4px;height:1px;overflow:scroll;visibility:hidden;';
                inner.style.width = '8px';
                outer.appendChild(inner);
                document.body.appendChild(outer);

                if (outer.scrollLeft > 0) {
                    window.__palgoalsRtlScrollType = 'default';
                } else {
                    outer.scrollLeft = 1;
                    window.__palgoalsRtlScrollType = outer.scrollLeft === 0 ? 'negative' : 'reverse';
                }

                document.body.removeChild(outer);

                return window.__palgoalsRtlScrollType;
            }

            function getNormalizedScrollLeft(slider) {
                const maxScroll = Math.max(0, slider.scrollWidth - slider.clientWidth);

                if (window.getComputedStyle(slider).direction !== 'rtl') {
                    return slider.scrollLeft;
                }

                const rtlType = getRtlScrollType();

                if (rtlType === 'negative') {
                    return -slider.scrollLeft;
                }

                if (rtlType === 'reverse') {
                    return maxScroll - slider.scrollLeft;
                }

                return slider.scrollLeft;
            }

            function toNativeScrollLeft(slider, normalizedLeft) {
                const maxScroll = Math.max(0, slider.scrollWidth - slider.clientWidth);
                const nextLeft = Math.max(0, Math.min(normalizedLeft, maxScroll));

                if (window.getComputedStyle(slider).direction !== 'rtl') {
                    return nextLeft;
                }

                const rtlType = getRtlScrollType();

                if (rtlType === 'negative') {
                    return -nextLeft;
                }

                if (rtlType === 'reverse') {
                    return maxScroll - nextLeft;
                }

                return nextLeft;
            }

            function getSlidePositions(slider, slides) {
                const maxScroll = Math.max(0, slider.scrollWidth - slider.clientWidth);
                const firstOffset = slides[0]?.offsetLeft || 0;
                const positions = slides
                    .map(function(slide) {
                        return Math.max(0, Math.min(slide.offsetLeft - firstOffset, maxScroll));
                    })
                    .filter(function(position, index, allPositions) {
                        return index === 0 || Math.abs(position - allPositions[index - 1]) > 4;
                    });

                if (positions.length === 0 || positions[0] !== 0) {
                    positions.unshift(0);
                }

                if (maxScroll > 0 && Math.abs(positions[positions.length - 1] - maxScroll) > 4) {
                    positions.push(maxScroll);
                }

                return positions;
            }

            function bindTemplateSlider(slider) {
                if (!slider || slider.dataset.templatesSliderBound === '1') {
                    return;
                }

                const slides = Array.from(slider.querySelectorAll('[data-template-slide]'));

                if (slides.length < 2) {
                    return;
                }

                slider.dataset.templatesSliderBound = '1';

                let autoScrollTimer = null;
                let resumeTimer = null;
                let currentIndex = 0;
                let snapRestoreTimer = null;

                function stopAutoScroll() {
                    if (autoScrollTimer) {
                        window.clearInterval(autoScrollTimer);
                        autoScrollTimer = null;
                    }
                }

                function startAutoScroll() {
                    stopAutoScroll();
                    autoScrollTimer = window.setInterval(scrollStep, 3500);
                }

                function pauseAutoScroll() {
                    window.clearTimeout(resumeTimer);
                    stopAutoScroll();
                }

                function resumeAutoScroll(delay) {
                    window.clearTimeout(resumeTimer);
                    resumeTimer = window.setTimeout(startAutoScroll, delay || 0);
                }

                function scrollStep() {
                    const positions = getSlidePositions(slider, slides);

                    if (positions.length < 2) {
                        return;
                    }

                    const currentLeft = getNormalizedScrollLeft(slider);
                    const nearestIndex = positions.reduce(function(closestIndex, position, index) {
                        return Math.abs(position - currentLeft) < Math.abs(positions[closestIndex] - currentLeft)
                            ? index
                            : closestIndex;
                    }, 0);
                    currentIndex = nearestIndex >= positions.length - 1 ? 0 : nearestIndex + 1;

                    window.clearTimeout(snapRestoreTimer);
                    slider.style.scrollSnapType = 'none';

                    slider.scrollTo({
                        left: toNativeScrollLeft(slider, positions[currentIndex]),
                        behavior: 'smooth',
                    });

                    snapRestoreTimer = window.setTimeout(function() {
                        slider.style.scrollSnapType = '';
                    }, 450);
                }

                slider.addEventListener('mouseenter', pauseAutoScroll);
                slider.addEventListener('mouseleave', function() {
                    resumeAutoScroll(0);
                });
                slider.addEventListener('touchstart', pauseAutoScroll, {
                    passive: true,
                });
                slider.addEventListener('touchend', function() {
                    resumeAutoScroll(1500);
                }, {
                    passive: true,
                });
                slider.addEventListener('touchcancel', function() {
                    resumeAutoScroll(1500);
                }, {
                    passive: true,
                });

                startAutoScroll();
            }

            function initializeTemplateSliders() {
                document.querySelectorAll('[data-templates-slider]').forEach(bindTemplateSlider);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeTemplateSliders, {
                    once: true,
                });
            } else {
                initializeTemplateSliders();
            }
        })();
    </script>
@endif
