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

    $serviceItems = collect(is_array($content['services'] ?? null) ? $content['services'] : [])
        ->map(fn ($item) => is_scalar($item) ? trim((string) $item) : '')
        ->filter()
        ->values();

    $imageItems = collect([
        ['url' => $resolveMediaUrl($content['image_one'] ?? null), 'alt' => __('Design Portfolio 1'), 'class' => 'col-span-1 md:col-span-1'],
        ['url' => $resolveMediaUrl($content['image_two'] ?? null), 'alt' => __('Design Portfolio 2'), 'class' => 'col-span-1 md:col-span-1'],
        ['url' => $resolveMediaUrl($content['image_three'] ?? null), 'alt' => __('Design Portfolio 3'), 'class' => 'col-span-2 md:col-span-2'],
        ['url' => $resolveMediaUrl($content['image_four'] ?? null), 'alt' => __('Design Portfolio 4'), 'class' => 'col-span-2 md:col-span-2'],
        ['url' => $resolveMediaUrl($content['image_five'] ?? null), 'alt' => __('Design Portfolio 5'), 'class' => 'col-span-1 md:col-span-1'],
        ['url' => $resolveMediaUrl($content['image_six'] ?? null), 'alt' => __('Design Portfolio 6'), 'class' => 'col-span-1 md:col-span-1'],
    ]);
@endphp

<section id="design" class="{{ $paddingY }} overflow-hidden bg-white px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-20">
            <div class="flex w-full flex-col items-center text-center ltr:lg:items-start ltr:lg:text-left rtl:lg:items-start rtl:lg:text-right lg:w-1/3">
                <p class="text-lg md:text-xl">
                    <span class="text-red-brand">{{ $brandPrefix }}</span><span class="text-purple-brand">{{ $brandSuffix }}</span>
                </p>

                @if ($sectionTitle)
                    <h2 class="mb-0 text-4xl font-extrabold uppercase leading-tight text-purple-brand md:text-[40px]">
                        {{ $sectionTitle }}
                    </h2>
                @endif

                @if ($sectionDescription)
                    <p class="mb-4 text-lg leading-relaxed text-gray-dark">
                        {{ $sectionDescription }}
                    </p>
                @endif

                @if ($serviceItems->isNotEmpty())
                    <ul class="mb-10 inline-block w-full space-y-2">
                        @foreach ($serviceItems as $serviceItem)
                            <li class="flex items-center justify-start gap-3 text-lg text-purple-brand transition-colors duration-300 hover:text-red-brand md:text-xl">
                                <span class="text-sm text-red-brand rtl:rotate-180">
                                    <svg width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="#BA112C" />
                                    </svg>
                                </span>
                                <span>{{ $serviceItem }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($primaryLabel && $primaryUrl)
                    <a
                        href="{{ $primaryUrl }}"
                        class="inline-flex items-center justify-center rounded-xl bg-red-brand px-14 py-4 text-lg text-white shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl md:text-xl"
                    >
                        {{ $primaryLabel }}
                    </a>
                @endif
            </div>

            <div class="w-full lg:w-2/3">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($imageItems as $imageItem)
                        <div class="{{ $imageItem['class'] }} h-48 overflow-hidden rounded-2xl shadow-lg group md:h-64">
                            @if ($imageItem['url'])
                                <img
                                    src="{{ $imageItem['url'] }}"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                    alt="{{ $imageItem['alt'] }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full items-center justify-center bg-slate-100 px-4 text-center text-sm text-slate-500">
                                    {{ __('Choose an image from the section editor.') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
