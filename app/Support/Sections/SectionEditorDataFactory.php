<?php

namespace App\Support\Sections;

use App\Models\Section;
use Illuminate\Support\Collection;

class SectionEditorDataFactory
{
    protected const LEGACY_TYPE_ALIASES = [
        'templates-pages' => 'templates_listing_showcase',
    ];

    public function __construct(
        protected DynamicSectionEditorRenderer $dynamicSectionEditorRenderer,
    ) {}

    public function make(Section $section, iterable $languages, array $sectionTypes = []): array
    {
        $dynamicEditor = $this->dynamicSectionEditorRenderer->buildForSection($section, $languages);

        return $this->buildDefinitionOnlyEditorState(
            $section,
            $languages,
            $dynamicEditor,
        );
    }

    /**
     * Definition-linked admin page sections bypass the legacy type capability,
     * schema, repeater, and locale compatibility payloads entirely.
     *
     * Shell editor legacy compatibility only now lives outside this factory.
     */
    protected function buildDefinitionOnlyEditorState(
        Section $section,
        iterable $languages,
        ?array $dynamicEditor,
    ): array {
        $defaultLocale = $this->resolveDefaultLocale($languages);
        $resolvedDynamicEditor = is_array($dynamicEditor) && (bool) ($dynamicEditor['enabled'] ?? false)
            ? $dynamicEditor
            : $this->emptyDynamicEditorState($languages, $defaultLocale);

        return [
            'selectedType' => $this->normalizeSelectedType(old('type', $section->type)),
            'defaultLocale' => $defaultLocale,
            'usesInternalLabel' => true,
            'usesDynamicEditor' => true,
            'dynamicEditor' => $resolvedDynamicEditor,
            'localeScalarValues' => $this->buildDefinitionOnlyLocaleScalarValues($section, $languages),
        ];
    }

    protected function oldTranslationValue(string $code, string $key, mixed $default = ''): mixed
    {
        return old("translations.$code.$key", $default);
    }

    protected function normalizeSelectedType(mixed $selectedType): string
    {
        if (! is_string($selectedType) || trim($selectedType) === '') {
            return 'hero_campaign';
        }

        return self::LEGACY_TYPE_ALIASES[$selectedType] ?? $selectedType;
    }

    protected function resolveDefaultLocale(iterable $languages): string
    {
        $localeCodes = Collection::make($languages)
            ->pluck('code')
            ->filter()
            ->values();

        return $localeCodes->contains(app()->getLocale())
            ? app()->getLocale()
            : ($localeCodes->first() ?? app()->getLocale());
    }

    protected function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    protected function buildDefinitionOnlyLocaleScalarValues(Section $section, iterable $languages): array
    {
        return Collection::make($languages)
            ->mapWithKeys(function ($language) use ($section) {
                $code = $language->code;
                $translation = $section->translations->firstWhere('locale', $code);

                return [
                    $code => [
                        'sectionTitleValue' => $this->stringValue(
                            $this->oldTranslationValue($code, 'title', $translation?->title ?? ''),
                        ),
                    ],
                ];
            })
            ->all();
    }

    protected function emptyDynamicEditorState(iterable $languages, string $defaultLocale): array
    {
        return [
            'enabled' => true,
            'defaultLocale' => $defaultLocale,
            'definition' => null,
            'locales' => Collection::make($languages)
                ->mapWithKeys(fn($language) => [
                    (string) $language->code => [
                        'code' => (string) $language->code,
                        'label' => (string) ($language->name ?? strtoupper((string) $language->code)),
                        'groups' => [],
                    ],
                ])
                ->all(),
        ];
    }
}
