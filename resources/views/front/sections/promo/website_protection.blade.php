@php
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));

    $cardItems = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));
            $iconMedia = $item['icon_media'] ?? null;

            if ($title === '' && $description === '' && $icon === '' && empty($iconMedia)) {
                return null;
            }

            return [
                'title' => $title,
                'description' => $description,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media' => $iconMedia,
            ];
        })
        ->filter()
        ->values();

    $resolvedMedia = \App\Support\Sections\SectionFrontendMediaResolver::resolveMany($cardItems->pluck('icon_media'));
@endphp

<section id="website-protection" class="bg-[#EAEAEA] py-16 md:py-24 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">

        @if ($title !== '')
            <h2
                class="text-purple-brand font-extrabold text-3xl md:text-4xl lg:text-[40px] text-center uppercase mb-4 animate-from-up font-theme-heading">
                {{ $title }}
            </h2>
        @endif

        @if ($subtitle !== '')
            <p class="text-[#555] text-base md:text-lg text-center max-w-[800px] mx-auto mb-12 animate-from-up">
                {{ $subtitle }}
            </p>
        @endif

        @if ($cardItems->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">

                @foreach ($cardItems as $card)
                    @php
                        $iconUrl =
                            $card['icon_source'] === 'media' && !empty($card['icon_media'])
                                ? $resolvedMedia[$card['icon_media']] ?? null
                                : null;
                    @endphp

                    <div class="bg-white rounded-[20px] p-6 transition-all duration-300">
                        <div class="w-20 h-20 rounded-[20px] bg-[#EAEAEA] flex items-center justify-center mb-4">

                            @if ($iconUrl)
                                <img src="{{ $iconUrl }}" alt="" class="w-11 h-11 object-contain">
                            @elseif (!empty($card['icon']))
                                <i class="{{ $card['icon'] }} text-red-brand text-3xl" aria-hidden="true"></i>
                            @else
                                <i class="ti ti-shield-check text-red-brand text-3xl" aria-hidden="true"></i>
                            @endif

                        </div>

                        @if ($card['title'] !== '')
                            <h3 class="text-purple-brand font-bold text-lg md:text-xl mb-2 font-theme-heading">
                                {{ $card['title'] }}
                            </h3>
                        @endif

                        @if ($card['description'] !== '')
                            <p class="text-[#626262] text-base md:text-lg leading-relaxed">
                                {{ $card['description'] }}
                            </p>
                        @endif
                    </div>
                @endforeach

            </div>
        @endif

    </div>
</section>
