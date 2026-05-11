@php
    $sectionId = trim((string) ($data['section_id'] ?? 'promo-image-features'));

    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $featuresTitle = trim((string) ($data['features_title'] ?? ''));

    $image = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);
    $alt = trim((string) ($data['alt'] ?? ''));

    $featureItems = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $text = trim((string) ($item['text'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));

            $iconMediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve(
                $item['icon_media'] ?? null
            );

            if ($text === '' && $icon === '' && ! $iconMediaUrl) {
                return null;
            }

            return [
                'text' => $text,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media_url' => $iconMediaUrl,
            ];
        })
        ->filter()
        ->values();

    $buttonLabel = trim((string) ($data['button_label'] ?? data_get($data, 'cta_button.label', '')));
    $buttonUrl = trim((string) ($data['button_url'] ?? data_get($data, 'cta_button.url', '')));
    $buttonNewTab = filter_var($data['button_new_tab'] ?? data_get($data, 'cta_button.new_tab', false), FILTER_VALIDATE_BOOLEAN);
    $hasButton = $buttonLabel !== '' && $buttonUrl !== '';

    $hasContent = $title !== ''
        || $description !== ''
        || $featuresTitle !== ''
        || $featureItems->isNotEmpty()
        || $image
        || $hasButton;
@endphp

<section id="{{ $sectionId }}" class="bg-theme-muted font-theme-body py-16 md:pb-20 md:pt-10 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        @if (! $hasContent)
            <x-front.empty-section-state
                title="{{ __('No promo content yet') }}"
                description="{{ __('Add text, feature items, or media from the section editor.') }}"
            />
        @else
            <div class="bg-theme-surface rounded-theme-xl shadow-theme p-6 md:p-10 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 lg:gap-18 items-center">

                    <div class="order-2 lg:order-1 md:col-span-3">
                        @if ($title !== '')
                            <h2 class="font-theme-heading text-theme-secondary font-bold text-xl md:text-[25px]">
                                {{ $title }}
                            </h2>
                        @endif

                        @if ($description !== '')
                            <p class="text-theme-heading text-base md:text-lg leading-[30px] mb-8">
                                {{ $description }}
                            </p>
                        @endif

                        @if ($featuresTitle !== '')
                            <p class="font-theme-heading text-theme-heading font-bold text-xl mb-2">
                                {{ $featuresTitle }}
                            </p>
                        @endif

                        @if ($featureItems->isNotEmpty())
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-10">
                                @foreach ($featureItems as $item)
                                    <div class="flex items-center gap-2">
                                        @if ($item['icon_source'] === 'media' && $item['icon_media_url'])
                                            <img
                                                src="{{ $item['icon_media_url'] }}"
                                                alt=""
                                                class="h-5 w-5 shrink-0"
                                                loading="lazy"
                                                aria-hidden="true"
                                            >
                                        @elseif ($item['icon'] !== '')
                                            <i class="{{ $item['icon'] }} text-theme-secondary text-lg" aria-hidden="true"></i>
                                        @else
                                            <span class="h-2 w-2 rounded-full bg-theme-secondary shrink-0" aria-hidden="true"></span>
                                        @endif

                                        @if ($item['text'] !== '')
                                            <span class="text-theme-heading text-base md:text-xl capitalize">
                                                {{ $item['text'] }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($hasButton)
                            <a
                                href="{{ $buttonUrl }}"
                                @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                                class="btn-theme-primary inline-flex items-center justify-center py-3 px-8 text-lg md:text-xl font-medium transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                            >
                                {{ $buttonLabel }}
                            </a>
                        @endif
                    </div>

                    @if ($image)
                        <div class="order-1 lg:order-2 md:col-span-2 h-full">
                            <img
                                src="{{ $image }}"
                                loading="lazy"
                                alt="{{ $alt ?: $title ?: 'Promo image' }}"
                                class="aspect-4/3 md:aspect-auto w-full h-full rounded-theme-xl object-cover shadow-theme"
                            >
                        </div>
                    @endif

                </div>
            </div>
        @endif
    </div>
</section>
