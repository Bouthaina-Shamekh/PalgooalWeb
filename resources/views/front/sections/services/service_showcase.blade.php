@php
    $sectionId = trim((string) ($data['section_id'] ?? 'programming'));
    $brandPrefix = trim((string) ($data['brand_prefix'] ?? ''));
    $brandSuffix = trim((string) ($data['brand_suffix'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $outputsTitle = trim((string) ($data['outputs_title'] ?? ''));

    $outputs = collect(is_array($data['outputs'] ?? null) ? $data['outputs'] : [])
        ->map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $text = trim((string) ($item['text'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));

            $iconMediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve(
                $item['icon_media'] ?? null
            );

            if ($text === '' && $icon === '' && ! $iconMediaUrl) {
                return null;
            }

            return [
                'text' => $text,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media_url' => $iconMediaUrl,
            ];
        })
        ->filter()
        ->values();

    $buttonLabel = trim((string) ($data['button_label'] ?? ''));
    $buttonUrl = trim((string) ($data['button_url'] ?? ''));
    $buttonNewTab = filter_var($data['button_new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $hasButton = $buttonLabel !== '' && $buttonUrl !== '';

    $mediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);
    $alt = trim((string) ($data['alt'] ?? ''));

    $shouldRender = $brandPrefix !== ''
        || $brandSuffix !== ''
        || $title !== ''
        || $description !== ''
        || $outputsTitle !== ''
        || $outputs->isNotEmpty()
        || $hasButton
        || $mediaUrl;
@endphp

@if ($shouldRender)
    <section id="{{ $sectionId }}" class="bg-theme-surface font-theme-body py-16 lg:py-24 px-4 sm:px-6 lg:px-12 overflow-hidden">
        <div class="container mx-auto">
            <div class="flex flex-col lg:flex-row lg:items-stretch gap-12 lg:gap-24">

                <div class="w-full lg:w-1/2 flex flex-col items-center lg:items-start text-center ltr:lg:text-left rtl:lg:text-right">

                    @if ($brandPrefix !== '' || $brandSuffix !== '')
                        <p class="text-lg md:text-xl">
                            @if ($brandPrefix !== '')
                                <span class="text-theme-secondary">{{ $brandPrefix }}</span>
                            @endif

                            @if ($brandSuffix !== '')
                                <span class="text-theme-primary">{{ $brandSuffix }}</span>
                            @endif
                        </p>
                    @endif

                    @if ($title !== '')
                        <h2 class="font-theme-heading text-theme-heading font-extrabold text-4xl md:text-[40px] mb-1 uppercase leading-tight">
                            {{ $title }}
                        </h2>
                    @endif

                    @if ($description !== '')
                        <p class="text-theme-body text-lg md:text-xl leading-relaxed mb-4 max-w-xl">
                            {{ $description }}
                        </p>
                    @endif

                    @if ($outputsTitle !== '' || $outputs->isNotEmpty())
                        <div class="w-full mb-8">
                            @if ($outputsTitle !== '')
                                <h3 class="font-theme-heading text-theme-heading text-xl font-bold mb-4 text-start">
                                    {{ $outputsTitle }}
                                </h3>
                            @endif

                            @if ($outputs->isNotEmpty())
                                <ul class="space-y-3 inline-block w-full">
                                    @foreach ($outputs as $outputItem)
                                        <li class="flex items-center justify-start gap-3 text-lg font-medium text-theme-body hover:text-theme-secondary transition-colors duration-300">
                                            @if ($outputItem['icon_source'] === 'media' && $outputItem['icon_media_url'])
                                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center md:h-6 md:w-6" aria-hidden="true">
                                                    <img
                                                        src="{{ $outputItem['icon_media_url'] }}"
                                                        alt=""
                                                        class="h-full w-full object-contain"
                                                        loading="lazy"
                                                    >
                                                </span>
                                            @elseif ($outputItem['icon'] !== '')
                                                <i class="{{ $outputItem['icon'] }} text-theme-secondary text-xl md:text-2xl" aria-hidden="true"></i>
                                            @else
                                                <span class="w-4 h-0.5 bg-theme-secondary rounded-full flex-shrink-0" aria-hidden="true"></span>
                                            @endif

                                            <span>{{ $outputItem['text'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    @if ($hasButton)
                        <a
                            href="{{ $buttonUrl }}"
                            @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                            class="btn-theme-primary inline-flex items-center justify-center md:px-10 md:py-4 px-6 py-3 text-lg md:text-xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                        >
                            {{ $buttonLabel }}
                        </a>
                    @endif
                </div>

                <div class="w-full lg:w-1/2 h-[400px] lg:h-auto">
                    <div class="relative h-full w-full rounded-theme-xl overflow-hidden shadow-theme group bg-theme-muted">
                        @if ($mediaUrl)
                            <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-all duration-500 z-10"></div>

                            <img
                                src="{{ $mediaUrl }}"
                                class="w-full h-full object-cover object-center transform transition-transform duration-700 group-hover:scale-105"
                                alt="{{ $alt ?: $title ?: 'Service illustration' }}"
                                loading="lazy"
                            >
                        @else
                            <div class="flex min-h-[24rem] items-center justify-center p-10 text-center text-theme-body">
                                {{ __('Add an image from the section editor.') }}
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </section>
@endif