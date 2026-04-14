@php
    $cardItems = collect(is_array($data['items'] ?? null) ? $data['items'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            if ($title === '' && $description === '') {
                return null;
            }
            return [
                'title' => $title,
                'description' => $description,
                'icon_source' => ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class',
                'icon' => trim((string) ($item['icon'] ?? '')),
                'icon_media' => $item['icon_media'] ?? null,
            ];
        })
        ->filter()
        ->values();

    // Resolve media icon URLs eagerly (one query per item at most)
    $resolvedMedia = [];
    foreach ($cardItems as $ci) {
        $mediaId = $ci['icon_media'] ?? null;
        if ($ci['icon_source'] === 'media' && $mediaId && !isset($resolvedMedia[$mediaId])) {
            $media = \App\Models\Media::find($mediaId);
            $resolvedMedia[$mediaId] = $media?->url ?? null;
        }
    }
@endphp
<section id="hosting-protection" class="bg-[#EAEAEA] py-16 md:py-24 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        @if (!empty($data['title']))
            <h2
                class="text-purple-brand font-extrabold text-3xl md:text-4xl lg:text-[40px] text-center uppercase mb-0 animate-from-up">
                {{ $data['title'] }}</h2>
        @endif
        @if (!empty($data['subtitle']))
            <p class="text-[#555] text-base md:text-lg text-center max-w-[800px] mx-auto mb-12 animate-from-up">
                {{ $data['subtitle'] }}</p>
        @endif

        @if ($cardItems->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($cardItems as $card)
                    <div class="animate-from-up bg-white rounded-[20px] p-6 transition-all duration-300">
                        <div class="w-20 h-20 rounded-[20px] bg-[#EAEAEA] flex items-center justify-center mb-4">
                            @if ($card['icon_source'] === 'media' && !empty($card['icon_media']))
                                @php $iconUrl = $resolvedMedia[$card['icon_media']] ?? null; @endphp
                                @if ($iconUrl)
                                    <img src="{{ $iconUrl }}" alt="" class="w-11 h-11 object-contain">
                                @else
                                    <i class="ti ti-shield-check text-3xl text-red-brand" aria-hidden="true"></i>
                                @endif
                            @elseif (!empty($card['icon']))
                                <i class="{{ $card['icon'] }} text-3xl text-red-brand" aria-hidden="true"></i>
                            @else
                                <i class="ti ti-shield-check text-3xl text-red-brand" aria-hidden="true"></i>
                            @endif
                        </div>
                        @if ($card['title'] !== '')
                            <h3 class="text-purple-brand font-bold text-lg md:text-xl mb-2">{{ $card['title'] }}</h3>
                        @endif
                        @if ($card['description'] !== '')
                            <p class="text-[#626262] text-base md:text-xl leading-relaxed">{{ $card['description'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
