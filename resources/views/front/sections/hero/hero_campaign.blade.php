@php
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $featuresHeading = trim((string) ($data['features_heading'] ?? ''));
    $primaryLabel = trim((string) data_get($data, 'primary_button.label', ''));
    $primaryUrl = trim((string) data_get($data, 'primary_button.url', ''));
    $primaryNewTab = (bool) data_get($data, 'primary_button.new_tab', false);
    $mediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['media_url'] ?? null);
    $mediaAlt = trim((string) ($data['media_alt'] ?? ''));

    $features = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($feature) {
            if (is_string($feature)) {
                $feature = ['text' => $feature];
            }

            if (! is_array($feature)) {
                return null;
            }

            $text = trim((string) ($feature['text'] ?? ''));

            if ($text === '') {
                return null;
            }

            $iconSource = trim((string) ($feature['icon_source'] ?? 'class'));

            return [
                'text' => $text,
                'icon_source' => in_array($iconSource, ['class', 'media'], true) ? $iconSource : 'class',
                'icon' => trim((string) ($feature['icon'] ?? '')),
                'icon_media' => $feature['icon_media'] ?? null,
            ];
        })
        ->filter()
        ->values();

    $resolvedFeatureMedia = \App\Support\Sections\SectionFrontendMediaResolver::resolveMany(
        $features->pluck('icon_media'),
    );

    $trustItems = collect(is_array($data['trust_items'] ?? null) ? $data['trust_items'] : [])
        ->map(function ($item) {
            if (is_array($item)) {
                return trim((string) ($item['text'] ?? ''));
            }

            return trim((string) $item);
        })
        ->filter()
        ->values();

    if ($mediaAlt === '') {
        $mediaAlt = $subtitle ?: $title ?: __('Hero illustration');
    }
@endphp

<section id="hero" class="px-4 pt-6 pb-8 sm:px-6 lg:px-12 lg:pt-10 lg:pb-18">
    <div
        class="container mx-auto flex h-full flex-col-reverse items-center justify-between gap-12 lg:gap-16 ltr:lg:flex-row rtl:lg:flex-row-reverse">
        <div class="text-content w-full text-center lg:w-1/2 lg:text-start ltr:lg:order-1 rtl:lg:order-2">
            @if ($title !== '')
                <span
                    class="mb-4 inline-flex items-center gap-2 rounded-full bg-purple-100 px-4 py-1.5 text-base font-medium text-purple-700">
                    {{ $title }}
                </span>
            @endif

            @if ($subtitle !== '')
                <h2 class="mb-2 text-2xl font-bold text-purple-brand md:text-3xl">
                    {{ $subtitle }}
                </h2>
            @endif

            @if ($description !== '')
                <p class="mb-4 text-base leading-relaxed text-gray-500 md:text-lg">
                    {{ $description }}
                </p>
            @endif

            @if ($featuresHeading !== '')
                <h3 class="mb-4 text-start text-lg font-bold md:text-2xl">
                    {{ $featuresHeading }}
                </h3>
            @endif

            @if ($features->isNotEmpty())
                <div class="mb-10 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($features as $feature)
                        <div class="flex items-center gap-3 ltr:justify-start rtl:justify-start">
                            @if ($feature['icon_source'] === 'media' && ! empty($feature['icon_media']) && ! empty($resolvedFeatureMedia[(int) $feature['icon_media']]))
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center md:h-6 md:w-6"
                                    aria-hidden="true">
                                    <img src="{{ $resolvedFeatureMedia[(int) $feature['icon_media']] }}" alt=""
                                        class="h-full w-full object-contain">
                                </span>
                            @elseif ($feature['icon'] !== '')
                                <i class="{{ $feature['icon'] }} text-xl text-red-brand md:text-2xl"
                                    aria-hidden="true"></i>
                            @else
                                <svg class="h-5 text-red-brand" fill="currentColor" viewBox="0 0 27 21"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z"
                                        fill="#BA112C" />
                                </svg>
                            @endif
                            <span class="text-base text-purple-brand md:text-xl">{{ $feature['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($primaryLabel !== '' && $primaryUrl !== '')
                <a href="{{ $primaryUrl }}" @if ($primaryNewTab) target="_blank" rel="noopener" @endif
                    class="inline-flex items-center justify-center rounded-xl bg-red-brand px-10 py-3 text-lg text-white shadow-md transition-all duration-300 hover:translate-x-1 hover:bg-red-brand/90 hover:shadow-lg md:text-xl">
                    {{ $primaryLabel }}
                </a>
            @endif

            @if ($trustItems->isNotEmpty())
                <div class="mt-4 flex flex-wrap items-center justify-start gap-2 text-sm text-gray-500">
                    @foreach ($trustItems as $trustItem)
                        <span class="flex items-center gap-1">
                            <svg class="h-4 w-4 text-red-brand" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $trustItem }}
                        </span>

                        @if (! $loop->last)
                            <span class="text-gray-300">&bull;</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="hero-image h-auto w-full p-8 ltr:lg:order-2 rtl:lg:order-1 lg:w-1/2">
            <div class="group relative h-full w-full rounded-[40px]">
                @if ($mediaUrl)
                    <img src="{{ $mediaUrl }}"
                        class="h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
                        alt="{{ $mediaAlt }}" loading="lazy">
                @else
                    <div
                        class="flex min-h-[24rem] items-center justify-center rounded-[40px] bg-background p-10 text-center text-tertiary">
                        {{ __('Add an illustration from the section editor.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
