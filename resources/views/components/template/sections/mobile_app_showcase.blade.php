@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-16 lg:py-24';

    $brandPrefix = $content['brand_prefix'] ?? 'PAL';
    $brandSuffix = $content['brand_suffix'] ?? 'GOALS';
    $sectionTitle = $content['title'] ?? '';
    $sectionDescription = $content['description'] ?? '';

    $primaryButton = is_array($content['primary_button'] ?? null) ? $content['primary_button'] : [];
    $primaryLabel = $primaryButton['label'] ?? null;
    $primaryUrl = $primaryButton['url'] ?? null;

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

    $imageItems = collect([
        [
            'url' => $resolveMediaUrl($content['image_one'] ?? null),
            'alt' => __('Mobile App 1'),
        ],
        [
            'url' => $resolveMediaUrl($content['image_two'] ?? null),
            'alt' => __('Mobile App 2'),
        ],
        [
            'url' => $resolveMediaUrl($content['image_three'] ?? null),
            'alt' => __('Mobile App 3'),
        ],
    ]);
@endphp

<section id="mobile-app" class="{{ $paddingY }} overflow-hidden bg-gray-50 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="flex flex-col-reverse items-center gap-6 lg:flex-row lg:gap-14">
            <div class="w-full lg:w-1/2">
                <div class="grid h-[200px] grid-cols-3 gap-4 sm:h-[250px] lg:h-[320px] lg:gap-6">
                    @foreach ($imageItems as $index => $imageItem)
                        <div class="h-full overflow-hidden rounded-2xl bg-gray-200 shadow-lg transition-transform duration-500 hover:-translate-y-2 {{ $index === 1 ? 'delay-100' : ($index === 2 ? 'delay-200' : '') }}">
                            @if ($imageItem['url'])
                                <img src="{{ $imageItem['url'] }}" class="h-full w-full object-cover" alt="{{ $imageItem['alt'] }}" loading="lazy">
                            @else
                                <div class="flex h-full items-center justify-center px-4 text-center text-sm text-slate-500">
                                    {{ __('Choose an image from the section editor.') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex w-full flex-col items-center text-center ltr:lg:items-start ltr:lg:text-left rtl:lg:items-start rtl:lg:text-right lg:w-1/2">
                <p class="text-lg md:text-xl">
                    <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
                </p>

                @if ($sectionTitle)
                    <h2 class="mb-2 text-4xl font-extrabold uppercase leading-tight text-purple-brand md:text-[40px]">
                        {{ $sectionTitle }}
                    </h2>
                @endif

                @if ($sectionDescription)
                    <p class="mb-4 text-lg leading-relaxed text-gray-dark md:text-xl">
                        {{ $sectionDescription }}
                    </p>
                @endif

                @if ($primaryLabel && $primaryUrl)
                    <a
                        href="{{ $primaryUrl }}"
                        class="inline-flex items-center justify-center rounded-xl bg-red-brand px-6 py-3 text-lg text-white shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl md:px-10 md:py-4 md:text-xl"
                    >
                        {{ $primaryLabel }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
