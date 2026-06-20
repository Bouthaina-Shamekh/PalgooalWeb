@php
    use Illuminate\Support\Str;
    use App\Support\Sections\SectionFrontendMediaResolver;

    $section_id = Str::slug(trim((string) ($data['section_id'] ?? 'content-showcase'))) ?: 'content-showcase';

    $eyebrow = trim((string) ($data['eyebrow'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));

    $features = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $title = trim((string) ($item['title'] ?? ''));

            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';

            $icon = trim((string) ($item['icon'] ?? ''));

            $iconMediaUrl = SectionFrontendMediaResolver::resolve($item['icon_media'] ?? null);

            if ($title === '' && $icon === '' && !$iconMediaUrl) {
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

    $highlight_text = trim((string) ($data['highlight_text'] ?? ''));
    $button_label = trim((string) ($data['button_label'] ?? ''));
    $button_url = trim((string) ($data['button_url'] ?? ''));

    $image = SectionFrontendMediaResolver::resolve($data['image'] ?? null);
    $image_alt = trim((string) ($data['image_alt'] ?? $title));
    $image_position = trim((string) ($data['image_position'] ?? 'right'));

    $contentOrder = $image_position === 'left' ? 'order-2 lg:order-2' : 'order-2 lg:order-1';

    $imageOrder = $image_position === 'left' ? 'order-1 lg:order-1' : 'order-1 lg:order-2';

    $background_token = trim((string) ($data['background_token'] ?? 'muted'));

    $backgroundClass = match ($background_token) {
        'primary' => 'bg-theme-primary',
        'secondary' => 'bg-theme-secondary',
        'surface' => 'bg-theme-surface',
        'muted' => 'bg-theme-muted',
        default => '',
    };

    $text_token = trim((string) ($data['text_token'] ?? 'heading'));

    $textClass = match ($text_token) {
        'body' => 'text-theme-body',
        'primary' => 'text-theme-primary',
        'secondary' => 'text-theme-secondary',
        'white' => 'text-white',
        default => 'text-theme-heading',
    };

@endphp

<section id="{{ $section_id }}" class="{{ $backgroundClass }} py-16 md:py-24 px-4 sm:px-6 lg:px-12">
    <div class="container mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="animate-from-left {{ $contentOrder }}">
                @if ($eyebrow)
                    <p class="{{ $textClass }} font-bold text-lg md:text-xl uppercase mb-4">
                        {{ $eyebrow }}
                    </p>
                @endif
                @if ($title)
                    <h2 class="{{ $textClass }} font-extrabold text-2xl md:text-[40px] leading-tight uppercase mb-6">
                        {{ $title }}
                    </h2>
                @endif
                @if ($subtitle)
                    <p class="{{ $textClass }}/80 text-base md:text-lg leading-relaxed mb-6">
                        {{ $subtitle }}
                    </p>
                @endif
                @if ($features->isNotEmpty())
                    <ul class="space-y-3 mb-8">
                        @foreach ($features as $feature)
                            <li class="flex items-center gap-3">

                                @if ($feature['icon_source'] === 'media' && $feature['icon_media_url'])
                                    <img src="{{ $feature['icon_media_url'] }}" alt=""
                                        class="w-5 h-5 object-contain flex-shrink-0">
                                @elseif ($feature['icon'])
                                    <i class="{{ $feature['icon'] }} {{ $textClass }} flex-shrink-0"></i>
                                @else
                                    <span class="w-3 h-[4px] rounded-2xl bg-red-brand flex-shrink-0"></span>
                                @endif

                                <span class="{{ $textClass }} text-base md:text-lg">
                                    {{ $feature['title'] }}
                                </span>

                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($highlight_text)
                    <p class="{{ $textClass }} font-extrabold text-2xl md:text-3xl mb-6">
                        {{ $highlight_text }}
                    </p>
                @endif
                @if ($button_label !== '' && $button_url !== '')
                    <a href="{{ $button_url }}"
                        class="inline-block bg-red-brand text-white py-3 px-8 rounded-xl text-lg md:text-xl hover:bg-red-brand/90 transition-all duration-300 hover:-translate-y-0.5">
                        {{ $button_label }}
                    </a>
                @endif
            </div>
            @if ($image)
                <div class="animate-from-right {{ $imageOrder }} h-full">
                    <img src="{{ $image }}" loading="lazy" alt="{{ $image_alt }}"
                        class="aspect-[3/2] w-full h-full rounded-[36px] object-cover hover:-translate-y-0.5 transition-all duration-300">
                </div>
            @endif
        </div>
    </div>
</section>
