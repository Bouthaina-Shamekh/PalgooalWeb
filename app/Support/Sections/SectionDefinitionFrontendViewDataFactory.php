<?php

namespace App\Support\Sections;

use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Support\Arr;

/**
 * Prepare frontend render data for definition-driven sections.
 *
 * This is a compatibility bridge, not a rewrite of the existing renderer.
 * It only activates when:
 * - the developer section-definition tables are available
 * - the current section instance is explicitly linked to a definition
 * - the definition has a selected template_key through its primary template
 *
 * Legacy sections keep `section_definition_id = null` and continue through the
 * existing type-based rendering path unchanged.
 */
class SectionDefinitionFrontendViewDataFactory
{
    public function __construct(
        protected SectionDefinitionRuntimeResolver $runtimeResolver,
    ) {}

    /**
     * Legacy section aliases that still need to resolve to canonical types.
     *
     * @var array<string, string>
     */
    protected const TYPE_ALIASES = [
        'templates-pages' => 'templates_listing_showcase',
    ];

    /**
     * Build a definition-driven render payload for a section when possible.
     *
     * Returns null when the section should continue through the existing
     * legacy/custom rendering path unchanged.
     *
     * @param  array<string, mixed>  $extraViewData
     * @return array{view: string, viewData: array<string, mixed>}|null
     */
    public function build(Section $section, ?string $locale = null, array $extraViewData = []): ?array
    {
        if (! $this->runtimeResolver->runtimeTablesAvailable()) {
            return null;
        }

        $locale ??= app()->getLocale();
        $section->loadMissing('translations');

        $definition = $this->resolveDefinition($section);

        if (! $definition) {
            return null;
        }

        $template = $definition->primaryTemplate();
        $templateKey = $template?->template_key;

        // If no template is assigned yet, keep the current legacy renderer.
        if (! is_string($templateKey) || trim($templateKey) === '') {
            return null;
        }

        $translation = $section->translation($locale);
        $content = is_array($translation?->content ?? null) ? $translation->content : [];
        $data = $this->normalizeContent($content, $definition, $locale, $translation);
        $resolvedType = $this->resolvedSectionType($definition->section_key);
        $resolvedView = SectionTemplateRegistry::resolveView($templateKey);

        return [
            'view' => $resolvedView,
            'viewData' => array_merge($extraViewData, [
                'data' => $data,
                'content' => $data,
                'section' => $section,
                'title' => $translation?->title,
                'translation' => $translation,
                'variant' => $section->variant,
                'currentLocale' => $locale,
                'sectionDefinition' => $definition,
                'sectionDefinitionFields' => $definition->relationLoaded('fields')
                    ? $definition->fields->values()
                    : collect(),
                'sectionTemplate' => $template,
                'sectionTemplateKey' => $templateKey,
                'sectionTemplateMeta' => SectionTemplateRegistry::get($templateKey),
                'resolvedSectionType' => $resolvedType,
            ]),
        ];
    }

    protected function definitionRenderingTablesAvailable(): bool
    {
        return $this->runtimeResolver->runtimeTablesAvailable();
    }

    protected function definitionFieldsTableAvailable(): bool
    {
        return $this->runtimeResolver->fieldTablesAvailable();
    }

    protected function resolveDefinition(Section $section): ?\App\Models\Sections\SectionDefinition
    {
        return $this->runtimeResolver->resolveDynamicDefinition($section);
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    protected function normalizeContent(
        array $content,
        SectionDefinition $definition,
        string $locale,
        ?SectionTranslation $translation,
    ): array {
        $normalizedContent = $content;
        $definitionFields = $definition->relationLoaded('fields')
            ? $definition->fields
            : collect();

        foreach ($definitionFields as $field) {
            if (! $field instanceof SectionDefinitionField) {
                continue;
            }

            if (Arr::has($normalizedContent, $field->field_key)) {
                continue;
            }

            [$hasDefault, $defaultValue] = $this->resolvedDefaultValue($field, $locale);

            if ($hasDefault) {
                Arr::set($normalizedContent, $field->field_key, $defaultValue);
            }
        }

        if (! Arr::has($normalizedContent, 'title')) {
            $fallbackTitle = trim((string) ($translation?->title ?? ''));

            if ($fallbackTitle !== '') {
                $normalizedContent['title'] = $fallbackTitle;
            } elseif (trim((string) $definition->label) !== '') {
                $normalizedContent['title'] = $definition->label;
            }
        }

        return SectionQueryResolver::resolve(
            $this->resolvedSectionType($definition->section_key),
            $normalizedContent,
        );
    }

    /**
     * @return array{0: bool, 1: mixed}
     */
    protected function resolvedDefaultValue(SectionDefinitionField $field, string $locale): array
    {
        return $this->runtimeResolver->resolvedDefaultValue($field, $locale);
    }

    protected function resolvedSectionType(?string $sectionType): ?string
    {
        if (! is_string($sectionType) || trim($sectionType) === '') {
            return null;
        }

        return self::TYPE_ALIASES[$sectionType] ?? $sectionType;
    }
}
