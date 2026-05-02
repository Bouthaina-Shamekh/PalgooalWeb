@php
    $title = trim((string) ($data['title'] ?? ''));
    $subtitle = trim((string) ($data['subtitle'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $feature_list_title = trim((string) ($data['feature_list_title'] ?? ''));
    $alt = trim((string) ($data['alt'] ?? ''));
    $buttonLabel = trim((string) data_get($data, 'button_label', ''));
    $buttonUrl = trim((string) data_get($data, 'button_url', ''));
    $buttonNewTab = (bool) data_get($data, 'button_new_tab', false);
    $features = collect(is_array($data['features'] ?? null) ? $data['features'] : [])
        ->map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $text = trim((string) ($item['text'] ?? ''));
            $iconSource = ($item['icon_source'] ?? 'class') === 'media' ? 'media' : 'class';
            $icon = trim((string) ($item['icon'] ?? ''));
            $iconMedia = $item['icon_media'] ?? null;

            if ($text === '' && $icon === '' && empty($iconMedia)) {
                return null;
            }

            return [
                'text' => $text,
                'icon_source' => $iconSource,
                'icon' => $icon,
                'icon_media' => $iconMedia,
            ];
        })
        ->filter()
        ->values();

    $resolvedFeatureMedia = \App\Support\Sections\SectionFrontendMediaResolver::resolveMany(
        $features->pluck('icon_media'),
    );

    $mediaUrl = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);

@endphp
<section id="hero" class="px-4 sm:px-6 lg:px-12 pt-6 pb-8 lg:pt-10 lg:pb-18">
    <div
        class="container h-full mx-auto flex flex-col-reverse ltr:lg:flex-row rtl:lg:flex-row-reverse items-center justify-between gap-12 lg:gap-16">

        <!-- Text Content -->
        <div class="lg:w-1/2 w-full text-center lg:text-start ltr:lg:order-1 rtl:lg:order-2 text-content">
            <!-- Main Title -->
            @if ($title !== '')
                <span
                    class="mb-4 inline-flex items-center gap-2 rounded-full bg-purple-100 px-4 py-1.5 text-base font-medium text-purple-700">
                    {{ $title }}
                </span>
            @endif

            <!-- Subtitle -->
            @if ($subtitle !== '')
                <h2 class="text-purple-brand font-bold text-2xl md:text-3xl mb-2">
                    {{ $subtitle }}
                </h2>
            @endif

            <!-- Description -->
            @if ($description !== '')
                <p class="text-gray-500 text-base md:text-lg mb-4 leading-relaxed">
                    {{ $description }}
                </p>
            @endif

            <!-- Campaign Features Title -->
            @if ($feature_list_title !== '')
                <h3 class="font-bold text-lg md:text-2xl mb-4 text-start">
                    {{ $feature_list_title }}
                </h3>
            @endif

            <!-- Features Grid -->
            @if ($features->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                    <!-- Feature 1 -->
                    @foreach ($features as $feature)
                        <div class="flex items-center ltr:justify-start rtl:justify-start gap-3">
                            @if (
                                $feature['icon_source'] === 'media' &&
                                    !empty($feature['icon_media']) &&
                                    !empty($resolvedFeatureMedia[$feature['icon_media']]))
                                <img src="{{ $resolvedFeatureMedia[$feature['icon_media']] }}" alt=""
                                    class="h-5 w-5 shrink-0 object-contain" aria-hidden="true">
                            @elseif ($feature['icon'] !== '')
                                <i class="{{ $feature['icon'] }} text-xl text-red-brand" aria-hidden="true"></i>
                            @else
                                <svg class="h-5 shrink-0 text-red-brand" fill="currentColor" viewBox="0 0 27 21"
                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z"
                                        fill="#BA112C" />
                                </svg>
                            @endif
                            <span class="text-base md:text-xl text-purple-brand">
                                {{ $feature['text'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- CTA Button -->
            @if ($buttonLabel !== '' && $buttonUrl !== '')
                <a href="{{ $buttonUrl }}"
                    @if ($buttonNewTab) target="_blank" rel="noopener noreferrer" @endif
                    class="inline-flex bg-red-brand text-white px-10 py-3 rounded-xl text-lg md:text-xl hover:bg-opacity-90 transition-all duration-300 hover:translate-x-1 hover:shadow-lg shadow-md">
                    {{ $buttonLabel }}
                </a>
            @endif
        </div>

        <!-- Image (End Side) -->
        <div class="w-full lg:w-1/2 h-auto ltr:lg:order-2 rtl:lg:order-1 p-8 hero-image">
            <div class="relative h-full w-full rounded-[40px] group">
                @if ($mediaUrl)
                    <img src="{{ $mediaUrl }}"
                        class="w-full h-full object-cover object-center transform transition-transform duration-700 group-hover:scale-105"
                        alt="{{ $alt ?: $title ?: 'Hero illustration' }}" loading="lazy">
                @else
                    <div
                        class="flex min-h-[24rem] items-center justify-center rounded-[40px] bg-background p-10 text-center text-tertiary">
                        {{ __('Add an image from the section editor.') }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</section>
