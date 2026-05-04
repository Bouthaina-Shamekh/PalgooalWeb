@php
    $brandPrefix = trim((string) ($data['brand_prefix'] ?? ''));
    $brandSuffix = trim((string) ($data['brand_suffix'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $outputs_title = trim((string) ($data['outputs_title'] ?? ''));
    $outputs = collect(is_array($data['outputs'] ?? null) ? $data['outputs'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $text = trim((string) ($item['text'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));
            $iconMedia = $item['icon_media'] ?? null;

            if ($text === '' && $icon === '' && empty($iconMedia)) {
                return null;
            }

            return [
                'text' => $text,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media' => $iconMedia,
            ];
        })
        ->filter()
        ->values();

    $resolvedFeatureMedia = \App\Support\Sections\SectionFrontendMediaResolver::resolveMany(
        $outputs->pluck('icon_media'),
    );

    $buttonLabel = trim((string) data_get($data, 'button_label', ''));
    $buttonUrl = trim((string) data_get($data, 'button_url', ''));
    $buttonNewTab = (bool) data_get($data, 'button_new_tab', false);
    $mediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);
    $alt = trim((string) ($data['alt'] ?? ''));
@endphp
<section id="programming" class="py-16 lg:py-24 px-4 sm:px-6 lg:px-12 bg-white overflow-hidden">
    <div class="container mx-auto">
        <div class="flex flex-col lg:flex-row lg:items-stretch gap-12 lg:gap-24">

            <!-- Text Content (Start Side) -->
            <div
                class="w-full lg:w-1/2 flex flex-col items-center lg:items-start text-center ltr:lg:text-left rtl:lg:text-right">

                <!-- Label -->
                <p class="text-lg md:text-xl">
                    @if ($brandPrefix !== '')
                        <span class="text-red-brand">{{ $brandPrefix }}</span>
                    @endif

                    @if ($brandSuffix !== '')
                        <span class="text-purple-brand">{{ $brandSuffix }}</span>
                    @endif
                </p>

                <!-- Title -->
                @if ($title !== '')
                    <h2 class="text-purple-brand font-extrabold text-4xl md:text-[40px] mb-1 uppercase leading-tight font-theme-heading">
                        {{ $title }}
                    </h2>
                @endif

                <!-- Description -->
                @if ($description !== '')
                    <p class="text-gray-dark text-lg md:text-xl leading-relaxed mb-4 max-w-xl">
                        {{ $description }}
                    </p>
                @endif

                <!-- Outputs Subsection -->
                <div class="w-full mb-8">
                    @if ($outputs_title !== '')
                        <h3 class="text-purple-brand text-xl font-bold mb-4 text-start font-theme-heading">
                            {{ $outputs_title }}
                        </h3>
                    @endif
                    @if ($outputs->isNotEmpty())
                        <ul class="space-y-3 inline-block w-full">
                            @foreach ($outputs as $outputItem)
                                <li
                                    class="flex items-center justify-start gap-3 text-lg font-medium text-gray-700 hover:text-red-brand transition-colors duration-300">
                                    @if (($outputItem['icon_source'] ?? 'class') === 'svg' && !empty($outputItem['icon_svg']))
                                        <span
                                            class="inline-flex h-5 w-5 shrink-0 items-center justify-center text-red-brand md:h-6 md:w-6"
                                            aria-hidden="true">
                                            {!! $outputItem['icon_svg'] !!}
                                        </span>
                                    @elseif (($outputItem['icon_source'] ?? 'class') === 'media' && !empty($outputItem['icon_media_url']))
                                        <span
                                            class="inline-flex h-5 w-5 shrink-0 items-center justify-center md:h-6 md:w-6"
                                            aria-hidden="true">
                                            <img src="{{ $outputItem['icon_media_url'] }}" alt=""
                                                class="h-full w-full object-contain">
                                        </span>
                                    @elseif ($outputItem['icon'])
                                        <i class="{{ $outputItem['icon'] }} text-xl text-red-brand md:text-2xl"
                                            aria-hidden="true"></i>
                                    @else
                                        <span class="w-4 h-0.5 bg-red-brand rounded-full flex-shrink-0"></span>
                                    @endif
                                    <span>{{ $outputItem['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- CTA Button -->
                @if ($buttonLabel !== '' && $buttonUrl !== '')
                    <a href="{{ $buttonUrl }}"
                        @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                        class="bg-red-brand text-white md:px-10 md:py-4 px-6 py-3 rounded-xl text-lg md:text-xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        {{ $buttonLabel }}
                    </a>
                @endif
            </div>

            <!-- Image (End Side) -->
            <div class="w-full lg:w-1/2 h-[400px] lg:h-auto">
                <div class="relative h-full w-full rounded-[40px] overflow-hidden shadow-2xl group">
                    <div
                        class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-all duration-500 z-10">
                    </div>
                    @if ($mediaUrl)
                        <img src="{{ $mediaUrl }}"
                            class="w-full h-full object-cover object-center transform transition-transform duration-700 group-hover:scale-105"
                            alt="{{ $alt ?: $title ?: 'Hero illustration' }}" loading="lazy">
                    @else
                        <div
                            class="flex min-h-[24rem] items-center justify-center rounded-[40px] bg-background p-10 text-center text-tertiary">
                            {{ __('Add an image from the section editor.') }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</section>
