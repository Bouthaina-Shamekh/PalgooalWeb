@php
    // Features stored under 'features' key; text/title/label fallback chain preserves
    // backward compatibility with any data saved before SectionEditorRepeaterFactory normalization.
    $featureItems = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (is_array($item)) {
                $text = trim((string) ($item['text'] ?? ($item['title'] ?? ($item['label'] ?? ''))));
            } elseif (is_scalar($item)) {
                $text = trim((string) $item);
            } else {
                return null;
            }

            return $text !== '' ? ['text' => $text] : null;
        })
        ->filter()
        ->values();

    $bgUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['background_image'] ?? null);
@endphp
<section id="hosting-wordpress" class="bg-[#F8F8F8] py-16 md:py-24 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="animate-from-left order-2 lg:order-1">
                @if (!empty($data['eyebrow']))
                    <p class="text-red-brand font-bold text-lg md:text-xl uppercase mb-4">{{ $data['eyebrow'] }}</p>
                @endif
                @if (!empty($data['title']))
                    <h2 class="text-purple-brand font-extrabold text-2xl md:text-[40px] leading-tight uppercase mb-6 font-theme-heading">
                        {{ $data['title'] }}</h2>
                @endif
                <ul class="space-y-3 mb-8">
                    @foreach ($featureItems as $featureItem)
                        <li class="flex items-center gap-3">
                            <span class="w-3 h-[4px] rounded-2xl bg-red-brand flex-shrink-0"></span>
                            <span
                                class="text-purple-brand text-base md:text-lg capitalize">{{ $featureItem['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
                @if (!empty($data['pricing']))
                    <p class="text-red-brand font-extrabold text-2xl md:text-3xl mb-6">{{ $data['pricing'] }}</p>
                @endif
                @if (!empty($data['button_label']) && !empty($data['button_url']))
                    <a href="{{ $data['button_url'] }}"
                        class="inline-block bg-red-brand text-white py-3 px-8 rounded-xl text-lg md:text-xl hover:bg-red-brand/90 transition-all duration-300 hover:-translate-y-0.5">
                        {{ $data['button_label'] }}
                    </a>
                @endif
            </div>
            <div class="animate-from-right order-1 lg:order-2 h-full">
                @if (!empty($bgUrl))
                    <img src="{{ $bgUrl }}" loading="lazy" alt="{{ $data['image_alt'] ?? '' }}"
                        class="aspect-[3/2] w-full h-full rounded-[36px] object-cover hover:-translate-y-0.5 transition-all duration-300">
                @endif
            </div>
        </div>
    </div>
</section>
