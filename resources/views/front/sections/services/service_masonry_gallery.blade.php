@php
    $sectionId = trim((string) ($data['section_id'] ?? 'design'));
    $brandPrefix = trim((string) ($data['brand_prefix'] ?? ''));
    $brandSuffix = trim((string) ($data['brand_suffix'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));

    $galleryLayout = match ($data['gallery_layout'] ?? 'masonry_a') {
        'split_two', 'masonry_b' => 'split_two',
        default => 'masonry_a',
    };

    $backgroundClass = match ($data['background_variant'] ?? 'white') {
        'gray' => 'bg-theme-muted',
        default => 'bg-theme-surface',
    };

    $services = collect(is_array($data['services'] ?? null) ? $data['services'] : [])
        ->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));

            $iconMediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve(
                $item['icon_media'] ?? null
            );

            if ($title === '' && $icon === '' && ! $iconMediaUrl) {
                return null;
            }

            return [
                'title' => $title,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media_url' => $iconMediaUrl,
            ];
        })
        ->filter()
        ->values();

    $galleryImages = collect(is_array($data['gallery_images'] ?? null) ? $data['gallery_images'] : [])
        ->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $imageUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($item['image'] ?? null);
            $alt = trim((string) ($item['alt'] ?? ''));

            $gridSpan = match ($item['grid_span'] ?? '1x1') {
                '2x1' => '2x1',
                '1x2' => '1x2',
                '2x2' => '2x2',
                default => '1x1',
            };

            if (! $imageUrl) {
                return null;
            }

            return [
                'url' => $imageUrl,
                'alt' => $alt,
                'grid_span' => $gridSpan,
            ];
        })
        ->filter()
        ->values();

    $buttonLabel = trim((string) ($data['button_label'] ?? ''));
    $buttonUrl = trim((string) ($data['button_url'] ?? ''));
    $buttonNewTab = filter_var($data['button_new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $hasButton = $buttonLabel !== '' && $buttonUrl !== '';

    $shouldRender = $brandPrefix !== ''
        || $brandSuffix !== ''
        || $title !== ''
        || $description !== ''
        || $services->isNotEmpty()
        || $galleryImages->isNotEmpty()
        || $hasButton;
@endphp

@if ($shouldRender)
    <section id="{{ $sectionId }}" class="{{ $backgroundClass }} font-theme-body py-16 lg:py-24 px-4 sm:px-6 lg:px-12 overflow-hidden">
        <div class="container mx-auto">
            <div @class([
                'flex gap-12',
                'flex-col lg:flex-row items-center lg:gap-20' => $galleryLayout === 'masonry_a',
                'flex-col-reverse lg:flex-row lg:items-stretch lg:gap-24' => $galleryLayout === 'split_two',
            ])>

                <div @class([
                    'w-full flex flex-col items-center text-center',
                    'lg:w-1/3 lg:items-start ltr:lg:text-left rtl:lg:text-right' => $galleryLayout === 'masonry_a',
                    'lg:w-1/2 lg:items-start ltr:lg:text-left rtl:lg:text-right' => $galleryLayout === 'split_two',
                ])>

                    @if ($brandPrefix !== '' || $brandSuffix !== '')
                        <p class="text-lg md:text-xl">
                            @if ($brandPrefix !== '')
                                <span class="text-theme-secondary">{{ $brandPrefix }}</span>
                            @endif

                            @if ($brandSuffix !== '')
                                <span class="text-theme-primary">{{ $brandSuffix }}</span>
                            @endif
                        </p>
                    @endif

                    @if ($title !== '')
                        <h2 @class([
                            'font-theme-heading text-theme-heading font-extrabold uppercase leading-tight',
                            'text-4xl md:text-[40px] mb-4' => $galleryLayout === 'masonry_a',
                            'text-3xl md:text-[40px] mb-4' => $galleryLayout === 'split_two',
                        ])>
                            {{ $title }}
                        </h2>
                    @endif

                    @if ($description !== '')
                        <p class="text-theme-body text-lg leading-relaxed mb-4">
                            {{ $description }}
                        </p>
                    @endif

                    @if ($services->isNotEmpty())
                        <ul @class([
                            'space-y-2 mb-10 w-full',
                            'max-w-lg inline-block' => $galleryLayout === 'split_two',
                        ])>
                            @foreach ($services as $service)
                                <li @class([
                                    'flex gap-3 text-lg md:text-xl text-theme-heading',
                                    'items-center justify-start hover:text-theme-secondary transition-colors duration-300' => $galleryLayout === 'masonry_a',
                                    'items-start justify-start' => $galleryLayout === 'split_two',
                                ])>
                                    @if ($service['icon_source'] === 'media' && $service['icon_media_url'])
                                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center md:h-6 md:w-6" aria-hidden="true">
                                            <img
                                                src="{{ $service['icon_media_url'] }}"
                                                alt=""
                                                class="h-full w-full object-contain"
                                                loading="lazy"
                                            >
                                        </span>
                                    @elseif ($service['icon'] !== '')
                                        <i class="{{ $service['icon'] }} text-theme-secondary text-xl md:text-2xl" aria-hidden="true"></i>
                                    @else
                                        <span @class([
                                            'text-theme-secondary rtl:rotate-180',
                                            'text-sm' => $galleryLayout === 'masonry_a',
                                            'text-xl mt-1 transform' => $galleryLayout === 'split_two',
                                        ]) aria-hidden="true">
                                            <svg width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="currentColor" />
                                            </svg>
                                        </span>
                                    @endif

                                    <span @class([
                                        'flex-1 ltr:text-left rtl:text-right max-w-max' => $galleryLayout === 'split_two',
                                    ])>
                                        {{ $service['title'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($hasButton)
                        <a
                            href="{{ $buttonUrl }}"
                            @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                            @class([
                                'btn-theme-primary inline-flex items-center justify-center text-lg md:text-xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300',
                                'px-14 py-4' => $galleryLayout === 'masonry_a',
                                'md:px-14 md:py-4 px-6 py-3' => $galleryLayout === 'split_two',
                            ])
                        >
                            {{ $buttonLabel }}
                        </a>
                    @endif
                </div>

                @if ($galleryImages->isNotEmpty())
                    @if ($galleryLayout === 'split_two')
                        <div class="w-full lg:w-1/2">
                            <div class="grid grid-cols-3 gap-4 lg:gap-6 h-[200px] sm:h-[250px] lg:h-full">
                                @foreach ($galleryImages->take(2) as $index => $galleryImage)
                                    <div @class([
                                        'rounded-theme-lg overflow-hidden shadow-theme transform hover:-translate-y-2 transition-transform duration-500 h-full bg-theme-muted',
                                        'col-span-2' => $index === 0,
                                        'col-span-1 delay-100' => $index === 1,
                                    ])>
                                        <img
                                            src="{{ $galleryImage['url'] }}"
                                            class="w-full h-full object-cover"
                                            alt="{{ $galleryImage['alt'] ?: $title ?: 'Service gallery image' }}"
                                            loading="lazy"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="w-full lg:w-2/3">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 auto-rows-[260px]">
                                @foreach ($galleryImages as $galleryImage)
                                    <div @class([
                                        'rounded-theme-lg overflow-hidden shadow-theme group h-full bg-theme-muted',
                                        'md:col-span-2' => $galleryImage['grid_span'] === '2x1',
                                        'row-span-2' => $galleryImage['grid_span'] === '1x2',
                                        'md:col-span-2 row-span-2' => $galleryImage['grid_span'] === '2x2',
                                        'col-span-1' => $galleryImage['grid_span'] === '1x1',
                                    ])>
                                        <img
                                            src="{{ $galleryImage['url'] }}"
                                            class="w-full h-full object-cover transform transition-transform duration-700 group-hover:scale-110"
                                            alt="{{ $galleryImage['alt'] ?: $title ?: 'Service gallery image' }}"
                                            loading="lazy"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

            </div>
        </div>
    </section>
@endif