<?php

namespace App\Support\Sections;

/**
 * Field Preset Library — ready-made groups of fields for common section patterns.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * ARCHITECTURAL PRINCIPLE — MULTI-TENANT PLATFORM SCOPE RULES
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * This platform serves thousands of templates, clients, and locales.
 * Field scopes MUST be decided based on platform-wide reusability, not on
 * what Palgoals.com itself happens to need today.
 *
 * ┌─────────────────────┬──────────────────────────────────────────────────┐
 * │ TRANSLATABLE        │ Value may differ between language versions        │
 * │                     │ of the same page/template/site.                  │
 * │                     │                                                  │
 * │ Examples:           │ eyebrow, title, subtitle, description,           │
 * │                     │ highlight_text, button_label, button_url,        │
 * │                     │ image_alt, meta_title, meta_description,         │
 * │                     │ features[].title                                 │
 * ├─────────────────────┼──────────────────────────────────────────────────┤
 * │ SHARED              │ Value is identical across all language versions  │
 * │                     │ of the same section. Layout, media assets,       │
 * │                     │ icon identifiers, and behavioral settings.       │
 * │                     │                                                  │
 * │ Examples:           │ image, icon, icon_media, icon_source,            │
 * │                     │ image_position, button_target, layout_style,     │
 * │                     │ theme_variant, background_color                  │
 * └─────────────────────┴──────────────────────────────────────────────────┘
 *
 * WHY button_url IS TRANSLATABLE (not Shared):
 *   Different locales commonly use different URL structures:
 *     ar  →  /contact         /services        /ar/pricing
 *     en  →  /en/contact      /en/services     /en/pricing
 *   WhatsApp links, localized landing pages, and locale-prefixed routes
 *   all require per-language URLs. Treating button_url as Shared would break
 *   every multi-locale template that uses locale-specific paths.
 *
 * WHY image IS SHARED (not Translatable):
 *   The visual asset itself (file, dimensions, composition) does not change
 *   between languages. image_alt IS translatable because the text description
 *   of that asset must be localized for accessibility and SEO.
 *
 * WHY icon / icon_media IS SHARED:
 *   An icon represents a universal visual symbol. The same icon is used
 *   regardless of locale. Only the accompanying text label is translatable.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * ADDING A NEW PRESET
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * 1. Add an entry to ALL_PRESETS (key = preset_key, value = metadata + fields).
 * 2. For each field, justify the scope choice using the rules above.
 * 3. Add the corresponding t('dashboard.Preset_*') translation key in the seeder.
 * 4. No other file needs to change.
 *
 * Field attribute shape mirrors SectionDefinitionField $fillable:
 *   field_key    string    (snake_case)
 *   label        string    (human label — English; translated at render time)
 *   field_type   string    (SectionDefinitionField::FIELD_TYPE_*)
 *   field_scope  string    'translatable' | 'shared'  ← see rules above
 *   is_required  bool
 *   is_active    bool
 *   schema       array|null  { item_schema: [...] } for repeater fields only
 */
class FieldPresetLibrary
{
    // ─────────────────────────────────────────────────────────────
    // Scope aliases
    // ─────────────────────────────────────────────────────────────
    private const TRANSLATABLE = 'translatable';
    private const SHARED       = 'shared';

    // ─────────────────────────────────────────────────────────────
    // Type aliases
    // ─────────────────────────────────────────────────────────────
    private const TEXT     = 'text';
    private const TEXTAREA = 'textarea';
    private const URL      = 'url';
    private const MEDIA    = 'media';
    private const SELECT   = 'select';
    private const REPEATER = 'repeater';

    /**
     * Return all registered presets.
     *
     * @return array<string, array{label: string, icon: string, color: string, fields: array<int, array<string, mixed>>}>
     */
    public static function all(): array
    {
        return self::ALL_PRESETS;
    }

    /**
     * Return a single preset by key, or null if not found.
     *
     * @return array{label: string, icon: string, color: string, fields: array<int, array<string, mixed>>}|null
     */
    public static function get(string $key): ?array
    {
        return self::ALL_PRESETS[$key] ?? null;
    }

