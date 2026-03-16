@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'pt-6 pb-8 lg:pt-10 lg:pb-18';

    $heroTitle = $content['title'] ?? '';
    $heroSubtitle = $content['subtitle'] ?? '';
    $heroDescription = $content['description'] ?? '';
    $featuresHeading = $content['features_heading'] ?? __('The campaign includes:');

    $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
    $primaryLabel = $primaryButton['label'] ?? null;
    $primaryUrl = $primaryButton['url'] ?? null;

    $sanitizeIconClass = static function ($value): ?string {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    };

    $features = collect(is_array($content['features'] ?? null) ? $content['features'] : [])
        ->map(function ($feature) use ($sanitizeIconClass): ?array {
            if (is_string($feature)) {
                $text = trim($feature);

                return $text !== ''
                    ? ['text' => $text, 'icon' => null]
                    : null;
            }

            if (! is_array($feature)) {
                return null;
            }

            $text = trim((string) ($feature['text'] ?? $feature['title'] ?? $feature['label'] ?? ''));

            if ($text === '') {
                return null;
            }

            return [
                'text' => $text,
                'icon' => $sanitizeIconClass($feature['icon'] ?? null),
            ];
        })
        ->filter()
        ->values();
    $rawMediaValue = $content['media_url'] ?? null;
    $mediaUrl = null;

    if (is_numeric($rawMediaValue)) {
        $media = \App\Models\Media::find((int) $rawMediaValue);
        $mediaUrl = $media?->url ?? ($media?->file_url ?? null);
    } elseif (is_string($rawMediaValue) && $rawMediaValue !== '') {
        $mediaUrl = \Illuminate\Support\Str::startsWith($rawMediaValue, ['http://', 'https://', '//', '/', 'data:'])
            ? $rawMediaValue
            : asset($rawMediaValue);
    }
@endphp

<section id="hero" class="px-4 sm:px-6 lg:px-12 {{ $paddingY }}">
    <div class="container mx-auto flex h-full flex-col-reverse items-center justify-between gap-12 lg:gap-16 ltr:lg:flex-row rtl:lg:flex-row-reverse">
        <div class="text-content w-full text-center lg:w-1/2 lg:text-start ltr:lg:order-1 rtl:lg:order-2">
            @if ($heroTitle)
                <h1 class="mb-2 text-4xl leading-tight font-extrabold text-purple-brand md:text-6xl">
                    {{ $heroTitle }}
                </h1>
            @endif

            @if ($heroSubtitle)
                <h2 class="mb-2 text-2xl font-bold text-purple-brand md:text-3xl">
                    {{ $heroSubtitle }}
                </h2>
            @endif

            @if ($heroDescription)
                <p class="mb-4 text-base leading-relaxed text-gray-500 md:text-lg">
                    {{ $heroDescription }}
                </p>
            @endif

            @if ($featuresHeading)
                <h3 class="mb-4 text-start text-lg font-bold md:text-2xl">
                    {{ $featuresHeading }}
                </h3>
            @endif

            @if ($features->isNotEmpty())
                <div class="mb-10 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($features as $feature)
                        <div class="flex items-center gap-3 ltr:justify-start rtl:justify-start">
                            @if ($feature['icon'])
                                <i class="{{ $feature['icon'] }} text-xl text-red-brand md:text-2xl" aria-hidden="true"></i>
                            @else
                                <svg class="h-5 text-red-brand" fill="currentColor" viewBox="0 0 27 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="#BA112C" />
                                </svg>
                            @endif
                            <span class="text-base text-purple-brand md:text-xl">{{ $feature['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($primaryLabel && $primaryUrl)
                <a
                    href="{{ $primaryUrl }}"
                    class="inline-flex items-center justify-center rounded-xl bg-red-brand px-10 py-3 text-lg text-white shadow-md transition-all duration-300 hover:translate-x-1 hover:bg-red-brand/90 hover:shadow-lg md:text-xl"
                >
                    {{ $primaryLabel }}
                </a>
            @endif
        </div>

        <div class="hero-image h-auto w-full p-8 ltr:lg:order-2 rtl:lg:order-1 lg:w-1/2">
            <div class="group relative h-full w-full rounded-[40px]">
                @if ($mediaUrl)
                    <img
                        src="{{ $mediaUrl }}"
                        class="h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
                        alt="{{ $heroSubtitle ?: $heroTitle ?: 'Hero illustration' }}"
                        loading="lazy"
                    >
                @else
                    <div class="flex min-h-[24rem] items-center justify-center rounded-[40px] bg-background p-10 text-center text-tertiary">
                        {{ __('Add an illustration URL from the section editor.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
