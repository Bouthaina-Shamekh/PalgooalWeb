@php
    /**
     * Hero Default Section
     * --------------------
     * Expected variables (passed from front.pages.page):
     *
     * @var \App\Models\Section $section
     * @var string|null         $title        // Localized section title (optional – from SectionTranslation->title)
     * @var array               $content      // JSON decoded array from SectionTranslation->content
     * @var string              $variant      // Design variant (default, v2, ...)
     *
     * Recommended JSON structure for $content:
     *
     * {
     *   "eyebrow": "Small top label",
     *   "title": "Main hero title",
     *   "subtitle": "Supporting description text",
     *   "primary_button": {
     *     "label": "Primary CTA",
     *     "url": "/checkout"
     *   },
     *   "secondary_button": {
     *     "label": "Secondary CTA",
     *     "url": "/templates"
     *   },
     *   "features": [
     *     "Feature 1",
     *     "Feature 2",
     *     "Feature 3"
     *   ],
     *   "media_type": "image",            // image | illustration | video (for future use)
     *   "media_url": "https://example.com/image.png"
     * }
     */

    // Safe extraction with fallbacks
    $eyebrow   = $content['eyebrow'] ?? null;
    $heroTitle = $content['title'] ?? $title ?? '';
    $subtitle  = $content['subtitle'] ?? null;

    $primaryButton   = $content['primary_button']   ?? [];
    $secondaryButton = $content['secondary_button'] ?? [];

    $primaryLabel = $primaryButton['label'] ?? null;
    $primaryUrl   = $primaryButton['url']   ?? null;

    $secondaryLabel = $secondaryButton['label'] ?? null;
    $secondaryUrl   = $secondaryButton['url']   ?? null;

    $features   = is_array($content['features'] ?? null) ? $content['features'] : [];
    $mediaType  = $content['media_type'] ?? 'image';
    $mediaUrl   = $content['media_url']  ?? null;

    // Simple flag to decide if we show the media column
    $hasMedia = ! empty($mediaUrl);
@endphp

<section
    class="relative overflow-hidden bg-background dark:bg-gray-950 text-primary dark:text-white py-16 sm:py-20 px-4 sm:px-8 lg:px-24 rtl:text-right ltr:text-left"
>
    {{-- Soft gradient background blob (purely decorative) --}}
    <div class="pointer-events-none absolute inset-x-0 -top-32 opacity-40 blur-3xl">
        <div class="mx-auto h-64 max-w-3xl rounded-full bg-gradient-to-r from-primary/20 via-secondary/20 to-emerald-300/20"></div>
    </div>

    <div class="relative mx-auto max-w-7xl">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center rtl:lg:flex-row-reverse">
            {{-- Text Column --}}
            <div class="space-y-6">
                {{-- Eyebrow / small label --}}
                @if($eyebrow)
                    <div class="inline-flex items-center rounded-full bg-primary/5 px-3 py-1 text-xs font-semibold text-primary dark:bg-primary/10">
                        {{ $eyebrow }}
                    </div>
                @endif

                {{-- Main Title --}}
                @if($heroTitle)
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
                        {{ $heroTitle }}
                    </h1>
                @endif

                {{-- Subtitle --}}
                @if($subtitle)
                    <p class="text-sm sm:text-base lg:text-lg text-tertiary dark:text-gray-300 max-w-xl">
                        {{ $subtitle }}
                    </p>
                @endif

                {{-- Call To Actions --}}
                @if($primaryLabel || $secondaryLabel)
                    <div class="flex flex-wrap items-center gap-3 mt-4 rtl:flex-row-reverse">
                        @if($primaryLabel && $primaryUrl)
                            <a
                                href="{{ $primaryUrl }}"
                                class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-primary/30 hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60 focus-visible:ring-offset-2 focus-visible:ring-offset-background transition"
                            >
                                {{ $primaryLabel }}
                            </a>
                        @endif

                        @if($secondaryLabel && $secondaryUrl)
                            <a
                                href="{{ $secondaryUrl }}"
                                class="inline-flex items-center justify-center rounded-full border border-primary/30 bg-white/80 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5 dark:bg-gray-900 dark:border-primary/40 dark:hover:bg-primary/10 transition"
                            >
                                {{ $secondaryLabel }}
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Small feature bullets under the buttons --}}
                @if(!empty($features))
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs sm:text-sm text-tertiary dark:text-gray-300">
                        @foreach($features as $feature)
                            @if($feature)
                                <div class="flex items-start gap-2 rtl:flex-row-reverse">
                                    <span class="mt-1 inline-flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-[10px] text-emerald-600 dark:text-emerald-300">
                                        ✓
                                    </span>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Media Column (image / illustration / video placeholder) --}}
            @if($hasMedia)
                <div class="relative lg:justify-self-end">
                    <div class="relative mx-auto max-w-md">
                        {{-- Decorative background card --}}
                        <div class="absolute inset-0 translate-y-4 rounded-3xl bg-gradient-to-tr from-primary/10 via-secondary/10 to-emerald-300/10 blur-sm"></div>

                        {{-- Media wrapper --}}
                        <div class="relative overflow-hidden rounded-3xl border border-white/60 bg-white/90 shadow-xl shadow-primary/10 dark:bg-gray-900 dark:border-gray-700">
                            @if($mediaType === 'video')
                                {{-- Simple responsive video embed --}}
                                <div class="aspect-video w-full">
                                    <iframe
                                        src="{{ $mediaUrl }}"
                                        class="h-full w-full"
                                        loading="lazy"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                            @else
                                {{-- Default: image / illustration --}}
                                <img
                                    src="{{ $mediaUrl }}"
                                    alt="{{ $heroTitle ?? 'Hero image' }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover"
                                >
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
