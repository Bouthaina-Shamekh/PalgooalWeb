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
 * - the linked definition is active, regardless of editor_mode
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
     * Resolution priority:
     * 1. explicit code-side template registry override
     * 2. convention-based Blade resolution from template_key
     * 3. existing legacy renderer path when the section type already supports it
     * 4. explicit missing-renderer view state
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
        $templateResolution = SectionTemplateRegistry::resolve($templateKey, $definition->category);
        $resolvedView = $templateResolution['view'] ?? null;

        if (! is_string($resolvedView) || trim($resolvedView) === '') {
            if ($this->shouldUseLegacyFallback($templateResolution, $resolvedType)) {
                return null;
            }

            return [
                'view' => SectionTemplateRegistry::fallbackView(),
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
                    'sectionTemplateMeta' => $templateResolution['descriptor'],
                    'resolvedSectionType' => $resolvedType,
                    'missingTemplate' => $this->missingTemplatePayload(
                        $section,
                        $definition,
                        $templateKey,
                        $resolvedType,
                        $templateResolution,
                    ),
                ]),
            ];
        }

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
                'sectionTemplateMeta' => $templateResolution['descriptor'],
                'sectionTemplateResolution' => $templateResolution,
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
        return $this->runtimeResolver->resolveRenderableDefinition($section);
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

    /**
     * Only preserve the legacy path when the template key was convention-based
     * and the section type already has an explicit old renderer.
     *
     * @param  array<string, mixed>  $templateResolution
     */
    protected function shouldUseLegacyFallback(array $templateResolution, ?string $resolvedType): bool
    {
        return in_array(($templateResolution['source'] ?? null), ['convention', 'deprecated_convention'], true)
            && SectionRenderer::hasLegacyRenderer($resolvedType);
    }

    /**
     * @param  array<string, mixed>  $templateResolution
     * @return array<string, mixed>
     */
    protected function missingTemplatePayload(
        Section $section,
        SectionDefinition $definition,
        string $templateKey,
        ?string $resolvedType,
        array $templateResolution,
    ): array {
        $attemptedViews = array_values(array_filter(
            array_map(
                static fn ($value): string => trim((string) $value),
                is_array($templateResolution['attempted_views'] ?? null) ? $templateResolution['attempted_views'] : [],
            ),
            static fn (string $value): bool => $value !== '',
        ));

        $resolutionSource = (string) ($templateResolution['source'] ?? 'missing');
        $sourceMessage = $resolutionSource === 'registry'
            ? __('A code-side template override is registered for this template key, but its Blade view could not be found.')
            : __('No explicit code-side override was registered, and the categorized convention-based Blade view could not be found.');
        $category = SectionTemplateRegistry::normalizeCategory($definition->category);

        return [
            'title' => __('Section renderer not found'),
            'message' => __('Template key ":templateKey" could not be resolved for definition ":definitionKey".', [
                'templateKey' => $templateKey,
                'definitionKey' => $definition->section_key,
            ]),
            'details' => [
                $sourceMessage,
                $resolvedType
                    ? __('Legacy fallback is not available for section type ":sectionType".', [
                        'sectionType' => $resolvedType,
                    ])
                    : __('Legacy fallback is not available because the section type could not be resolved.'),
            ],
            'template_key' => $templateKey,
            'category' => $category,
            'section_key' => $definition->section_key,
            'resolved_section_type' => $resolvedType,
            'resolution_source' => $resolutionSource,
            'attempted_views' => $attemptedViews,
            'section_id' => $section->id,
            'section_definition_id' => $definition->id,
        ];
    }
}
