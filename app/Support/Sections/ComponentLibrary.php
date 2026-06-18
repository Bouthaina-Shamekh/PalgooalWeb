<?php

namespace App\Support\Sections;

/**
 * Component Library — reusable field groups for Section Templates.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * ARCHITECTURE LAYER
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 *  Field Presets  →  single field group, applied to an existing SectionDefinition
 *  Components     →  canonical field group, composed into Section Templates
 *  Section Templates → full blueprint = components[] + extra_fields[] + blade_stub
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * HOW IT WORKS
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * 1. Each Component defines a named, reusable set of SectionDefinitionField specs.
 * 2. SectionTemplateLibrary references components by key instead of repeating fields.
 * 3. resolveFields() merges the requested components in order, deduplicating by
 *    field_key (first occurrence wins). Extra template-specific fields are appended.
 * 4. sort_order is assigned sequentially across the merged result.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * FIELD SCOPE RULES (Multi-Tenant Platform)
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * TRANSLATABLE: text, textarea, url (incl. button_url), image_alt, meta_*
 * SHARED:       image, icon, icon_media, icon_source, image_position,
 *               button_target, layout_style, background_color
 *
 * Full rationale: docs/FIELD_SCOPE_ARCHITECTURE.md
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * ADDING A NEW COMPONENT
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * 1. Add an entry to ALL_COMPONENTS. Key = lowercase-dash slug.
 * 2. Populate `name`, `description`, and `fields[]`.
 * 3. Use it in SectionTemplateLibrary by referencing the key in `components[]`.
 * No other file needs to change.
 */
class ComponentLibrary
{
    // ── Scope shortcuts ───────────────────────────────────────────
    private const T = 'translatable';
    private const S = 'shared';

    // ── Type shortcuts ────────────────────────────────────────────
    private const TEXT     = 'text';
    private const TEXTAREA = 'textarea';
    private const URL      = 'url';
    private const MEDIA    = 'media';
    private const SELECT   = 'select';
    private const REPEATER = 'repeater';

    // ─────────────────────────────────────────────────────────────

    public static function all(): array
    {
        return self::ALL_COMPONENTS;
    }

