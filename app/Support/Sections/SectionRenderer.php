<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Models\SectionTranslation;

class SectionRenderer
{
    /**
     * Render a section into HTML through the first safe renderer that applies.
     *
     * @param  \App\Models\Section  $section
     * @param  string|null          $locale
     * @param  array<string, mixed> $extraViewData
     * @return string
     */
    public static function render(Section $section, ?string $locale = null, array $extraViewData = []): string
    {
        $definitionDrivenHtml = self::renderDefinitionDriven($section, $locale, $extraViewData);

        if ($definitionDrivenHtml !== null) {
            return $definitionDrivenHtml;
        }

        return self::renderRegisteredSection($section, $locale, $extraViewData);
    }

    /**
     * Render a section through the definition/template registry when possible.
     *
     * Returns null when the section should keep using the existing legacy or
     * custom rendering path unchanged.
     *
     * @param  array<string, mixed> $extraViewData
     */
    public static function renderDefinitionDriven(
        Section $section,
        ?string $locale = null,
        array $extraViewData = [],
    ): ?string {
        $renderPayload = app(SectionDefinitionFrontendViewDataFactory::class)->build(
            $section,
            $locale,
            $extraViewData,
        );

        if (! is_array($renderPayload)) {
            return null;
        }

        return view(
            $renderPayload['view'],
            $renderPayload['viewData'],
        )->render();
    }

    /**
     * Render a section through the older code-side registry flow.
     *
     * @param  array<string, mixed> $extraViewData
     */
    protected static function renderRegisteredSection(
        Section $section,
        ?string $locale = null,
        array $extraViewData = [],
    ): string {
        $locale ??= app()->getLocale();

        /** @var \App\Models\SectionTranslation|null $translation */
        $translation = $section->translation($locale);

        // Get section config from registry (view, options, etc.)
        $config = SectionRegistry::get($section->type);

        if (! $config) {
            return "<!-- Section type '{$section->type}' not registered -->";
        }

        $view = $config['view'];

        // Base raw content from translation (JSON cast on model)
        $content = is_array($translation?->content ?? null) ? $translation->content : [];

        // Normalize data per section type if needed
        $data = match ($section->type) {
            'features' => self::normalizeFeaturesData($content, $translation),
            default    => $content,
        };

        // Render Blade view with normalized $data
        return view($view, array_merge($extraViewData, [
            'data' => $data,
            'section' => $section,
        ]))->render();
    }

    /**
     * Normalize data for "features" section type to match the
     * features.blade.php expectations.
     *
     * Expected content JSON example:
     * {
     *   "title": "Why choose Palgoals?",
     *   "subtitle": "Integrated digital services...",
     *   "show_illustration": true,
     *   "illustration": "assets/tamplate/images/Fu.svg",
     *   "features": [
     *     {
     *       "title": "Fast setup",
     *       "description": "Get your website live in minutes.",
     *       "icon": "<svg>...</svg>",
     *       "enabled": true
     *     }
     *   ]
     * }
     *
     * @param  array                               $content
     * @param  \App\Models\SectionTranslation|null $translation
     * @return array
     */
    protected static function normalizeFeaturesData(array $content, ?SectionTranslation $translation): array
    {
        // Title: translation->title OR JSON "title" OR fallback
        $title    = $translation?->title ?? $content['title'] ?? __('عنوان غير متوفر');
        $subtitle = $content['subtitle'] ?? '';

        // Toggle illustration (default: true)
        $showIllustration = array_key_exists('show_illustration', $content)
            ? (bool) $content['show_illustration']
            : true;

        // Optional custom illustration path
        $illustration = $content['illustration'] ?? null;

        // Raw features may live under "features" or "items"
        $rawFeatures = $content['features'] ?? $content['items'] ?? [];

        $features = collect($rawFeatures)
            // Skip disabled if "enabled" flag exists
            ->filter(function ($item) {
                return ! isset($item['enabled']) || $item['enabled'];
            })
            ->map(function ($item) {
                return [
                    'title'       => $item['title'] ?? __('عنوان'),
                    'description' => $item['description'] ?? __('وصف مختصر'),
                    'icon'        => $item['icon'] ?? null, // inline SVG string or null
                ];
            })
            ->values()
            ->all();

        return [
            'title'            => $title,
            'subtitle'         => $subtitle,
            'features'         => $features,
            'show_illustration' => $showIllustration,
            'illustration'     => $illustration,
        ];
    }
}