    /**
     * Return all preset keys.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::ALL_PRESETS);
    }

    // ─────────────────────────────────────────────────────────────
    // Preset definitions
    // Each scope annotation explains the multi-tenant rationale.
    // ─────────────────────────────────────────────────────────────

    private const ALL_PRESETS = [

        // ══════════════════════════════════════════════════════════
        // 1. Section Intro
        // ══════════════════════════════════════════════════════════
        'section_intro' => [
            'label' => 'Section Intro',
            'icon'  => 'ti-heading',
            'color' => 'indigo',
            'fields' => [
                [
                    'field_key'  => 'eyebrow',
                    'label'      => 'Eyebrow',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE, // text label — must be localized
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'title',
                    'label'      => 'Title',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE, // primary heading — always localized
                    'is_required'=> true,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'subtitle',
                    'label'      => 'Subtitle',
                    'field_type' => self::TEXTAREA,
                    'field_scope'=> self::TRANSLATABLE, // secondary text — always localized
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // 2. Description Block
        // ══════════════════════════════════════════════════════════
        'description_block' => [
            'label' => 'Description Block',
            'icon'  => 'ti-text-wrap',
            'color' => 'slate',
            'fields' => [
                [
                    'field_key'  => 'description',
                    'label'      => 'Description',
                    'field_type' => self::TEXTAREA,
                    'field_scope'=> self::TRANSLATABLE, // body text — always localized
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // 3. CTA Button
        // ══════════════════════════════════════════════════════════
        'cta_button' => [
            'label' => 'CTA Button',
            'icon'  => 'ti-cursor-text',
            'color' => 'emerald',
            'fields' => [
                [
                    'field_key'  => 'button_label',
                    'label'      => 'Button Label',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE, // button text — always localized
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'button_url',
                    'label'      => 'Button URL',
                    'field_type' => self::URL,
                    'field_scope'=> self::TRANSLATABLE, // TRANSLATABLE — different locales use
                                                        // different URL structures, locale prefixes,
                                                        // WhatsApp numbers, or landing page paths.
                                                        // e.g. ar→/contact, en→/en/contact
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'button_target',
                    'label'      => 'Button Target',
                    'field_type' => self::SELECT,
                    'field_scope'=> self::SHARED,       // SHARED — _self/_blank is a browser
                                                        // behavior choice, not language-specific
                    'is_required'=> false,
                    'is_active'  => true,
                    'options'    => [
                        ['value' => '_self',  'label' => 'Same window'],
                        ['value' => '_blank', 'label' => 'New window'],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // 4. Features List (Repeater)
        // ══════════════════════════════════════════════════════════
        'features_list' => [
            'label' => 'Features List',
            'icon'  => 'ti-list-check',
            'color' => 'violet',
            'fields' => [
                [
                    'field_key'  => 'features',
                    'label'      => 'Features',
                    'field_type' => self::REPEATER,
                    'field_scope'=> self::TRANSLATABLE, // repeater stored per-locale because
                                                        // item titles differ between languages
                    'is_required'=> false,
                    'is_active'  => true,
                    'schema'     => [
                        'item_schema' => [
                            [
                                // TRANSLATABLE — feature title is always localized
                                'key'          => 'title',
                                'label'        => 'Title',
                                'type'         => 'text',
                                'required'     => true,
                                'translatable' => true,
                            ],
                            [
                                // SHARED — whether to use a CSS class or media image
                                // is a design decision, not a language decision
                                'key'          => 'icon_source',
                                'label'        => 'Icon Source',
                                'type'         => 'select',
                                'required'     => false,
                                'translatable' => false,
                                'options'      => 'class|CSS Class|media|Media Image',
                            ],
                            [
                                // SHARED — CSS icon class identifier (e.g. ti-star)
                                // is a visual symbol, language-agnostic
                                'key'          => 'icon',
                                'label'        => 'Icon Class',
                                'type'         => 'text',
                                'required'     => false,
                                'translatable' => false,
                            ],
                            [
                                // SHARED — the image file/asset does not change per locale;
                                // only its alt text (if added separately) would be translatable
                                'key'          => 'icon_media',
                                'label'        => 'Icon Image',
                                'type'         => 'media',
                                'required'     => false,
                                'translatable' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // 5. Image Block
        // ══════════════════════════════════════════════════════════
        'image_block' => [
            'label' => 'Image Block',
            'icon'  => 'ti-photo',
            'color' => 'cyan',
            'fields' => [
                [
                    'field_key'  => 'image',
                    'label'      => 'Image',
                    'field_type' => self::MEDIA,
                    'field_scope'=> self::SHARED,       // SHARED — visual asset is the same
                                                        // regardless of locale
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'image_alt',
                    'label'      => 'Image Alt Text',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE, // TRANSLATABLE — alt text must be
                                                        // localized for accessibility & SEO
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'image_position',
                    'label'      => 'Image Position',
                    'field_type' => self::SELECT,
                    'field_scope'=> self::SHARED,       // SHARED — layout choice (left/right/center)
                                                        // is a design decision, not locale-specific
                    'is_required'=> false,
                    'is_active'  => true,
                    'options'    => [
                        ['value' => 'left',   'label' => 'Left'],
                        ['value' => 'right',  'label' => 'Right'],
                        ['value' => 'center', 'label' => 'Center'],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // 6. Highlight Block
        // ══════════════════════════════════════════════════════════
        'highlight_block' => [
            'label' => 'Highlight Block',
            'icon'  => 'ti-highlight',
            'color' => 'amber',
            'fields' => [
                [
                    'field_key'  => 'highlight_text',
                    'label'      => 'Highlight Text',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE, // callout/badge text — always localized
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // 7. SEO Block
        // ══════════════════════════════════════════════════════════
        'seo_block' => [
            'label' => 'SEO Block',
            'icon'  => 'ti-brand-google',
            'color' => 'blue',
            'fields' => [
                [
                    'field_key'  => 'meta_title',
                    'label'      => 'Meta Title',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE, // <title> tag — must be localized for SEO
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'meta_description',
                    'label'      => 'Meta Description',
                    'field_type' => self::TEXTAREA,
                    'field_scope'=> self::TRANSLATABLE, // meta description — localized for SEO
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // 8. Complete Content Section
        // Combines all common content fields in canonical order.
        // Scope rationale: same as individual presets above.
        // ══════════════════════════════════════════════════════════
        'complete_content' => [
            'label' => 'Complete Content Section',
            'icon'  => 'ti-layout-grid',
            'color' => 'rose',
            'fields' => [
                ['field_key' => 'eyebrow',        'label' => 'Eyebrow',        'field_type' => self::TEXT,     'field_scope' => self::TRANSLATABLE, 'is_required' => false, 'is_active' => true],
                ['field_key' => 'title',           'label' => 'Title',          'field_type' => self::TEXT,     'field_scope' => self::TRANSLATABLE, 'is_required' => true,  'is_active' => true],
                ['field_key' => 'subtitle',        'label' => 'Subtitle',       'field_type' => self::TEXTAREA, 'field_scope' => self::TRANSLATABLE, 'is_required' => false, 'is_active' => true],
                [
                    'field_key'  => 'features',
                    'label'      => 'Features',
                    'field_type' => self::REPEATER,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                    'schema'     => [
                        'item_schema' => [
                            ['key' => 'title',       'label' => 'Title',       'type' => 'text',   'required' => true,  'translatable' => true],
                            ['key' => 'icon_source', 'label' => 'Icon Source', 'type' => 'select', 'required' => false, 'translatable' => false, 'options' => 'class|CSS Class|media|Media Image'],
                            ['key' => 'icon',        'label' => 'Icon Class',  'type' => 'text',   'required' => false, 'translatable' => false],
                            ['key' => 'icon_media',  'label' => 'Icon Image',  'type' => 'media',  'required' => false, 'translatable' => false],
                        ],
                    ],
                ],
                ['field_key' => 'highlight_text', 'label' => 'Highlight Text', 'field_type' => self::TEXT,  'field_scope' => self::TRANSLATABLE, 'is_required' => false, 'is_active' => true],
                ['field_key' => 'button_label',   'label' => 'Button Label',   'field_type' => self::TEXT,  'field_scope' => self::TRANSLATABLE, 'is_required' => false, 'is_active' => true],
                ['field_key' => 'button_url',     'label' => 'Button URL',     'field_type' => self::URL,   'field_scope' => self::TRANSLATABLE, 'is_required' => false, 'is_active' => true], // see CTA rationale
                ['field_key' => 'image',          'label' => 'Image',          'field_type' => self::MEDIA, 'field_scope' => self::SHARED,       'is_required' => false, 'is_active' => true], // visual asset — locale-agnostic
                ['field_key' => 'image_alt',      'label' => 'Image Alt Text', 'field_type' => self::TEXT,  'field_scope' => self::TRANSLATABLE, 'is_required' => false, 'is_active' => true],
            ],
        ],

    ];
}
