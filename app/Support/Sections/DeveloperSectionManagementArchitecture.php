<?php

namespace App\Support\Sections;

/**
 * Internal architecture foundation for the planned Developer Section
 * Management System.
 *
 * This file is intentionally lightweight and non-invasive. It documents the
 * future system boundaries in code and provides stable terminology/constants
 * that later schema, editor, and registry work can share.
 *
 * Architectural decisions:
 *
 * 1. Definition Layer vs Content Layer
 *    - The definition layer stores developer-authored section definitions,
 *      field definitions, editor metadata, defaults, and template keys.
 *    - The content layer stores actual section usage data and localized field
 *      values only.
 *    - Blade templates, raw PHP, and rendering logic must not be stored in
 *      the database.
 *
 * 2. Dynamic Editor vs Custom Editor Preset
 *    - A dynamic editor builds admin forms directly from normalized field
 *      definitions.
 *    - A custom editor preset is a developer-owned curated editor around the
 *      same definition contract when a section needs a specialized UX.
 *    - Both paths must stay admin-only and must not change frontend runtime
 *      rendering contracts by themselves.
 *
 * 3. template_key-driven Frontend Rendering
 *    - Frontend rendering must remain template_key-driven so the database only
 *      selects which registered template/configuration to use.
 *    - The actual render implementation continues to live in code through
 *      Blade, support classes, registries, and resolvers.
 *
 * 4. Locale-agnostic Translatable Field Strategy
 *    - Field definitions declare whether a field is shared or translatable.
 *    - Translatable fields are locale-agnostic at the definition layer: the
 *      definition does not hardcode locale codes.
 *    - Runtime/editor expansion should use the application's enabled
 *      languages, preserving compatibility with any number of locales.
 *
 * Compatibility notes:
 * - Existing Section, SectionTranslation, SectionRenderer, and SectionRegistry
 *   remain the current runtime system of record.
 * - This class does not enable a new persistence path yet. It only establishes
 *   terminology and safe future boundaries.
 */
final class DeveloperSectionManagementArchitecture
{
    /**
     * Definition-layer records describe a section type and its editable shape.
     */
    public const LAYER_DEFINITION = 'definition';

    /**
     * Content-layer records carry actual page usage and localized values.
     */
    public const LAYER_CONTENT = 'content';

    /**
     * Dynamic editor mode renders fields from normalized definitions.
     */
    public const EDITOR_MODE_DYNAMIC = 'dynamic';

    /**
     * Custom preset mode uses a hand-crafted admin editor over the same
     * normalized definition contract.
     */
    public const EDITOR_MODE_CUSTOM_PRESET = 'custom_preset';

    /**
     * Frontend rendering remains code-driven through a registered template key.
     */
    public const RENDER_STRATEGY_TEMPLATE_KEY = 'template_key';

    /**
     * Shared fields keep one value across all locales.
     */
    public const FIELD_SCOPE_SHARED = 'shared';

    /**
     * Translatable fields expand across the currently enabled locales.
     */
    public const FIELD_SCOPE_TRANSLATABLE = 'translatable';

    /**
     * Field definitions stay locale-agnostic and do not embed locale lists.
     */
    public const TRANSLATION_STRATEGY_LOCALE_AGNOSTIC = 'locale_agnostic';

    /**
     * Return the agreed conceptual boundaries for the upcoming system.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function boundaries(): array
    {
        return [
            self::LAYER_DEFINITION => [
                'stores' => [
                    'section definitions',
                    'field definitions',
                    'editor behavior flags',
                    'template keys',
                    'default values',
                    'developer metadata',
                ],
                'must_not_store' => [
                    'blade templates',
                    'raw php',
                    'rendering closures',
                    'locale-specific content values',
                ],
            ],
            self::LAYER_CONTENT => [
                'stores' => [
                    'page-level section usage',
                    'localized values for translatable fields',
                    'shared values for non-translatable fields',
                    'ordering/visibility references when the implementation phase begins',
                ],
                'must_not_store' => [
                    'duplicated field definitions',
                    'duplicated render templates',
                ],
            ],
        ];
    }

    /**
     * Return the planned support-layer building blocks for future phases.
     *
     * These names are intentional references for later implementation work.
     * They are not wired into the current runtime yet.
     *
     * @return array<string, string>
     */
    public static function plannedSupportClasses(): array
    {
        return [
            'DeveloperSectionDefinitionRepository' => 'Loads and caches developer-authored section definitions.',
            'DeveloperSectionDefinitionNormalizer' => 'Normalizes raw definition records into a stable contract.',
            'DeveloperSectionDynamicEditorFactory' => 'Builds admin editor payloads from normalized definitions.',
            'DeveloperSectionPresetRegistry' => 'Maps sections that need curated custom editor presets.',
            'DeveloperSectionTemplateKeyResolver' => 'Maps template_key values to registered frontend render implementations.',
            'DeveloperSectionContentMapper' => 'Bridges future definition/content records with existing section content contracts.',
        ];
    }
}
