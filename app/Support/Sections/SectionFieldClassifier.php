<?php

namespace App\Support\Sections;

/**
 * SectionFieldClassifier — UI-layer field grouping for the Page Builder sidebar editor.
 *
 * Classifies a SectionDefinitionField's key into one of two UI buckets:
 *
 *   content — "What does the user see?" (text, media, CTAs, repeaters, icons…)
 *   design  — "How does it look?" (layout, spacing, colours, styles, animations…)
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * SCOPE OF THIS CLASS
 * ─────────────────────────────────────────────────────────────────────────────
 * This is a **presentation-layer concern only**. It does NOT affect:
 *   • field_scope (shared / translatable) in SectionDefinitionField
 *   • the section save pipeline or form payload
 *   • the frontend Blade renderer or SectionRenderer
 *   • the SectionDefinitionField DB schema
 *
 * Unknown field_key values fall back to 'content' — safe default so that
 * custom fields are never silently hidden from editors.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class SectionFieldClassifier
{
    /**
     * Field keys that belong in the "Design / Settings" tab.
     * Every other key defaults to the "Content" tab.
     *
     * Keep this list sorted alphabetically for easy diffing.
     */
    public const DESIGN_FIELD_KEYS = [
        'align',
        'animation',
        'background_color',
        'background_image',
        'background_token',
        'text_token',
        'button_size',
        'button_style',
        'columns',
        'custom_classes',
        'grid_columns',
        'image_position',
        'layout_style',
        'padding_bottom',
        'padding_top',
        'spacing_bottom',
        'spacing_top',
        'subtitle_size',
        'text_align',
        'theme_variant',
        'title_size',
    ];

    /**
     * Return the UI tab for a given field_key.
     * Returns 'design' for known design keys; 'content' for everything else.
     */
    public static function classify(string $fieldKey): string
    {
        return in_array($fieldKey, self::DESIGN_FIELD_KEYS, true) ? 'design' : 'content';
    }

    /**
     * Split a flat array of field payloads (from DynamicSectionEditorRenderer)
     * into ['content' => [...], 'design' => [...]].
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array{content: list<array>, design: list<array>}
     */
    public static function splitFields(array $fields): array
    {
        $content = [];
        $design  = [];

        foreach ($fields as $field) {
            $key = (string) ($field['fieldKey'] ?? '');
            if (static::classify($key) === 'design') {
                $design[] = $field;
            } else {
                $content[] = $field;
            }
        }

        return compact('content', 'design');
    }

    /**
     * Split groups produced by DynamicSectionEditorRenderer::buildGroupsForLocale()
     * into content groups and design groups.
     *
     * Groups that have no fields in a given bucket are omitted from that bucket
     * to avoid rendering empty group cards. Groups that contain a mix of
     * content and design fields will appear in BOTH buckets (one card each),
     * preserving the group label for context.
     *
     * @param  array<int, array<string, mixed>>  $groups
     * @return array{content: list<array>, design: list<array>}
     */
    public static function splitGroups(array $groups): array
    {
        $contentGroups = [];
        $designGroups  = [];

        foreach ($groups as $group) {
            ['content' => $contentFields, 'design' => $designFields] =
                static::splitFields($group['fields'] ?? []);

            if ($contentFields !== []) {
                $contentGroups[] = array_merge($group, ['fields' => $contentFields]);
            }

            if ($designFields !== []) {
                $designGroups[] = array_merge($group, ['fields' => $designFields]);
            }
        }

        return ['content' => $contentGroups, 'design' => $designGroups];
    }
}