    public static function get(string $key): ?array
    {
        return self::ALL_COMPONENTS[$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(self::ALL_COMPONENTS);
    }

    /**
     * Resolve a list of component keys + optional extra fields into a flat,
     * deduplicated, sort_order-assigned field list ready for DB creation.
     *
     * Deduplication: first occurrence of a field_key wins (earlier component
     * takes priority). Extra fields are appended after all component fields.
     *
     * @param  string[]             $componentKeys   Component keys to merge
     * @param  array<int, array>    $extraFields     Template-specific fields (appended last)
     * @return array<int, array>                     Flat field list with sort_order
     */
    public static function resolveFields(array $componentKeys, array $extraFields = []): array
    {
        $seen   = [];   // field_key → true, for O(1) dedup
        $result = [];

        // 1. Merge component fields in order
        foreach ($componentKeys as $componentKey) {
            $component = self::get($componentKey);

            if (! is_array($component)) {
                continue; // silently skip unknown component keys
            }

            foreach ($component['fields'] ?? [] as $fieldDef) {
                $fieldKey = (string) ($fieldDef['field_key'] ?? '');

                if ($fieldKey === '' || isset($seen[$fieldKey])) {
                    continue; // skip empty or duplicate
                }

                $seen[$fieldKey] = true;
                $result[]        = $fieldDef;
            }
        }

        // 2. Append extra (template-specific) fields
        foreach ($extraFields as $fieldDef) {
            $fieldKey = (string) ($fieldDef['field_key'] ?? '');

            if ($fieldKey === '' || isset($seen[$fieldKey])) {
                continue;
            }

            $seen[$fieldKey] = true;
            $result[]        = $fieldDef;
        }

        // 3. Assign sequential sort_order (1-based)
        foreach ($result as $index => &$fieldDef) {
            $fieldDef['sort_order'] = $index + 1;
        }
        unset($fieldDef);

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // Component definitions
    // ─────────────────────────────────────────────────────────────

    private const ALL_COMPONENTS = [

        // ══════════════════════════════════════════════════════════
        // intro — Section heading block (eyebrow + title + subtitle)
        // Used in: Hero, Features Grid, Content Showcase, CTA Banner,
        //          FAQ, Testimonials
        // ══════════════════════════════════════════════════════════
        'intro' => [
            'name'        => 'Intro',
            'icon'        => 'ti-text-size',
            'color'       => 'indigo',
            'description' => 'Section heading: eyebrow label, main title, and subtitle.',
            'fields' => [
                // eyebrow — translatable: short label above the title, always differs by locale
                ['field_key' => 'eyebrow',  'label' => 'Eyebrow',  'field_type' => self::TEXT,     'field_scope' => self::T, 'is_required' => false],
                // title — translatable: primary heading, always differs by locale
                ['field_key' => 'title',    'label' => 'Title',    'field_type' => self::TEXT,     'field_scope' => self::T, 'is_required' => true],
                // subtitle — translatable: supporting text under the heading
                ['field_key' => 'subtitle', 'label' => 'Subtitle', 'field_type' => self::TEXTAREA, 'field_scope' => self::T, 'is_required' => false],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // description — Rich/long description block
        // ══════════════════════════════════════════════════════════
        'description' => [
            'name'        => 'Description',
            'icon'        => 'ti-align-left',
            'color'       => 'slate',
            'description' => 'Long-form description or body text field.',
            'fields' => [
                // description — translatable: body copy always differs by locale
                ['field_key' => 'description', 'label' => 'Description', 'field_type' => self::TEXTAREA, 'field_scope' => self::T, 'is_required' => false],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // cta — Call-to-action button
        // button_url is TRANSLATABLE: locale-prefixed routes, WhatsApp numbers,
        // and localized landing pages differ between languages.
        // See docs/FIELD_SCOPE_ARCHITECTURE.md for full rationale.
        // button_target is SHARED: browser behaviour is language-agnostic.
        // ══════════════════════════════════════════════════════════
        'cta' => [
            'name'        => 'CTA Button',
            'icon'        => 'ti-cursor-text',
            'color'       => 'violet',
            'description' => 'Primary call-to-action: button label, URL, and target.',
            'fields' => [
                // button_label — translatable: button text differs by locale
                ['field_key' => 'button_label',  'label' => 'Button Label',  'field_type' => self::TEXT,   'field_scope' => self::T, 'is_required' => false],
                // button_url — translatable: URL differs by locale (prefixes, WhatsApp, UTM)
                ['field_key' => 'button_url',    'label' => 'Button URL',    'field_type' => self::URL,    'field_scope' => self::T, 'is_required' => false],
                // button_target — shared: _self/_blank is a browser behaviour, not localized
                ['field_key' => 'button_target', 'label' => 'Button Target', 'field_type' => self::SELECT, 'field_scope' => self::S, 'is_required' => false,
                 'options' => [['value' => '_self', 'label' => 'Same window'], ['value' => '_blank', 'label' => 'New window']]],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // image — Media + alt text + position
        // image is SHARED: the visual asset does not change by locale.
        // image_alt is TRANSLATABLE: alt text is SEO copy, differs by locale.
        // image_position is SHARED: layout decision, not locale-specific.
        // ══════════════════════════════════════════════════════════
        'image' => [
            'name'        => 'Image',
            'icon'        => 'ti-photo',
            'color'       => 'emerald',
            'description' => 'Section image with alt text and layout position.',
            'fields' => [
                // image — shared: the same visual asset for all locales
                ['field_key' => 'image',          'label' => 'Image',          'field_type' => self::MEDIA,  'field_scope' => self::S, 'is_required' => false],
                // image_alt — translatable: SEO/accessibility text, differs by locale
                ['field_key' => 'image_alt',      'label' => 'Image Alt',      'field_type' => self::TEXT,   'field_scope' => self::T, 'is_required' => false],
                // image_position — shared: left/right is a layout decision, not localized
                ['field_key' => 'image_position', 'label' => 'Image Position', 'field_type' => self::SELECT, 'field_scope' => self::S, 'is_required' => false,
                 'options' => [['value' => 'left', 'label' => 'Left'], ['value' => 'right', 'label' => 'Right'], ['value' => 'center', 'label' => 'Center']]],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // features — Repeater of feature items with icon support
        // Repeater scope = translatable (items contain translated text).
        // icon_* sub-fields are shared (visual assets, language-agnostic).
        // ══════════════════════════════════════════════════════════
        'features' => [
            'name'        => 'Features List',
            'icon'        => 'ti-list-check',
            'color'       => 'rose',
            'description' => 'Repeater of feature cards: title, description, and icon.',
            'fields' => [
                [
                    'field_key'   => 'features',
                    'label'       => 'Features',
                    'field_type'  => self::REPEATER,
                    'field_scope' => self::T,   // repeater holds translated text items
                    'is_required' => false,
                    'schema'      => [
                        'item_schema' => [
                            // title — translatable: feature headline
                            ['key' => 'title',       'label' => 'Title',       'type' => 'text',    'required' => true,  'translatable' => true],
                            // description — translatable: feature body copy
                            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea','required' => false, 'translatable' => true],
                            // icon_source — shared: class vs media choice is layout, not locale
                            ['key' => 'icon_source', 'label' => 'Icon Source', 'type' => 'select',  'required' => false, 'translatable' => false,
                             'options' => 'class|CSS Class|media|Media Image'],
                            // icon — shared: CSS class identifier is language-agnostic
                            ['key' => 'icon',        'label' => 'Icon Class',  'type' => 'text',    'required' => false, 'translatable' => false],
                            // icon_media — shared: visual asset, same for all locales
                            ['key' => 'icon_media',  'label' => 'Icon Image',  'type' => 'media',   'required' => false, 'translatable' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // highlight — Callout / badge text
        // ══════════════════════════════════════════════════════════
        'highlight' => [
            'name'        => 'Highlight',
            'icon'        => 'ti-tag',
            'color'       => 'amber',
            'description' => 'Short callout or badge text (e.g. "Most Popular", "New").',
            'fields' => [
                // highlight_text — translatable: badge copy differs by locale
                ['field_key' => 'highlight_text', 'label' => 'Highlight Text', 'field_type' => self::TEXT, 'field_scope' => self::T, 'is_required' => false],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // faq — Frequently Asked Questions repeater
        // ══════════════════════════════════════════════════════════
        'faq' => [
            'name'        => 'FAQ',
            'icon'        => 'ti-help',
            'color'       => 'amber',
            'description' => 'Repeater of FAQ items: question and answer pairs.',
            'fields' => [
                [
                    'field_key'   => 'faqs',
                    'label'       => 'FAQs',
                    'field_type'  => self::REPEATER,
                    'field_scope' => self::T,
                    'is_required' => false,
                    'schema'      => [
                        'item_schema' => [
                            ['key' => 'question', 'label' => 'Question', 'type' => 'text',     'required' => true,  'translatable' => true],
                            ['key' => 'answer',   'label' => 'Answer',   'type' => 'textarea', 'required' => true,  'translatable' => true],
                        ],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // testimonials — Customer testimonials repeater
        // name, position, quote — translatable (may differ per locale)
        // company, avatar — shared (same entity/image regardless of locale)
        // ══════════════════════════════════════════════════════════
        'testimonials' => [
            'name'        => 'Testimonials',
            'icon'        => 'ti-quote',
            'color'       => 'cyan',
            'description' => 'Repeater of customer testimonials: name, position, quote, avatar.',
            'fields' => [
                [
                    'field_key'   => 'testimonials',
                    'label'       => 'Testimonials',
                    'field_type'  => self::REPEATER,
                    'field_scope' => self::T,
                    'is_required' => false,
                    'schema'      => [
                        'item_schema' => [
                            // name — translatable: display name may be transliterated
                            ['key' => 'name',     'label' => 'Name',     'type' => 'text',    'required' => true,  'translatable' => true],
                            // position — translatable: job title wording differs by locale
                            ['key' => 'position', 'label' => 'Position', 'type' => 'text',    'required' => false, 'translatable' => true],
                            // company — shared: company name is the same across locales
                            ['key' => 'company',  'label' => 'Company',  'type' => 'text',    'required' => false, 'translatable' => false],
                            // quote — translatable: customer quote is per locale
                            ['key' => 'quote',    'label' => 'Quote',    'type' => 'textarea','required' => true,  'translatable' => true],
                            // avatar — shared: photo does not change by locale
                            ['key' => 'avatar',   'label' => 'Avatar',   'type' => 'media',   'required' => false, 'translatable' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════
        // seo — Meta title and description for search engines
        // ══════════════════════════════════════════════════════════
        'seo' => [
            'name'        => 'SEO',
            'icon'        => 'ti-search',
            'color'       => 'slate',
            'description' => 'Search engine meta: meta title and meta description.',
            'fields' => [
                // meta_title — translatable: SEO title differs by locale
                ['field_key' => 'meta_title',       'label' => 'Meta Title',       'field_type' => self::TEXT,     'field_scope' => self::T, 'is_required' => false],
                // meta_description — translatable: SEO description differs by locale
                ['field_key' => 'meta_description', 'label' => 'Meta Description', 'field_type' => self::TEXTAREA, 'field_scope' => self::T, 'is_required' => false],
            ],
        ],

    ];
}
