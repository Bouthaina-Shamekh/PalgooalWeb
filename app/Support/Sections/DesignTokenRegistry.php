<?php

namespace App\Support\Sections;

/**
 * DesignTokenRegistry — Canonical registry for design token fields in the Section system.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * PURPOSE
 * ─────────────────────────────────────────────────────────────────────────────
 * A single source of truth for:
 *   1. Canonical field_key names for design tokens (prevents naming drift)
 *   2. Valid option sets per token (shared with DB field options JSON)
 *   3. Default values per token
 *   4. Tailwind CSS class resolution per value
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT THIS IS NOT
 * ─────────────────────────────────────────────────────────────────────────────
 * • NOT a runtime resolver called on every page render (no DB queries here)
 * • NOT a replacement for existing Blade match() blocks (those keep working)
 * • NOT a theme system or per-tenant customisation layer
 * • NOT a migration or schema change
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * CONSUMERS
 * ─────────────────────────────────────────────────────────────────────────────
 * • SectionFieldClassifier — derives token keys from keys() instead of a
 *   manually-maintained static array.
 * • BladeGenerator (future) — queries get() to emit correct match() stubs.
 * • SectionPackageGenerator (future) — sources options() when creating
 *   SectionDefinitionField rows for token fields.
 * • ComponentLibrary (future) — a 'design' component will reference these keys.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ADDING A NEW TOKEN
 * ─────────────────────────────────────────────────────────────────────────────
 * Add one entry to ALL_TOKENS below. No other file needs to change for the
 * token to be recognised as a design field in the Page Builder sidebar.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class DesignTokenRegistry
{
    /**
     * All registered design tokens.
     *
     * Schema per entry:
     * [
     *   'key'         => string,            // canonical field_key
     *   'field_type'  => string,            // 'select' | 'text' | 'boolean'
     *   'field_scope' => string,            // 'shared' | 'translatable'
     *   'default'     => string,            // default value
     *   'group_name'  => string,            // FieldGroupRegistry group_key
     *   'options'     => [                  // array of {value, label} — label is English fallback
     *     ['value' => string, 'label' => string],
     *     ...
     *   ],
     *   'css_map'     => [value => class],  // Tailwind class per value (empty string = no class)
     * ]
     *
     * Translation note:
     * Option labels are English fallbacks only. Translations are handled via t() at the UI layer.
     * Do NOT embed multilingual arrays here (no 'ar'/'en' keys).
     */
    private const ALL_TOKENS = [

        // ─── Background Token ──────────────────────────────────────────────────
        'background_token' => [
            'key'         => 'background_token',
            'field_type'  => 'select',
            'field_scope' => 'shared',
            'default'     => 'muted',
            'group_name'  => 'design',
            'options'     => [
                ['value' => 'none',      'label' => 'No background'],
                ['value' => 'primary',   'label' => 'Primary background'],
                ['value' => 'secondary', 'label' => 'Secondary background'],
                ['value' => 'surface',   'label' => 'Surface background'],
                ['value' => 'muted',     'label' => 'Muted background'],
                // ── Admin Brand custom color slots (Phase 2) ───────
                // CSS class → admin-brand.css → --admin-color-custom-N
                ['value' => 'custom_1',  'label' => 'Custom Color 1'],
                ['value' => 'custom_2',  'label' => 'Custom Color 2'],
                ['value' => 'custom_3',  'label' => 'Custom Color 3'],
                ['value' => 'custom_4',  'label' => 'Custom Color 4'],
                ['value' => 'custom_5',  'label' => 'Custom Color 5'],
            ],
            'css_map' => [
                'none'      => '',
                'primary'   => 'bg-theme-primary',
                'secondary' => 'bg-theme-secondary',
                'surface'   => 'bg-theme-surface',
                'muted'     => 'bg-theme-muted',
                'light'     => 'bg-gray-light',
                // ── Admin Brand custom color slots (Phase 2) ───────
                'custom_1'  => 'bg-admin-custom-1',
                'custom_2'  => 'bg-admin-custom-2',
                'custom_3'  => 'bg-admin-custom-3',
                'custom_4'  => 'bg-admin-custom-4',
                'custom_5'  => 'bg-admin-custom-5',
            ],
        ],

        // ─── Text Token ────────────────────────────────────────────────────────
        'text_token' => [
            'key'         => 'text_token',
            'field_type'  => 'select',
            'field_scope' => 'shared',
            'default'     => 'heading',
            'group_name'  => 'design',
            'options'     => [
                ['value' => 'heading',   'label' => 'Heading colour'],
                ['value' => 'body',      'label' => 'Body colour'],
                ['value' => 'primary',   'label' => 'Primary colour'],
                ['value' => 'secondary', 'label' => 'Secondary colour'],
                ['value' => 'white',     'label' => 'White'],
                // ── Admin Brand custom color slots (Phase 2) ───────
                // CSS class → admin-brand.css → --admin-color-custom-N
                ['value' => 'custom_1',  'label' => 'Custom Color 1'],
                ['value' => 'custom_2',  'label' => 'Custom Color 2'],
                ['value' => 'custom_3',  'label' => 'Custom Color 3'],
                ['value' => 'custom_4',  'label' => 'Custom Color 4'],
                ['value' => 'custom_5',  'label' => 'Custom Color 5'],
            ],
            'css_map' => [
                'heading'   => 'text-theme-heading',
                'body'      => 'text-theme-body',
                'primary'   => 'text-theme-primary',
                'secondary' => 'text-theme-secondary',
                'white'     => 'text-white',
                // ── Admin Brand custom color slots (Phase 2) ───────
                'custom_1'  => 'text-admin-custom-1',
                'custom_2'  => 'text-admin-custom-2',
                'custom_3'  => 'text-admin-custom-3',
                'custom_4'  => 'text-admin-custom-4',
                'custom_5'  => 'text-admin-custom-5',
            ],
        ],

        // ─── Image Position ────────────────────────────────────────────────────
        // Already defined in ComponentLibrary → 'image' component.
        // Registered here to give BladeGenerator and PackageGenerator the
        // canonical options + default without duplicating the ComponentLibrary entry.
        'image_position' => [
            'key'         => 'image_position',
            'field_type'  => 'select',
            'field_scope' => 'shared',
            'default'     => 'right',
            'group_name'  => 'image',
            'options'     => [
                ['value' => 'right', 'label' => 'Image on the right'],
                ['value' => 'left',  'label' => 'Image on the left'],
            ],
            // No css_map: image_position is resolved via layout order classes in Blade,
            // not a single utility class. Example: $imageOrder = $image_position === 'left'
            // ? 'order-1 lg:order-1' : 'order-1 lg:order-2'
            'css_map' => [],
        ],

        // ─── Section Spacing ───────────────────────────────────────────────────
        // Unified vertical spacing control — replaces the individual padding_top /
        // padding_bottom pair, which exposes implementation details to editors.
        'section_spacing' => [
            'key'         => 'section_spacing',
            'field_type'  => 'select',
            'field_scope' => 'shared',
            'default'     => 'md',
            'group_name'  => 'design',
            'options'     => [
                ['value' => 'none', 'label' => 'No spacing'],
                ['value' => 'sm',   'label' => 'Small spacing'],
                ['value' => 'md',   'label' => 'Medium spacing'],
                ['value' => 'lg',   'label' => 'Large spacing'],
                ['value' => 'xl',   'label' => 'Extra large spacing'],
            ],
            'css_map' => [
                'none' => 'py-0',
                'sm'   => 'py-8 md:py-12',
                'md'   => 'py-16 md:py-24',
                'lg'   => 'py-24 md:py-32',
                'xl'   => 'py-32 md:py-40',
            ],
        ],

        // ─── Container Width ───────────────────────────────────────────────────
        // Controls the max-width of the inner container div.
        'container_width' => [
            'key'         => 'container_width',
            'field_type'  => 'select',
            'field_scope' => 'shared',
            'default'     => 'default',
            'group_name'  => 'design',
            'options'     => [
                ['value' => 'narrow',  'label' => 'Narrow (max-w-3xl)'],
                ['value' => 'default', 'label' => 'Default (max-w-5xl)'],
                ['value' => 'wide',    'label' => 'Wide (max-w-7xl)'],
                ['value' => 'full',    'label' => 'Full width'],
            ],
            'css_map' => [
                'narrow'  => 'max-w-3xl',
                'default' => 'max-w-5xl',
                'wide'    => 'max-w-7xl',
                'full'    => 'max-w-full',
            ],
        ],

    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return all registered token field_keys.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::ALL_TOKENS);
    }

    /**
     * Return true if the given field_key is a registered design token.
     */
    public static function has(string $key): bool
    {
        return isset(self::ALL_TOKENS[$key]);
    }

    /**
     * Return the full token definition for a given key, or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        return self::ALL_TOKENS[$key] ?? null;
    }

    /**
     * Return the options array for a given token.
     * Each element: ['value' => string, 'label' => string]
     *
     * Returns [] if the token is not registered.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(string $key): array
    {
        return self::ALL_TOKENS[$key]['options'] ?? [];
    }

    /**
     * Return valid option values (strings only) for a given token.
     * Useful for validation Rule::in([...]).
     *
     * @return list<string>
     */
    public static function validValues(string $key): array
    {
        return array_column(self::ALL_TOKENS[$key]['options'] ?? [], 'value');
    }

    /**
     * Return the default value for a given token, or null if not registered.
     */
    public static function defaultValue(string $key): ?string
    {
        return self::ALL_TOKENS[$key]['default'] ?? null;
    }

    /**
     * Resolve a token value to its Tailwind CSS class.
     *
     * Falls back to the token's default value if $value is null or empty.
     * Falls back to an empty string if the value is not in the css_map.
     *
     * Examples:
     *   resolveClass('background_token', 'primary')  → 'bg-theme-primary'
     *   resolveClass('background_token', null)        → 'bg-theme-muted' (default)
     *   resolveClass('background_token', 'unknown')   → ''
     *   resolveClass('image_position', 'left')        → '' (no css_map for this token)
     */
    public static function resolveClass(string $key, ?string $value): string
    {
        $token = self::ALL_TOKENS[$key] ?? null;

        if ($token === null) {
            return '';
        }

        $resolved = ($value !== null && $value !== '')
            ? $value
            : ($token['default'] ?? '');

        return $token['css_map'][$resolved] ?? '';
    }

    /**
     * Return all token definitions as a flat array.
     * Useful for generating documentation or seeder data.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::ALL_TOKENS;
    }
}
