@php
    $sectionId = trim((string) ($data['section_id'] ?? 'mobile-app'));
    $brandPrefix = trim((string) ($data['brand_prefix'] ?? ''));
    $brandSuffix = trim((string) ($data['brand_suffix'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));

    $buttonLabel = trim((string) ($data['button_label'] ?? ''));
    $buttonUrl = trim((string) ($data['button_url'] ?? ''));
    $buttonNewTab = filter_var($data['button_new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $hasButton = $buttonLabel !== '' && $buttonUrl !== '';

    $galleryImages = collect(is_array($data['gallery_images'] ?? null) ? $data['gallery_images'] : [])
        ->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $imageUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($item['image'] ?? null);

            if (! $imageUrl) {
                return null;
            }

            return [
                'url' => $imageUrl,
                'alt' => trim((string) ($item['alt'] ?? '')),
            ];
        })
        ->filter()
        ->values();

    $layoutDirection = ($data['layout_direction'] ?? 'image-left') === 'image-right'
        ? 'image-right'
        : 'image-left';

    $shouldRender = $brandPrefix !== ''
        || $brandSuffix !== ''
        || $title !== ''
        || $description !== ''
        || $galleryImages->isNotEmpty()
        || $hasButton;
@endphp

@if ($shouldRender)
    <section id="{{ $sectionId }}" class="bg-theme-muted font-theme-body py-16 lg:py-24 px-4 sm:px-6 lg:px-12 overflow-hidden">
        <div class="container mx-auto">
            <div @class([
                'flex flex-col-reverse items-center gap-6 lg:gap-14',
                'lg:flex-row-reverse' => $layoutDirection === 'image-right',
                'lg:flex-row' => $layoutDirection !== 'image-right',
            ])>

                @if ($galleryImages->isNotEmpty())
                    <div class="w-full lg:w-1/2">
                        <div class="grid grid-cols-3 gap-4 lg:gap-6 h-[200px] sm:h-[250px] lg:h-[320px]">
                            @foreach ($galleryImages->take(3) as $index => $galleryImage)
                                <div @class([
                                    'rounded-theme-lg overflow-hidden shadow-theme transform hover:-translate-y-2 transition-transform duration-500 h-full bg-theme-surface',
                                    'delay-100' => $index === 1,
                                    'delay-200' => $index === 2,
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
                @endif

                <div class="w-full lg:w-1/2 flex flex-col items-center lg:items-start text-center ltr:lg:text-left rtl:lg:text-right">

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
                        <h2 class="font-theme-heading text-theme-heading font-extrabold text-4xl md:text-[40px] mb-2 uppercase leading-tight">
                            {{ $title }}
                        </h2>
                    @endif

                    @if ($description !== '')
                        <p class="text-theme-body text-lg md:text-xl leading-relaxed mb-4">
                            {{ $description }}
                        </p>
                    @endif

                    @if ($hasButton)
                        <a
                            href="{{ $buttonUrl }}"
                            @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                            class="btn-theme-primary inline-flex items-center justify-center md:px-10 md:py-4 px-6 py-3 text-lg md:text-xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                        >
                            {{ $buttonLabel }}
                        </a>
                    @endif

                </div>
            </div>
        </div>
    </section>
@endif