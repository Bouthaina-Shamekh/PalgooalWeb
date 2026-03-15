@php
    use Illuminate\Support\Str;

    $pageTranslation = method_exists($page, 'translation') ? $page->translation() : null;
    $pageTitle = $pageTranslation?->title ?? $page->slug ?? ('#' . $page->id);
    $currentLocale = app()->getLocale();
    $previewTitle = $pageTitle . ' - ' . __('Sections Preview');
    $previewDescription = __('Live preview for the sections workspace.');
@endphp

@push('styles')
    <style>
        html,
        body {
            background: transparent;
        }

        .sections-preview-page {
            min-height: 100vh;
            padding: 0;
            background: transparent;
        }

        .sections-preview-shell {
            width: 100%;
            margin: 0;
        }

        .sections-preview-block {
            position: relative;
            scroll-margin-top: 1rem;
            transition: box-shadow 180ms ease, transform 180ms ease, opacity 180ms ease;
        }

        .sections-preview-block + .sections-preview-block {
            margin-top: 0;
        }

        .sections-preview-block.is-highlighted {
            box-shadow: inset 0 0 0 3px rgba(15, 23, 42, 0.14);
        }

        .sections-preview-block.is-hidden {
            opacity: 0.78;
        }

        .sections-preview-state {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            position: absolute;
            top: 1rem;
            z-index: 10;
            padding: 0.5rem 0.85rem;
            border: 1px solid rgba(248, 113, 113, 0.24);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: #b91c1c;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 14px 30px -20px rgba(15, 23, 42, 0.32);
        }

        html[dir="ltr"] .sections-preview-state {
            left: 1rem;
        }

        html[dir="rtl"] .sections-preview-state {
            right: 1rem;
        }

        .sections-preview-fallback {
            padding: 2rem;
            border: 1px dashed #cbd5e1;
            border-radius: 2rem;
            background: rgba(255, 255, 255, 0.92);
            color: #475569;
        }

        .sections-preview-empty {
            display: grid;
            place-items: center;
            min-height: calc(100vh - 6rem);
            padding: 2rem;
            border: 1px dashed #cbd5e1;
            border-radius: 2rem;
            background: rgba(255, 255, 255, 0.88);
            text-align: center;
            color: #64748b;
        }
    </style>
@endpush

@include('front.layouts.partials.head', [
    'seo' => [
        'title' => $previewTitle,
        'description' => $previewDescription,
        'canonical' => url()->current(),
        'type' => 'website',
    ],
])

@include('front.layouts.partials.header')

