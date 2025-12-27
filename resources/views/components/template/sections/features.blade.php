{{-- resources/views/components/template/sections/features.blade.php --}}
@props(['data' => []])

@php
    /**
     * Features section
     * - $data['title']      : Section heading (string)
     * - $data['subtitle']   : Section subheading / description (string)
     * - $data['features']   : Array of features:
     *      [
     *          [
     *              'icon'        => '<svg>...</svg>', // inline SVG icon (optional)
     *              'title'       => 'Feature title',
     *              'description' => 'Short description...',
     *          ],
     *          ...
     *      ]
     * - $data['show_illustration'] : bool - toggle illustration column (default: true)
     * - $data['illustration']      : custom path for illustration image (optional)
     */

    $title            = $data['title']    ?? __('عنوان غير متوفر');
    $subtitle         = $data['subtitle'] ?? '';
    $features         = $data['features'] ?? [];
    $showIllustration = array_key_exists('show_illustration', $data)
        ? (bool) $data['show_illustration']
        : true;

    // Illustration image (fallback to default Palgoals illustration)
    $illustrationPath = $data['illustration'] ?? 'assets/tamplate/images/Fu.svg';
@endphp

<section
    class="py-20 sm:py-24 lg:py-28 px-4 sm:px-6 lg:px-8 bg-background"
    dir="auto"
    aria-labelledby="features-heading"
    data-section-type="features"
>
    <div class="container-xx">
        {{-- ===========================
             SECTION HEADING
             ============================ --}}
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-14 lg:mb-16">
            <h2 id="features-heading"
                class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-primary tracking-tight mb-4">
                {{ $title }}
            </h2>

            @if ($subtitle)
                <p class="text-tertiary text-sm sm:text-base leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        {{-- ===========================
             MAIN GRID: ILLUSTRATION + FEATURES
             ============================ --}}
        <div class="grid gap-12 lg:gap-16 lg:grid-cols-5 items-center">
            {{-- Illustration column (optional) --}}
            @if ($showIllustration)
                <div class="lg:col-span-2 flex justify-center">
                    <img
                        src="{{ asset($illustrationPath) }}"
                        alt="مميزات المنصة"
                        class="max-w-[260px] sm:max-w-sm lg:max-w-[420px] w-full h-auto object-contain mx-auto
                               animate-fade-in-up
                               transition-transform duration-500 ease-out
                               hover:scale-105"
                        loading="lazy"
                    >
                </div>
            @endif

            {{-- Features list column --}}
            @php
                // If there is no illustration, take the full width
                $featuresColSpan = $showIllustration ? 'lg:col-span-3' : 'lg:col-span-5';
            @endphp

            <div class="{{ $featuresColSpan }}">
                @if (empty($features))
                    {{-- Graceful fallback if no features are provided --}}
                    <p class="text-sm text-gray-500 text-center lg:text-start">
                        {{ __('لم يتم إعداد مميزات لهذا القسم بعد.') }}
                    </p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8">
                        @foreach ($features as $feature)
                            @php
                                $featureTitle       = $feature['title'] ?? __('عنوان');
                                $featureDescription = $feature['description'] ?? __('وصف مختصر');
                                $featureIcon        = $feature['icon'] ?? null;
                            @endphp

                            <div
                                class="group rounded-2xl bg-white/90 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700
                                       p-5 sm:p-6 shadow-[0_10px_30px_rgba(15,23,42,0.06)]
                                       hover:shadow-[0_18px_40px_rgba(15,23,42,0.14)]
                                       transition-all duration-200">
                                <dt class="flex flex-col items-center sm:items-start gap-4">
                                    {{-- Icon circle --}}
                                    <div
                                        class="w-12 h-12 flex items-center justify-center rounded-xl
                                               bg-primary/10 text-primary
                                               group-hover:bg-primary group-hover:text-white
                                               transition-colors duration-200 shrink-0">
                                        @if ($featureIcon)
                                            {!! $featureIcon !!}
                                        @else
                                            {{-- Minimal placeholder icon (3 dots) --}}
                                            <span class="w-2 h-2 rounded-full bg-current shadow-[0_0_0_3px_rgba(255,255,255,0.35)]"></span>
                                        @endif
                                    </div>

                                    {{-- Feature title --}}
                                    <span class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white text-center sm:text-start">
                                        {{ $featureTitle }}
                                    </span>
                                </dt>

                                <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed text-center sm:text-start">
                                    {{ $featureDescription }}
                                </dd>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
