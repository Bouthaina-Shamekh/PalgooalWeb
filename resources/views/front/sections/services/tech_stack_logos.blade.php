@php
    $sectionId = trim((string) ($data['section_id'] ?? 'tech-stack'));

    $backgroundClass = match ($data['background_variant'] ?? 'white') {
        'gray'           => 'bg-gray-50 bg-theme-muted',
        'purple'         => 'bg-purple-brand bg-theme-primary',
        'red'            => 'bg-red-brand bg-theme-secondary',
        'dark'           => 'bg-gray-950',
        'gradient_brand' => 'bg-gradient-to-br from-purple-brand to-red-brand',
        'transparent'    => 'bg-transparent',
        default          => 'bg-white bg-theme-surface',
    };

    $items = collect(is_array($data['items'] ?? null) ? $data['items'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $imageUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($item['image'] ?? null);

            $alt = trim((string) ($item['alt'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            $newTab = filter_var($item['new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$imageUrl) {
                return null;
            }

            return [
                'image_url' => $imageUrl,
                'alt' => $alt,
                'url' => $url,
                'new_tab' => $newTab,
            ];
        })
        ->filter()
        ->values();

    $shouldRender = $items->isNotEmpty();
@endphp

@if ($shouldRender)
    <section id="{{ $sectionId }}" class="py-12 relative px-4 sm:px-6 lg:px-12 overflow-hidden {{ $backgroundClass }}">
        <div class="container mx-auto">

            <div id="{{ $sectionId }}-container"
                class="flex items-center justify-start overflow-x-auto py-2 pb-6 gap-4 lg:gap-6 cursor-grab scrollbar-hide select-none">

                @foreach ($items as $item)
                    @php
                        $wrapperTag = $item['url'] !== '' ? 'a' : 'div';
                    @endphp

                    <<?= $wrapperTag ?> @if ($wrapperTag === 'a')
                        href="{{ $item['url'] }}"
                        @if ($item['new_tab'])
                            target="_blank"
                            rel="noopener noreferrer"
                        @endif
                @endif

                class="shrink-0 hover:-translate-y-1 transition-all duration-300 group relative"
                >

                <div class="absolute inset-0 z-10"></div>

                <img src="{{ $item['image_url'] }}" class="w-full h-full object-cover"
                    alt="{{ $item['alt'] ?: 'Technology logo' }}" loading="lazy" draggable="false">

                </<?= $wrapperTag ?>>
@endforeach

</div>

</div>
</section>
@endif
<script
    src="{{ asset('assets/tamplate/js/tech-stack-drag.js') }}?v={{ filemtime(public_path('assets/tamplate/js/tech-stack-drag.js')) }}"
    defer></script>
