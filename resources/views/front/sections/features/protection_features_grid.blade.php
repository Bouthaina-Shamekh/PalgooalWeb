@php
use App\Support\Sections\SectionFrontendMediaResolver;
    $section_id = trim((string) ($data['section_id'] ?? '')); // text / trans
    $eyebrow = trim((string) ($data['eyebrow'] ?? '')); // text / trans
    $title = trim((string) ($data['title'] ?? '')); // text / trans
    $subtitle = (string) ($data['subtitle'] ?? ''); // textarea / trans
    $background_token = trim((string) ($data['background_token'] ?? 'muted')); // design-token / shared
    $backgroundClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('background_token', $background_token);

    $text_token = trim((string) ($data['text_token'] ?? 'heading')); // design-token / shared
    $textClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('text_token', $text_token);

    $protection_items = collect(is_array($data['protection_items'] ?? null) ? $data['protection_items'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));
            $iconMediaUrl = SectionFrontendMediaResolver::resolve($item['icon_media'] ?? null);

            if ($title === '' && $description === '' && $icon === '' && !$iconMediaUrl) {
                return null;
            }

            return [
                'title' => $title,
                'description' => $description,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media_url' => $iconMediaUrl,
            ];
        })
        ->filter()
        ->values();
@endphp

<section id="{{ $section_id }}" class="{{ $backgroundClass }} py-16 md:py-24 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">

        {{-- Intro --}}
        @if ($eyebrow)
            <span class="section-eyebrow">{{ $eyebrow }}</span>
        @endif
        @if ($title)
            <h2
                class="text-purple-brand font-extrabold text-3xl md:text-4xl lg:text-[40px] text-center uppercase mb-0 animate-from-up mb-4">
                {{ $title }}
            </h2>
        @endif
        @if ($subtitle)
            <p class="text-[#555] text-base md:text-lg text-center max-w-[800px] mx-auto mb-12 animate-from-up">
                {{ $subtitle }}
            </p>
        @endif
        {{-- protection_items / repeater --}}
        @if (!empty($protection_items))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($protection_items as $protection_item)
                    <div class="animate-from-left bg-white rounded-[20px] p-6 transition-all duration-300">
                        <div class="w-20 h-20 rounded-[20px] bg-[#EAEAEA] flex items-center justify-center mb-4">
                            @if ($protection_item['icon_source'] === 'media' && $protection_item['icon_media_url'])
                                <img src="{{ $protection_item['icon_media_url'] }}" alt=""
                                    class="h-5 w-5 object-contain flex-shrink-0">
                            @elseif ($protection_item['icon'])
                                <i class="{{ $protection_item['icon'] }} text-red-brand text-4xl"></i>
                            @else
                                <svg class="w-11 h-11 text-red-brand" viewBox="0 0 44 44" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M15.0867 6.41871C16.1497 6.21974 17.2155 6.7505 17.6793 7.7098C17.7947 7.88387 17.8871 8.07167 17.9542 8.26862C18.2489 12.8164 18.5631 17.287 18.8577 21.7577C18.851 22.2225 18.9241 22.6848 19.0738 23.1258C19.4263 23.9933 20.3034 24.5438 21.254 24.4941L35.6901 23.5691L35.7884 23.6075L36.0519 23.6266C36.5737 23.6934 37.0602 23.9315 37.4298 24.3063C37.8607 24.7437 38.0969 25.3311 38.0867 25.9392C37.5176 34.2288 31.4401 41.155 23.1694 42.9393C14.8986 44.7238 6.42016 40.9379 2.35923 33.6473C1.16514 31.5474 0.410569 29.2348 0.139791 26.8449C0.0347694 26.1368 -0.0112121 25.4215 0.00230329 24.706C0.029675 15.8913 6.30545 8.28298 15.0867 6.41871ZM23.6697 0.00178908C33.5831 0.298553 41.8683 7.4951 43.3698 17.1136C43.3793 17.171 43.3793 17.2296 43.3698 17.287L43.3672 17.5585C43.3344 17.9175 43.1872 18.2598 42.9423 18.5346C42.6365 18.8782 42.2036 19.088 41.7397 19.1177L25.084 20.216L24.8097 20.2215C24.3549 20.1993 23.9189 20.0225 23.5793 19.7168C23.1718 19.35 22.9408 18.8316 22.9432 18.289L21.8236 1.92879V1.65901C21.844 1.19889 22.05 0.76561 22.3964 0.454727C22.7428 0.143843 23.2008 -0.0191144 23.6697 0.00178908Z"
                                        fill="#BA112C" />
                                </svg>
                            @endif
                        </div>
                        <h3 class="text-purple-brand font-bold text-lg md:text-xl mb-2">
                            {{ $protection_item['title'] ?? '' }}</h3>
                        <p class="text-[#626262] text-base md:text-xl leading-relaxed">
                            {{ $protection_item['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>
