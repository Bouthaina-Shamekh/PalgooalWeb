@php
    use Illuminate\Support\Str;

    $currentLocale = $currentLocale ?? app()->getLocale();
    $sectionTypes = $sectionTypes ?? [];
    $templates = $templates ?? collect();

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

    @case('hero_campaign')
        @include('components.template.sections.hero_campaign', [
            'section' => $section,
            'title' => $fallbackTranslation?->title,
            'content' => $content,
            'variant' => $section->variant,
        ])
        @break

    @case('programming_showcase')
        @include('components.template.sections.programming_showcase', [
            'section' => $section,
            'title' => $fallbackTranslation?->title,
            'content' => $content,
            'variant' => $section->variant,
        ])
        @break

    @case('mobile_app_showcase')
        @include('components.template.sections.mobile_app_showcase', [
            'section' => $section,
            'title' => $fallbackTranslation?->title,
            'content' => $content,
            'variant' => $section->variant,
        ])
        @break

    @case('how_we_build')
        @include('components.template.sections.how_we_build', [
            'section' => $section,
            'title' => $fallbackTranslation?->title,
            'content' => $content,
            'variant' => $section->variant,
        ])
        @break

    @case('design_showcase')
        @include('components.template.sections.design_showcase', [
            'section' => $section,
            'title' => $fallbackTranslation?->title,
            'content' => $content,
            'variant' => $section->variant,
        ])
        @break

    @case('digital_marketing_showcase')
        @include('components.template.sections.digital_marketing_showcase', [
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
        <x-template.sections.templates :data="$templatesData" :templates="$templates" />
        @break

    @default
        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-slate-600">
            <h2 class="text-xl font-semibold text-slate-900">{{ $typeLabel }}</h2>
            <p class="mt-2 text-sm">{{ __('This section type does not have a renderer yet.') }}</p>
        </div>
@endswitch
