@php
    $currentLocale = $currentLocale ?? app()->getLocale();
    $definitionDrivenSectionHtml = \App\Support\Sections\SectionRenderer::renderDefinitionDriven(
        $section,
        $currentLocale,
        [
            'disable_legacy_fallback' => true,
        ],
    );

    $fallbackHtml = null;

    if (!is_string($definitionDrivenSectionHtml) || trim($definitionDrivenSectionHtml) === '') {
        $section->loadMissing('sectionDefinition.templates');

        $definition = $section->sectionDefinition;
        $templateKey = $definition?->primaryTemplateKey();
        $templateResolution = \App\Support\Sections\SectionTemplateRegistry::resolve(
            $templateKey,
            $definition?->category,
        );

        $resolvedSectionType = trim((string) ($definition?->section_key ?? ($section->type ?? '')));

        $missingTemplate = [
            'title' => __('Section renderer not found'),
            'message' => $section->section_definition_id
                ? __('Definition-driven rendering did not return HTML for section #:sectionId.', [
                    'sectionId' => $section->id,
                ])
                : __(
                    'This section is not linked to an active section definition, so it can no longer render through the active frontend definition-only path.',
                ),
            'details' => array_values(
                array_filter([
                    $section->section_definition_id
                        ? __('Active frontend rendering no longer falls back to legacy section partial switches.')
                        : __('Legacy fallback rendering is intentionally bypassed here.'),
                    $templateKey
                        ? __(
                            'Template key ":templateKey" could not be resolved to a definition-driven frontend view.',
                            [
                                'templateKey' => $templateKey,
                            ],
                        )
                        : __('No active primary template key is assigned to the linked definition.'),
                ]),
            ),
            'template_key' => $templateKey,
            'category' => \App\Support\Sections\SectionTemplateRegistry::normalizeCategory($definition?->category),
            'section_key' => $definition?->section_key ?? ($section->type ?? null),
            'resolved_section_type' => $resolvedSectionType !== '' ? $resolvedSectionType : null,
            'resolution_source' => $templateResolution['source'] ?? 'missing',
            'attempted_views' => is_array($templateResolution['attempted_views'] ?? null)
                ? $templateResolution['attempted_views']
                : [],
            'section_id' => $section->id,
            'section_definition_id' => $definition?->id ?? $section->section_definition_id,
        ];

        $fallbackHtml = view(\App\Support\Sections\SectionTemplateRegistry::fallbackView(), [
            'missingTemplate' => $missingTemplate,
        ])->render();
    }
@endphp

{!! is_string($definitionDrivenSectionHtml) && trim($definitionDrivenSectionHtml) !== ''
    ? $definitionDrivenSectionHtml
    : $fallbackHtml !!}
