@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-16 lg:py-24';

    $brandPrefix = $content['brand_prefix'] ?? 'PAL';
    $brandSuffix = $content['brand_suffix'] ?? 'GOALS';
    $sectionTitle = $content['title'] ?? '';
    $sectionDescription = $content['description'] ?? '';
    $outputsHeading = $content['outputs_heading'] ?? __('What Are Our Outputs?');

    $outputs = collect(is_array($content['outputs'] ?? null) ? $content['outputs'] : [])
        ->map(fn ($item) => is_scalar($item) ? trim((string) $item) : '')
        ->filter()
        ->values();

    $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
    $primaryLabel = $primaryButton['label'] ?? null;
    $primaryUrl = $primaryButton['url'] ?? null;

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

<section id="programming" class="{{ $paddingY }} overflow-hidden bg-white px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="flex flex-col gap-12 lg:flex-row lg:items-stretch lg:gap-24">
            <div class="flex w-full flex-col items-center text-center ltr:lg:items-start ltr:lg:text-left rtl:lg:items-start rtl:lg:text-right lg:w-1/2">
                <p class="text-lg md:text-xl">
                    <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
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
                                    <span class="h-0.5 w-4 flex-shrink-0 rounded-full bg-red-brand"></span>
                                    <span>{{ $output }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($primaryLabel && $primaryUrl)
                    <a
                        href="{{ $primaryUrl }}"
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
