<?php

namespace App\Support\Sections;

/**
 * Field Preset Library — ready-made groups of fields for common section patterns.
 *
 * Each preset is an ordered array of field attribute maps that can be passed
 * directly to `$sectionDefinition->fields()->create([...])`.
 *
 * Adding a new preset:
 *  1. Add an entry to ALL_PRESETS (key = preset_key, value = metadata + fields).
 *  2. Add the corresponding t('dashboard.Preset_*') translation key in the seeder.
 *  No other file needs to change.
 *
 * Field attribute shape mirrors SectionDefinitionField $fillable:
 *   field_key    string   (snake_case)
 *   label        string   (human label)
 *   field_type   string   (SectionDefinitionField::FIELD_TYPE_*)
 *   field_scope  string   'translatable' | 'shared'
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
     * @return array{label: string, icon: string, color: string, fields: array<int, array<string, mixed>>>}|null
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
    // ─────────────────────────────────────────────────────────────

    private const ALL_PRESETS = [

        // ── 1. Section Intro ────────────────────────────────────
        'section_intro' => [
            'label' => 'Section Intro',
            'icon'  => 'ti-heading',
            'color' => 'indigo',
            'fields' => [
                [
                    'field_key'  => 'eyebrow',
                    'label'      => 'Eyebrow',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'title',
                    'label'      => 'Title',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> true,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'subtitle',
                    'label'      => 'Subtitle',
                    'field_type' => self::TEXTAREA,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ── 2. Description Block ─────────────────────────────────
        'description_block' => [
            'label' => 'Description Block',
            'icon'  => 'ti-text-wrap',
            'color' => 'slate',
            'fields' => [
                [
                    'field_key'  => 'description',
                    'label'      => 'Description',
                    'field_type' => self::TEXTAREA,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ── 3. CTA Button ────────────────────────────────────────
        'cta_button' => [
            'label' => 'CTA Button',
            'icon'  => 'ti-cursor-text',
            'color' => 'emerald',
            'fields' => [
                [
                    'field_key'  => 'button_label',
                    'label'      => 'Button Label',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'button_url',
                    'label'      => 'Button URL',
                    'field_type' => self::URL,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'button_target',
                    'label'      => 'Button Target',
                    'field_type' => self::SELECT,
                    'field_scope'=> self::SHARED,
                    'is_required'=> false,
                    'is_active'  => true,
                    'options'    => [
                        ['value' => '_self',  'label' => 'Same window'],
                        ['value' => '_blank', 'label' => 'New window'],
                    ],
                ],
            ],
        ],

        // ── 4. Features List (Repeater) ──────────────────────────
        'features_list' => [
            'label' => 'Features List',
            'icon'  => 'ti-list-check',
            'color' => 'violet',
            'fields' => [
                [
                    'field_key'  => 'features',
                    'label'      => 'Features',
                    'field_type' => self::REPEATER,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                    'schema'     => [
                        'item_schema' => [
                            ['key' => 'title',      'label' => 'Title',      'type' => 'text',   'required' => true,  'translatable' => true],
                            ['key' => 'icon_source','label' => 'Icon Source','type' => 'select', 'required' => false, 'translatable' => false,
                             'options' => 'class|CSS Class|media|Media Image'],
                            ['key' => 'icon',       'label' => 'Icon Class', 'type' => 'text',   'required' => false, 'translatable' => false],
                            ['key' => 'icon_media', 'label' => 'Icon Image', 'type' => 'media',  'required' => false, 'translatable' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ── 5. Image Block ───────────────────────────────────────
        'image_block' => [
            'label' => 'Image Block',
            'icon'  => 'ti-photo',
            'color' => 'cyan',
            'fields' => [
                [
                    'field_key'  => 'image',
                    'label'      => 'Image',
                    'field_type' => self::MEDIA,
                    'field_scope'=> self::SHARED,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'image_alt',
                    'label'      => 'Image Alt Text',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'image_position',
                    'label'      => 'Image Position',
                    'field_type' => self::SELECT,
                    'field_scope'=> self::SHARED,
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

        // ── 6. Highlight Block ───────────────────────────────────
        'highlight_block' => [
            'label' => 'Highlight Block',
            'icon'  => 'ti-highlight',
            'color' => 'amber',
            'fields' => [
                [
                    'field_key'  => 'highlight_text',
                    'label'      => 'Highlight Text',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ── 7. SEO Block ─────────────────────────────────────────
        'seo_block' => [
            'label' => 'SEO Block',
            'icon'  => 'ti-brand-google',
            'color' => 'blue',
            'fields' => [
                [
                    'field_key'  => 'meta_title',
                    'label'      => 'Meta Title',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'meta_description',
                    'label'      => 'Meta Description',
                    'field_type' => self::TEXTAREA,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

        // ── 8. Complete Content Section ──────────────────────────
        'complete_content' => [
            'label' => 'Complete Content Section',
            'icon'  => 'ti-layout-grid',
            'color' => 'rose',
            'fields' => [
                [
                    'field_key'  => 'eyebrow',
                    'label'      => 'Eyebrow',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'title',
                    'label'      => 'Title',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> true,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'subtitle',
                    'label'      => 'Subtitle',
                    'field_type' => self::TEXTAREA,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'features',
                    'label'      => 'Features',
                    'field_type' => self::REPEATER,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                    'schema'     => [
                        'item_schema' => [
                            ['key' => 'title',      'label' => 'Title',      'type' => 'text',   'required' => true,  'translatable' => true],
                            ['key' => 'icon_source','label' => 'Icon Source','type' => 'select', 'required' => false, 'translatable' => false,
                             'options' => 'class|CSS Class|media|Media Image'],
                            ['key' => 'icon',       'label' => 'Icon Class', 'type' => 'text',   'required' => false, 'translatable' => false],
                            ['key' => 'icon_media', 'label' => 'Icon Image', 'type' => 'media',  'required' => false, 'translatable' => false],
                        ],
                    ],
                ],
                [
                    'field_key'  => 'highlight_text',
                    'label'      => 'Highlight Text',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'button_label',
                    'label'      => 'Button Label',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'button_url',
                    'label'      => 'Button URL',
                    'field_type' => self::URL,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'image',
                    'label'      => 'Image',
                    'field_type' => self::MEDIA,
                    'field_scope'=> self::SHARED,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
                [
                    'field_key'  => 'image_alt',
                    'label'      => 'Image Alt Text',
                    'field_type' => self::TEXT,
                    'field_scope'=> self::TRANSLATABLE,
                    'is_required'=> false,
                    'is_active'  => true,
                ],
            ],
        ],

    ];
}
