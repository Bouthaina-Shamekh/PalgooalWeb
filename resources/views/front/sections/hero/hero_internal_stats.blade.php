@php
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));

    $featureItems = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            return [
                'text' => trim((string) ($item['text'] ?? '')),
                'value' => trim((string) ($item['value'] ?? '')),
                'label' => trim((string) ($item['label'] ?? '')),
                'icon_source' => ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class',
                'icon' => trim((string) ($item['icon'] ?? '')),
                'icon_media' => $item['icon_media'] ?? null,
            ];
        })
        ->filter(fn($i) => $i['text'] || $i['value'] || $i['label'] || $i['icon'] || $i['icon_media'])
        ->values();

    $resolvedFeatureMedia = \App\Support\Sections\SectionFrontendMediaResolver::resolveMany(
        $featureItems->pluck('icon_media'),
    );

    $backgroundUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['background_image'] ?? null);

    $features_title = trim((string) ($data['features_title'] ?? ''));

    $ctaTitle = trim((string) ($data['cta_title'] ?? ''));
    $ctaLabel = trim((string) data_get($data, 'cta_button.label', ''));
    $ctaUrl = trim((string) data_get($data, 'cta_button.url', ''));
    $ctaNewTab = (bool) data_get($data, 'cta_button.new_tab', false);
@endphp
<section id="programming" class="relative min-h-[448px] bg-purple-brand overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center"
        @if ($backgroundUrl) style="background-image: url('{{ $backgroundUrl }}')" @endif>
    </div>
    <div class="absolute inset-0 bg-purple-brand/80"></div>
    <div class="container mx-auto relative px-2 py-14">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center ">
            <div class="lg:col-span-7">
                @if (!empty($title))
                    <h1 class="text-white font-extrabold text-3xl md:text-4xl lg:text-[40px] mb-1 uppercase mb-4">
                        {{ $title }}
                    </h1>
                @endif
                @if (!empty($subtitle))
                    <p class="text-[#d9d9d9] text-base md:text-lg leading-relaxed mb-4 md:mb-20 max-w-[735px]">
                        {{ $subtitle }}
                    </p>
                @endif
                <div class="space-y-3 mb-6">
                    @if (!empty($features_title))
                        <p class="text-white font-bold text-base">{{ $features_title }}</p>
                    @endif

                    <div class="flex flex-wrap items-baseline gap-4 md:gap-6">
                        @foreach ($featureItems as $item)
                            <div class="flex items-center gap-1">
                                {{-- ICON --}}
                                @if ($item['icon_source'] === 'media' && !empty($item['icon_media']))
                                    @if (!empty($resolvedFeatureMedia[$item['icon_media']]))
                                        <img src="{{ $resolvedFeatureMedia[$item['icon_media']] }}"
                                            class="w-5 h-5 object-contain">
                                    @endif
                                @elseif ($item['icon_source'] === 'class' && $item['icon'])
                                    <span>
                                        <i class="{{ $item['icon'] }} text-red-brand text-lg"></i>
                                    </span>
                                @endif
                                {{-- VALUE AND LABEL --}}
                                @if ($item['value'])
                                    <span class="text-white font-bold text-base">{{ $item['value'] }}</span>
                                @endif
                                @if ($item['label'])
                                    <span class="text-[#a8a8a8] text-base capitalize">{{ $item['label'] }}</span>
                                @endif
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
            <div class="lg:col-span-5 flex flex-col md:items-end items-center">
                @if ($ctaTitle || ($ctaLabel && $ctaUrl))
                <div class="bg-purple-brand rounded-[20px] p-8 mb-6">
                    @if ($ctaTitle)
                    <p class="text-white font-bold text-xl md:text-[29px] leading-tight mb-4 capitalize">
                        {{ $ctaTitle }}
                    </p>
                    @endif
                    @if ($ctaLabel && $ctaUrl)
                    <a href="{{ $ctaUrl }}"
                    @if ($ctaNewTab) target="_blank" rel="noopener" @endif
                        class="block bg-red-brand text-white text-center py-3 px-4 rounded-xl text-lg md:text-xl hover:bg-red-brand/90 transition-all duration-300 hover:-translate-y-0.5">
                        {{ $ctaLabel }}
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
