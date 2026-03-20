@php
    $style = is_array($section->style ?? null) ? $section->style : [];
    $paddingY = $style['padding_y'] ?? 'py-16 lg:py-24';

    $brandPrefix = $content['brand_prefix'] ?? 'PAL';
    $brandSuffix = $content['brand_suffix'] ?? 'GOALS';
    $sectionTitle = $content['title'] ?? '';

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

    $sanitizeIconClass = static function ($value): ?string {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return $value !== '' ? $value : null;
    };

    $serviceItems = collect(is_array($content['services'] ?? null) ? $content['services'] : [])
        ->map(function ($item) use ($sanitizeIconClass, $resolveMediaUrl) {
            if (is_scalar($item)) {
                $text = trim((string) $item);

                return $text !== '' ? ['text' => $text, 'icon_source' => 'class', 'icon' => null, 'icon_media_url' => null] : null;
            }

            if (! is_array($item)) {
                return null;
            }

            $text = trim((string) ($item['text'] ?? $item['title'] ?? $item['label'] ?? ''));
            if ($text === '') {
                return null;
            }

            $iconSource = in_array(($item['icon_source'] ?? 'class'), ['class', 'media'], true)
                ? (string) ($item['icon_source'] ?? 'class')
                : 'class';

            return [
                'text' => $text,
                'icon_source' => $iconSource,
                'icon' => $sanitizeIconClass($item['icon'] ?? null),
                'icon_media_url' => $resolveMediaUrl($item['icon_media'] ?? null),
            ];
        })
        ->filter()
        ->values();

    $imageOneUrl = $resolveMediaUrl($content['image_one'] ?? null);
    $imageTwoUrl = $resolveMediaUrl($content['image_two'] ?? null);
@endphp

<section id="digital-marketing" class="{{ $paddingY }} overflow-hidden bg-gray-50 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="flex flex-col-reverse gap-12 lg:flex-row lg:items-stretch lg:gap-24">
            <div class="w-full lg:w-1/2">
                <div class="grid h-[200px] grid-cols-3 gap-4 sm:h-[250px] lg:h-full lg:gap-6">
                    <div class="col-span-2 h-full overflow-hidden rounded-2xl bg-gray-200 shadow-lg transition-transform duration-500 hover:-translate-y-2">
                        @if ($imageOneUrl)
                            <img src="{{ $imageOneUrl }}" class="h-full w-full object-cover" alt="{{ __('Marketing Image 1') }}" loading="lazy">
                        @else
                            <div class="flex h-full items-center justify-center px-4 text-center text-sm text-slate-500">
                                {{ __('Choose an image from the section editor.') }}
                            </div>
                        @endif
                    </div>

                    <div class="col-span-1 h-full overflow-hidden rounded-2xl bg-gray-200 shadow-lg delay-100 transition-transform duration-500 hover:-translate-y-2">
                        @if ($imageTwoUrl)
                            <img src="{{ $imageTwoUrl }}" class="h-full w-full object-cover" alt="{{ __('Marketing Image 2') }}" loading="lazy">
                        @else
                            <div class="flex h-full items-center justify-center px-4 text-center text-sm text-slate-500">
                                {{ __('Choose an image from the section editor.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex w-full flex-col items-center text-center ltr:lg:items-start ltr:lg:text-left rtl:lg:items-start rtl:lg:text-right lg:w-1/2">
                <p class="inline-flex items-center gap-1 text-lg md:text-xl">
                    <span class="text-red-brand">{{ $brandPrefix }}</span>
                    <span class="text-purple-brand">{{ $brandSuffix }}</span>
                </p>

                @if ($sectionTitle)
                    <h2 class="mb-4 text-3xl font-extrabold uppercase leading-tight text-purple-brand md:text-[40px]">
                        {{ $sectionTitle }}
                    </h2>
                @endif

                @if ($serviceItems->isNotEmpty())
                    <ul class="mb-10 inline-block w-full max-w-lg space-y-2">
                        @foreach ($serviceItems as $serviceItem)
                            <li class="flex items-start justify-start gap-3 text-lg text-purple-brand md:text-xl">
                                @if (($serviceItem['icon_source'] ?? 'class') === 'media' && ! empty($serviceItem['icon_media_url']))
                                    <span class="mt-1 flex h-5 w-5 flex-shrink-0 items-center justify-center text-red-brand">
                                        <img src="{{ $serviceItem['icon_media_url'] }}" alt="" class="h-5 w-5 object-contain">
                                    </span>
                                @elseif (! empty($serviceItem['icon']))
                                    <span class="mt-1 flex h-5 w-5 flex-shrink-0 items-center justify-center text-red-brand">
                                        <i class="{{ $serviceItem['icon'] }} text-base leading-none" aria-hidden="true"></i>
                                    </span>
                                @else
                                    <span class="mt-1 transform text-xl text-red-brand rtl:rotate-180">
                                        <svg width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M9.75 6.49512L0 12.9903V-7.34329e-05L9.75 6.49512Z" fill="#BA112C" />
                                        </svg>
                                    </span>
                                @endif
                                <span class="max-w-max flex-1 ltr:text-left rtl:text-right">{{ $serviceItem['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($primaryLabel && $primaryUrl)
                    <a
                        href="{{ $primaryUrl }}"
                        class="inline-flex items-center justify-center rounded-xl bg-red-brand px-6 py-3 text-lg text-white shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl md:px-14 md:py-4 md:text-xl"
                    >
                        {{ $primaryLabel }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
