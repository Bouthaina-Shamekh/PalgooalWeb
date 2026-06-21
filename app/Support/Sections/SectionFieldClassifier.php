<?php

namespace App\Support\Sections;

/**
 * SectionFieldClassifier — UI-layer field grouping for the Page Builder sidebar editor.
 *
 * Classifies a SectionDefinitionField's key into one of two UI buckets:
 *
 *   content — "What does the user see?" (text, media, CTAs, repeaters, icons)
 *   design  — "How does it look?" (layout, spacing, colours, styles, animations)
 *
 * DESIGN KEY RESOLUTION (v2 — Registry-aware)
 *
 * A field_key is classified as 'design' if:
 *
 *   (a) It is a registered token in DesignTokenRegistry::keys(), OR
 *   (b) It is listed in EXTRA_DESIGN_KEYS below.
 *
 * This means:
 * - Adding a new token to DesignTokenRegistry automatically makes it a design
 *   field — no manual change is needed here.
 * - Structural design keys that are not tokens (background_image, animation,
 *   custom_classes, etc.) remain in EXTRA_DESIGN_KEYS.
 *
 * v1 behaviour (single DESIGN_FIELD_KEYS array) is replaced by isDesignKey()
 * and allDesignKeys(). External callers that used DESIGN_FIELD_KEYS directly
 * should migrate to allDesignKeys() or isDesignKey().
 *
 * SCOPE OF THIS CLASS
 *
 * This is a presentation-layer concern only. It does NOT affect:
 *   - field_scope (shared / translatable) in SectionDefinitionField
 *   - the section save pipeline or form payload
 *   - the frontend Blade renderer or SectionRenderer
 *   - the SectionDefinitionField DB schema
 *
 * Unknown field_key values fall back to 'content' — safe default so that
 * custom fields are never silently hidden from editors.
 */
class SectionFieldClassifier
{
    /**
     * Design field keys that are NOT registered in DesignTokenRegistry but
     * still belong in the Design tab.
     *
     * These are structural or low-level keys that do not have a canonical
     * options list, default value, or CSS resolution map — so they do not
     * belong in DesignTokenRegistry, but they are clearly design concerns.
     *
     * Keep sorted alphabetically for easy diffing.
     *
     * NOTE: Token keys (background_token, text_token, image_position,
     * section_spacing, container_width) are NO LONGER listed here.
     * They are derived dynamically from DesignTokenRegistry::keys().
     */
    private const EXTRA_DESIGN_KEYS = [
        'align',
        'animation',
        'background_color',
        'background_image',
        'button_size',
        'button_style',
        'columns',
        'custom_classes',
        'grid_columns',
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
     * Return true if the field_key belongs in the Design tab.
     *
     * Check order:
     *   1. DesignTokenRegistry — canonical tokens (background_token, text_token, etc.)
     *   2. EXTRA_DESIGN_KEYS  — structural design keys (background_image, animation, etc.)
     */
    public static function isDesignKey(string $fieldKey): bool
    {
        return DesignTokenRegistry::has($fieldKey)
            || in_array($fieldKey, self::EXTRA_DESIGN_KEYS, true);
    }

    /**
     * Return the UI tab for a given field_key.
     *
     * Returns 'design' if the key is a registered token or an extra design key.
     * Returns 'content' for everything else (safe fallback).
     */
    public static function classify(string $fieldKey): string
    {
        return static::isDesignKey($fieldKey) ? 'design' : 'content';
    }

    /**
     * Return all field_keys currently classified as design fields.
     * This is the union of DesignTokenRegistry::keys() and EXTRA_DESIGN_KEYS.
     *
     * Replaces the old DESIGN_FIELD_KEYS public constant for callers that need
     * the full list rather than per-key lookups.
     *
     * @return list<string>
     */
    public static function allDesignKeys(): array
    {
        return array_values(array_unique(
            array_merge(DesignTokenRegistry::keys(), self::EXTRA_DESIGN_KEYS)
        ));
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
            if (static::isDesignKey($key)) {
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