<div class="pc-container">
    <div class="pc-content">
        <div class="sections-preview-page">
            <div class="sections-preview-shell">
                @forelse ($sections as $section)
                    @php
                        $translation = method_exists($section, 'translation') ? $section->translation($currentLocale) : null;
                        $fallbackTranslation = $translation ?? $section->translations->first();
                        $content = is_array($fallbackTranslation?->content ?? null) ? $fallbackTranslation->content : [];
                        $typeLabel = $sectionTypes[$section->type]['label'] ?? Str::headline(str_replace(['_', '-'], ' ', $section->type));

                        $featureItems = collect(is_array($content['features'] ?? null) ? $content['features'] : [])
                            ->map(function ($item) {
                                if (is_string($item)) {
                                    return [
                                        'title' => $item,
                                        'description' => '',
                                        'icon' => null,
                                    ];
                                }

                                if (is_array($item)) {
                                    return [
                                        'title' => $item['title'] ?? $item['label'] ?? __('Feature'),
                                        'description' => $item['description'] ?? $item['subtitle'] ?? '',
                                        'icon' => $item['icon'] ?? null,
                                    ];
                                }

                                return null;
                            })
                            ->filter()
                            ->values()
                            ->all();

                        $featuresData = [
                            'title' => $fallbackTranslation?->title ?? ($content['title'] ?? $typeLabel),
                            'subtitle' => $content['subtitle'] ?? '',
                            'features' => $featureItems,
                            'show_illustration' => array_key_exists('show_illustration', $content)
                                ? (bool) $content['show_illustration']
                                : true,
                            'illustration' => $content['illustration'] ?? null,
                        ];

                        $servicesData = \App\Support\Sections\SectionQueryResolver::resolve('services', [
                            'title' => $fallbackTranslation?->title ?? ($content['title'] ?? $typeLabel),
                            'subtitle' => $content['subtitle'] ?? '',
                            'limit' => $content['limit'] ?? 8,
                            'order' => $content['order'] ?? 'order',
                        ]);

                        $templatesData = [
                            'title' => $fallbackTranslation?->title ?? ($content['title'] ?? $typeLabel),
                            'subtitle' => $content['subtitle'] ?? '',
                        ];
                    @endphp

                    <div
                        id="preview-section-{{ $section->id }}"
                        data-preview-section-id="{{ $section->id }}"
                        class="sections-preview-block {{ $highlightSectionId === $section->id ? 'is-highlighted' : '' }} {{ $section->is_active ? '' : 'is-hidden' }}"
                    >
                        @unless ($section->is_active)
                            <div class="sections-preview-state">{{ __('Hidden') }}</div>
                        @endunless

                        @switch($section->type)
                            @case('hero_default')
                            @case('hero_minimal')
                                @include('components.template.sections.hero_default', [
                                    'section' => $section,
                                    'title' => $fallbackTranslation?->title,
                                    'content' => $content,
                                    'variant' => $section->variant,
                                ])
                                @break

                            @case('features_grid')
                                <x-template.sections.features :data="$featuresData" />
                                @break

                            @case('services_grid')
                                @include('components.template.sections.services', ['data' => $servicesData])
                                @break

                            @case('templates_showcase')
                                <x-template.sections.templates :data="$templatesData" :templates="$previewTemplates" />
                                @break

                            @default
                                <div class="sections-preview-fallback">
                                    <h2 class="text-xl font-semibold text-slate-900">{{ $typeLabel }}</h2>
                                    <p class="mt-2 text-sm text-slate-600">{{ __('This section type does not have a preview renderer yet.') }}</p>
                                </div>
                        @endswitch
                    </div>
                @empty
                    <div class="sections-preview-empty">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">{{ __('No sections to preview yet') }}</h2>
                            <p class="mt-3 text-sm leading-6">{{ __('Add a section from the workspace library to start the live preview.') }}</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@include('front.layouts.partials.footer')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const previewBlocks = Array.from(document.querySelectorAll('[data-preview-section-id]'));
        const initialHighlight = Number(@json($highlightSectionId));
        const currentOrigin = window.location.origin;

        const setHighlightedSection = (sectionId, shouldScroll = true) => {
            if (!sectionId) {
                return;
            }

            previewBlocks.forEach((block) => {
                block.classList.toggle('is-highlighted', Number(block.dataset.previewSectionId) === sectionId);
            });

            const activeBlock = document.querySelector(`[data-preview-section-id="${sectionId}"]`);
            if (activeBlock && shouldScroll) {
                activeBlock.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }
        };

        previewBlocks.forEach((block) => {
            block.addEventListener('click', function (event) {
                if (event.target.closest('a, button, input, textarea, select, form')) {
                    event.preventDefault();
                }

                const sectionId = Number(block.dataset.previewSectionId || 0);
                if (!sectionId) {
                    return;
                }

                setHighlightedSection(sectionId, false);

                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'sections-preview:selected',
                        sectionId: sectionId,
                    }, currentOrigin);
                }
            });
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('a, button, [role="button"], form')) {
                event.preventDefault();
            }
        }, true);

        window.addEventListener('message', function (event) {
            if (event.origin !== currentOrigin) {
                return;
            }

            const payload = event.data || {};
            if (payload.type === 'sections-preview:highlight') {
                setHighlightedSection(Number(payload.sectionId || 0), true);
            }
        });

        if (initialHighlight) {
            window.setTimeout(() => {
                setHighlightedSection(initialHighlight, true);
            }, 120);
        }
    });
</script>

@include('front.layouts.partials.end')
