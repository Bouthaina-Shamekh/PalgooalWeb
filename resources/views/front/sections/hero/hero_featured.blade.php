@php
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));

    $featureItems = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (! is_array($item)) {
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
        $featureItems->pluck('icon_media')
    );

    $backgroundUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['background_image'] ?? null);

    $ctaTitle = trim((string) ($data['cta_title'] ?? ''));
    $ctaLabel = trim((string) data_get($data, 'cta_button.label', ''));
    $ctaUrl = trim((string) data_get($data, 'cta_button.url', ''));
    $ctaNewTab = (bool) data_get($data, 'cta_button.new_tab', false);
@endphp

<section id="hosting" class="relative min-h-[448px] overflow-hidden bg-purple-brand">
    <div
        class="absolute inset-0 bg-cover bg-center"
        @if ($backgroundUrl)
            style="background-image: url('{{ $backgroundUrl }}')"
        @endif
    ></div>
    <div class="absolute inset-0 bg-purple-brand/80"></div>

    <div class="container relative mx-auto px-2">
        <div class="grid grid-cols-1 items-center gap-8 py-12 md:py-14 lg:grid-cols-12">
            <div class="animate-from-left lg:col-span-7">
                @if ($title !== '')
                    <h1 class="mb-4 text-3xl font-extrabold uppercase text-white md:text-4xl lg:text-[40px] font-theme-heading">
                        {{ $title }}
                    </h1>
                @endif

                @if ($subtitle !== '')
                    <p class="mb-6 text-base leading-relaxed text-[#d9d9d9] md:text-lg">
                        {{ $subtitle }}
                    </p>
                @endif

                @if ($featureItems->isNotEmpty())
                    <div class="mb-6 space-y-3">
                        @foreach ($featureItems as $featureItem)
                            @php
                                $featureIconUrl = $featureItem['icon_source'] === 'media' && !empty($featureItem['icon_media'])
                                    ? ($resolvedFeatureMedia[$featureItem['icon_media']] ?? null)
                                    : null;
                            @endphp

                            <div class="flex items-center gap-3">
                                @if ($featureIconUrl)
                                    <img src="{{ $featureIconUrl }}" alt="" class="h-5 w-5 flex-shrink-0 object-contain">
                                @elseif (!empty($featureItem['icon']))
                                    <i class="{{ $featureItem['icon'] }} text-red-brand text-lg leading-none flex-shrink-0" aria-hidden="true"></i>
                                @else
                                    <svg class="h-5 flex-shrink-0 text-red-brand" fill="currentColor" viewBox="0 0 27 21" aria-hidden="true">
                                        <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="#BA112C" />
                                    </svg>
                                @endif

                                <span class="text-base text-white md:text-lg">
                                    {{ $featureItem['text'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-center animate-from-right lg:col-span-5 lg:justify-end">
                @if ($ctaTitle !== '' || ($ctaLabel !== '' && $ctaUrl !== ''))
                    <div class="rounded-[20px] bg-purple-brand p-8">
                        @if ($ctaTitle !== '')
                            <p class="mb-4 text-xl font-bold capitalize leading-tight text-white md:text-[29px]">
                                {{ $ctaTitle }}
                            </p>
                        @endif

                        @if ($ctaLabel !== '' && $ctaUrl !== '')
                            <a href="{{ $ctaUrl }}"
                               @if ($ctaNewTab) target="_blank" rel="noopener noreferrer" @endif
                               class="block rounded-xl bg-red-brand px-4 py-3 text-center text-lg text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-red-brand/90 md:text-xl">
                                {{ $ctaLabel }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>