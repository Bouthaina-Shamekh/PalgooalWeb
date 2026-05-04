@php
    $brandPrefix = trim((string) ($data['brand_prefix'] ?? ''));
    $brandSuffix = trim((string) ($data['brand_suffix'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $buttonLabel = trim((string) data_get($data, 'button_label', ''));
    $buttonUrl = trim((string) data_get($data, 'button_url', ''));
    $buttonNewTab = (bool) data_get($data, 'button_new_tab', false);
    $galleryImages = collect(is_array($data['gallery_images'] ?? null) ? $data['gallery_images'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $image = $item['image'] ?? null;
            $imageUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($image);
            $alt = trim((string) ($item['alt'] ?? ''));

            if (!$imageUrl) {
                return null;
            }

            return [
                'url' => $imageUrl,
                'alt' => $alt,
            ];
        })
        ->filter()
        ->values();

    $layoutDirection = ($data['layout_direction'] ?? 'image-left') === 'image-right' ? 'image-right' : 'image-left';
    // $layoutClass = $layoutDirection === 'image-right' ? 'lg:flex-row-reverse' : 'lg:flex-row';
@endphp

<section id="mobile-app" class="py-16 lg:py-24 px-4 sm:px-6 lg:px-12 bg-gray-50 overflow-hidden">
    <div class="container mx-auto">
        <div @class([
            'flex flex-col-reverse items-center gap-6 lg:gap-14',
            'lg:flex-row-reverse' => $layoutDirection === 'image-right',
            'lg:flex-row' => $layoutDirection !== 'image-right',
        ])>

            <!-- Images Block (Start Side) -->
            <div class="w-full lg:w-1/2">
                @if ($galleryImages->isNotEmpty())
                    <div class="grid grid-cols-3 gap-4 lg:gap-6 h-[200px] sm:h-[250px] lg:h-[320px]">
                        @foreach ($galleryImages->take(3) as $index => $galleryImage)
                            <div
                                class="rounded-2xl overflow-hidden shadow-lg transform hover:-translate-y-2 transition-transform duration-500 h-full bg-gray-200 {{ $index === 1 ? 'delay-100' : '' }} {{ $index === 2 ? 'delay-200' : '' }}">
                                <img src="{{ $galleryImage['url'] }}" class="w-full h-full object-cover"
                                    alt="{{ $galleryImage['alt'] ?: $title ?: 'Service gallery image' }}" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Text Content (End Side) -->
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
                    <h2 class="text-purple-brand font-extrabold text-4xl md:text-[40px] mb-2 uppercase leading-tight font-theme-heading">
                        {{ $title }}
                    </h2>
                @endif

                <!-- Description -->
                @if ($description !== '')
                    <p class="text-gray-dark text-lg md:text-xl leading-relaxed mb-4">
                        {{ $description }}
                    </p>
                @endif
                <!-- CTA Button -->
                @if ($buttonLabel !== '' && $buttonUrl !== '')
                    <a href="{{ $buttonUrl }}"
                        @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                        class="bg-red-brand text-white md:px-10 md:py-4 px-6 py-3 rounded-xl text-lg md:text-xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        {{ $buttonLabel }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
