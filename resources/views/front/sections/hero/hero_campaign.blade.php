@php
    $sectionId = trim((string) ($data['section_id'] ?? 'hero'));
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $featureListTitle = trim((string) ($data['feature_list_title'] ?? ''));
    $alt = trim((string) ($data['alt'] ?? ''));

    $buttonLabel = trim((string) ($data['button_label'] ?? ''));
    $buttonUrl = trim((string) ($data['button_url'] ?? ''));
    $buttonNewTab = filter_var($data['button_new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $hasButton = $buttonLabel !== '' && $buttonUrl !== '';

    $features = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
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

    $mediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);

    $shouldRender = $title !== ''
        || $subtitle !== ''
        || $description !== ''
        || $featureListTitle !== ''
        || $features->isNotEmpty()
        || $hasButton
        || $mediaUrl;
@endphp

@if ($shouldRender)
    <section id="{{ $sectionId }}" class="bg-theme-surface font-theme-body px-4 sm:px-6 lg:px-12 pt-6 pb-8 lg:pt-10 lg:pb-18 overflow-hidden">
        <div class="container h-full mx-auto flex flex-col-reverse ltr:lg:flex-row rtl:lg:flex-row-reverse items-center justify-between gap-12 lg:gap-16">

            <div class="lg:w-1/2 w-full text-center lg:text-start ltr:lg:order-1 rtl:lg:order-2 text-content">

                @if ($title !== '')
                    <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-theme-muted px-4 py-1.5 text-base font-medium text-theme-primary">
                        {{ $title }}
                    </span>
                @endif

                @if ($subtitle !== '')
                    <h2 class="font-theme-heading text-theme-heading font-bold text-2xl md:text-3xl mb-2">
                        {{ $subtitle }}
                    </h2>
                @endif

                @if ($description !== '')
                    <p class="text-theme-body text-base md:text-lg mb-4 leading-relaxed">
                        {{ $description }}
                    </p>
                @endif

                @if ($featureListTitle !== '')
                    <h3 class="font-theme-heading text-theme-heading font-bold text-lg md:text-2xl mb-4 text-start">
                        {{ $featureListTitle }}
                    </h3>
                @endif

                @if ($features->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                        @foreach ($features as $feature)
                            <div class="flex items-center ltr:justify-start rtl:justify-start gap-3">
                                @if ($feature['icon_source'] === 'media' && $feature['icon_media_url'])
                                    <img
                                        src="{{ $feature['icon_media_url'] }}"
                                        alt=""
                                        class="h-5 w-5 shrink-0 object-contain"
                                        loading="lazy"
                                        aria-hidden="true"
                                    >
                                @elseif ($feature['icon'] !== '')
                                    <i class="{{ $feature['icon'] }} text-theme-secondary text-xl" aria-hidden="true"></i>
                                @else
                                    <svg class="h-5 shrink-0 text-theme-secondary" fill="currentColor" viewBox="0 0 27 21" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="currentColor" />
                                    </svg>
                                @endif

                                @if ($feature['text'] !== '')
                                    <span class="text-theme-heading text-base md:text-xl">
                                        {{ $feature['text'] }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($hasButton)
                    <a
                        href="{{ $buttonUrl }}"
                        @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                        class="btn-theme-primary inline-flex items-center justify-center px-10 py-3 text-lg md:text-xl transition-all duration-300 hover:translate-x-1 hover:shadow-lg shadow-md"
                    >
                        {{ $buttonLabel }}
                    </a>
                @endif
            </div>

            <div class="w-full lg:w-1/2 h-auto ltr:lg:order-2 rtl:lg:order-1 p-8 hero-image">
                <div class="relative h-full w-full rounded-theme-xl group bg-theme-muted overflow-hidden">
                    @if ($mediaUrl)
                        <img
                            src="{{ $mediaUrl }}"
                            class="w-full h-full object-cover object-center transform transition-transform duration-700 group-hover:scale-105"
                            alt="{{ $alt ?: $title ?: 'Hero illustration' }}"
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
    </section>
@endif