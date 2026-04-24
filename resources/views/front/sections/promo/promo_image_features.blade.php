@php
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $features = trim((string) ($data['features_title'] ?? ''));
    $image = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);
    $featureItems = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            return [
                'text' => trim((string) ($item['text'] ?? '')),
                'icon_source' => ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class',
                'icon' => trim((string) ($item['icon'] ?? '')),
                'icon_media' => $item['icon_media'] ?? null,
            ];
        })
        ->filter(fn($i) => $i['text'] || $i['icon'] || $i['icon_media'])
        ->values();
    $resolvedFeatureMedia = \App\Support\Sections\SectionFrontendMediaResolver::resolveMany(
    $featureItems->pluck('icon_media'),
    );
    $ctaLabel = trim((string) data_get($data, 'cta_button.label', ''));
    $ctaUrl = trim((string) data_get($data, 'cta_button.url', ''));
    $ctaNewTab = (bool) data_get($data, 'cta_button.new_tab', false);

@endphp
<section class="bg-[#F8F8F8] py-16 md:pb-20 md:pt-10 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="bg-white rounded-[20px] p-6 md:p-10 overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 lg:gap-18 items-center">
                <div class="order-2 lg:order-1 md:col-span-3">
                    @if ($title !== '')
                        <h2 class="text-red-brand font-bold text-xl md:text-[25px]">
                            {{ $title }}
                        </h2>
                    @endif
                    @if ($description !== '')
                        <p class="text-purple-brand text-base md:text-lg leading-[30px] mb-8">
                            {{ $description }}
                        </p>
                    @endif
                    @if ($features !== '')
                        <p class="text-purple-brand font-bold text-xl mb-2">
                            {{ $features }}
                        </p>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-10">
                        @foreach ($featureItems as $item)
                            <div class="flex items-center gap-2">
                                @if ($item['icon_source'] === 'media' && !empty($item['icon_media']))
                                    @if (!empty($resolvedFeatureMedia[$item['icon_media']]))
                                        <img src="{{ $resolvedFeatureMedia[$item['icon_media']] ?? '' }}"
                                            alt="Feature Icon" class="h-5 w-5 shrink-0">
                                    @endif
                                @elseif ($item['icon_source'] === 'class' && $item['icon'])
                                    <i class="{{ $item['icon'] }} text-red-brand text-lg"></i>
                                @endif

                                @if ($item['text'])
                                    <span class="text-purple-brand text-base md:text-xl capitalize">
                                        {{ $item['text'] }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($ctaLabel && $ctaUrl)
                    <a href="{{ $ctaUrl }}"
                    @if ($ctaNewTab) target="_blank" rel="noopener" @endif
                        class="inline-block bg-red-brand text-white py-3 px-8 rounded-xl text-lg md:text-xl font-medium hover:bg-red-brand/90 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg">
                        {{ $ctaLabel }}
                    </a>
                    @endif
                </div>
                <div class="order-1 lg:order-2 md:col-span-2 h-full">
                    <div class="h-full">
                        @if ($image)
                        <img src="{{ $image }}" loading="lazy" alt="SaaS platform mockup" 
                            class="aspect-4/3 md:aspect-auto w-full h-full rounded-[36px] object-cover shadow-lg">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
