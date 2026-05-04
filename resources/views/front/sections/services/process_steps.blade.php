@php
    $sectionId = trim((string) ($data['section_id'] ?? 'how-we-build'));
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));

    $steps = collect(is_array($data['steps'] ?? null) ? $data['steps'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));
            $iconMediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($item['icon_media'] ?? null);
            $isFeatured = filter_var($item['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $showConnector = filter_var($item['show_connector'] ?? true, FILTER_VALIDATE_BOOLEAN);

            if ($title === '' && $icon === '' && !$iconMediaUrl) {
                return null;
            }

            return [
                'title' => $title,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media_url' => $iconMediaUrl,
                'is_featured' => $isFeatured,
                'show_connector' => $showConnector,
            ];
        })
        ->filter()
        ->values();

    $gridClass = match (min($steps->count(), 5)) {
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        default => 'md:grid-cols-5',
    };
@endphp

@if ($title !== '' || $subtitle !== '' || $steps->isNotEmpty())
    <section id="{{ $sectionId }}"
        class="bg-purple-brand py-16 lg:py-24 px-4 sm:px-6 lg:px-12 text-center text-white overflow-hidden">
        <div class="container mx-auto">

            @if ($title !== '' || $subtitle !== '')
                <div class="mb-16">
                    @if ($title !== '')
                        <h2 class="text-2xl md:text-3xl font-extrabold mb-2 font-theme-heading">
                            {{ $title }}
                        </h2>
                    @endif

                    @if ($subtitle !== '')
                        <p class="text-gray-300 text-lg md:text-xl font-light opacity-90">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            @endif

            @if ($steps->isNotEmpty())
                <div class="grid grid-cols-1 {{ $gridClass }} gap-12 md:gap-10 relative max-w-7xl mx-auto">
                    @foreach ($steps as $step)
                        <div @class([
                            'relative rounded-2xl p-6 flex flex-col items-center justify-center gap-3 min-h-30 shadow-lg transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl',
                            'bg-red-brand text-white z-20' => $step['is_featured'],
                            'bg-white text-purple-brand z-10' => !$step['is_featured'],
                        ])>
                            @if ($step['icon_source'] === 'media' && $step['icon_media_url'])
                                <img src="{{ $step['icon_media_url'] }}" alt="" class="h-6 w-6 object-contain"
                                    loading="lazy" aria-hidden="true">
                            @elseif ($step['icon'] !== '')
                                <i @class([
                                    $step['icon'],
                                    'text-2xl',
                                    'text-white' => $step['is_featured'],
                                    'text-red-brand' => !$step['is_featured'],
                                ]) aria-hidden="true"></i>
                            @else
                                <span @class([
                                    'h-3 w-3 rounded-full',
                                    'bg-white' => $step['is_featured'],
                                    'bg-red-brand' => !$step['is_featured'],
                                ]) aria-hidden="true"></span>
                            @endif

                            @if ($step['title'] !== '')
                                <span class="text-base">
                                    {{ $step['title'] }}
                                </span>
                            @endif

                            @if ($step['show_connector'] && !$loop->last)
                                <div @class([
                                    'absolute w-8 h-8 rotate-45 transform pointer-events-none bottom-[-1rem] left-1/2 -translate-x-1/2 md:bottom-auto md:top-1/2 md:-translate-y-1/2 ltr:md:right-[-1rem] ltr:md:left-auto ltr:md:translate-x-0 rtl:md:left-[-1rem] rtl:md:right-auto rtl:md:translate-x-0',
                                    'bg-red-brand' => $step['is_featured'],
                                    'bg-white' => !$step['is_featured'],
                                ])></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
