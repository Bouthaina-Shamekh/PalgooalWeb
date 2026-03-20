@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-16 lg:py-24';

    $brandPrefix = $content['brand_prefix'] ?? 'PAL';
    $brandSuffix = $content['brand_suffix'] ?? 'GOALS';
    $sectionTitle = $content['title'] ?? '';
    $sectionDescription = $content['description'] ?? '';
    $outputsHeading = $content['outputs_heading'] ?? __('What Are Our Outputs?');

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

    $sanitizeIconClass = static function ($value): ?string {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    };

    $outputs = collect(is_array($content['outputs'] ?? null) ? $content['outputs'] : [])
        ->map(function ($item) use ($sanitizeIconClass, $resolveMediaUrl) {
            if (is_string($item)) {
                $text = trim($item);
                return $text !== '' ? ['text' => $text, 'icon_source' => 'class', 'icon' => null, 'icon_media_url' => null] : null;
            }

            if (! is_array($item)) {
                return null;
            }

            $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
            if ($text === '') {
                return null;
            }

            $iconSource = in_array(($item['icon_source'] ?? 'class'), ['class', 'media'], true)
                ? (string) ($item['icon_source'] ?? 'class')
                : 'class';

            return [
                'text' => $text,
                'icon_source' => $iconSource,
                'icon' => $sanitizeIconClass($item['icon'] ?? null),
                'icon_media_url' => $resolveMediaUrl($item['icon_media'] ?? null),
            ];
        })
        ->filter()
        ->values();

    $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
    $primaryLabel = $primaryButton['label'] ?? null;
    $primaryUrl = $primaryButton['url'] ?? null;
    $primaryNewTab = filter_var($primaryButton['new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $rawMediaValue = $content['media_url'] ?? null;
    $mediaUrl = $resolveMediaUrl($rawMediaValue);
@endphp

<section id="programming" class="{{ $paddingY }} overflow-hidden bg-white px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="flex flex-col gap-12 lg:flex-row lg:items-stretch lg:gap-24">
            <div class="flex w-full flex-col items-center text-center ltr:lg:items-start ltr:lg:text-left rtl:lg:items-start rtl:lg:text-right lg:w-1/2">
                <p class="inline-flex items-center gap-1 text-lg md:text-xl">
                    <span class="text-red-brand">{{ $brandPrefix }}</span>
                    <span class="text-purple-brand">{{ $brandSuffix }}</span>
                </p>

                @if ($sectionTitle)
                    <h2 class="mb-1 text-4xl font-extrabold uppercase leading-tight text-purple-brand md:text-[40px]">
                        {{ $sectionTitle }}
                    </h2>
                @endif

                @if ($sectionDescription)
                    <p class="mb-4 max-w-xl text-lg leading-relaxed text-gray-dark md:text-xl">
                        {{ $sectionDescription }}
                    </p>
                @endif

                <div class="mb-8 w-full">
                    @if ($outputsHeading)
                        <h3 class="mb-4 text-start text-xl font-bold text-purple-brand">
                            {{ $outputsHeading }}
                        </h3>
                    @endif

                    @if ($outputs->isNotEmpty())
                        <ul class="inline-block w-full space-y-3">
                            @foreach ($outputs as $output)
                                <li class="flex items-center justify-start gap-3 text-lg font-medium text-gray-700 transition-colors duration-300 hover:text-red-brand">
                                    @if (($output['icon_source'] ?? 'class') === 'media' && ! empty($output['icon_media_url']))
                                        <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center text-red-brand">
                                            <img src="{{ $output['icon_media_url'] }}" alt="" class="h-5 w-5 object-contain">
                                        </span>
                                    @elseif (! empty($output['icon']))
                                        <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center text-red-brand">
                                            <i class="{{ $output['icon'] }} text-base leading-none" aria-hidden="true"></i>
                                        </span>
                                    @else
                                        <span class="h-0.5 w-4 flex-shrink-0 rounded-full bg-red-brand"></span>
                                    @endif
                                    <span>{{ $output['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($primaryLabel && $primaryUrl)
                    <a
                        href="{{ $primaryUrl }}"
                        target="{{ $primaryNewTab ? '_blank' : '_self' }}"
                        @if ($primaryNewTab) rel="noopener noreferrer" @endif
                        class="inline-flex items-center justify-center rounded-xl bg-red-brand px-6 py-3 text-lg text-white shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl md:px-10 md:py-4 md:text-xl"
                    >
                        {{ $primaryLabel }}
                    </a>
                @endif
            </div>

            <div class="h-[400px] w-full lg:h-auto lg:w-1/2">
                <div class="group relative h-full w-full overflow-hidden rounded-[40px] shadow-2xl">
                    @if ($mediaUrl)
                        <div class="absolute inset-0 z-10 bg-black/10 transition-all duration-500 group-hover:bg-transparent"></div>
                        <img
                            src="{{ $mediaUrl }}"
                            class="h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
                            alt="{{ $sectionTitle ?: 'Programming section image' }}"
                            loading="lazy"
                        >
                    @else
                        <div class="flex h-full min-h-[26rem] items-center justify-center rounded-[40px] bg-slate-100 p-10 text-center text-slate-500">
                            {{ __('Choose a featured image from the section editor.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
