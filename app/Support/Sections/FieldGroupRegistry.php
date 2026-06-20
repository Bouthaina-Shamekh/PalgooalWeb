<?php

namespace App\Support\Sections;

use Illuminate\Support\Str;

/**
 * Central registry that maps group_key → translation key + English fallback.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * v2 — Full Multi-Language Support (2026-06-20)
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Each registered group maps to a t() translation key in the `section_groups`
 * namespace.  Arabic translations live in DashboardTranslationsSeeder.
 * Any future language (French, Turkish, etc.) only requires adding rows to
 * the translation_values table — no PHP code change is needed.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * USAGE
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Developers store lowercase slug keys in section_definition_fields.group_name:
 *
 *   group_name = 'intro'         → t('section_groups.intro', 'Introduction')
 *   group_name = 'cta'           → t('section_groups.cta',  'Call to Action')
 *   group_name = 'animations'    → 'Animations'  (graceful fallback — no crash)
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * DESIGN PRINCIPLES
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *  • No hardcoded locale arrays — registry is language-count-independent.
 *  • Translation key for every group: 'section_groups.{group_key}'.
 *  • English fallback stored here as the t() default; other languages come
 *    from translation_values (DB) via the t() helper.
 *  • Unknown keys never crash — fall back to Str::headline().
 *  • Third-party template authors may use custom group keys freely.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ADDING A NEW GROUP
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * 1. Add one entry to KNOWN_GROUPS: 'my_group' => 'My English Label'
 * 2. Add an Arabic row to DashboardTranslationsSeeder:
 *    'section_groups.my_group' => 'التسمية العربية'
 * 3. For French/other: INSERT into translation_values for locale 'fr'.
 * No other PHP file needs to change.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ADDING A NEW LANGUAGE (e.g. French)
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * No PHP change needed. Add rows to translation_values for locale 'fr':
 *   section_groups.background  → Arrière-plan
 *   section_groups.cta         → Appel à l'action
 *   ...
 * The t() helper picks them up automatically via app()->getLocale().
 */
class FieldGroupRegistry
{
    /**
     * Known canonical group keys → English label (used as t() fallback).
     *
     * The translation key is always: 'section_groups.' . $group_key
     * Translations for Arabic and other locales live in translation_values (DB).
     *
     * Keep sorted alphabetically by key for easy diffing.
     *
     * @var array<string, string>
     */
    private const KNOWN_GROUPS = [
        'background'   => 'Background',
        'content'      => 'Content',
        'cta'          => 'Call to Action',
        'description'  => 'Description',
        'design'       => 'Design',
        'faq'          => 'FAQ',
        'features'     => 'Features',
        'general'      => 'General',
        'highlight'    => 'Highlight',
        'image'        => 'Image',
        'intro'        => 'Introduction',
        'media'        => 'Media',
        'seo'          => 'SEO',
        'testimonials' => 'Testimonials',
        'background-color' => 'Background Color'
    ];

    /**
     * Resolve a human-readable label for the current app locale.
     *
     * Resolution chain:
     *   1. Known group → t('section_groups.{key}', '{English fallback}')
     *      The t() helper looks up translation_values for app()->getLocale().
     *      If the locale has no matching row, t() returns the English fallback.
     *   2. Unknown group → Str::headline(str_replace(['_', '-'], ' ', $key))
     *
     * @param  string  $groupKey  Value stored in group_name (e.g. 'cta', 'intro')
     * @return string             Localized, human-readable label
     */
    public static function label(string $groupKey): string
    {
        if (isset(self::KNOWN_GROUPS[$groupKey])) {
            return t('section_groups.' . $groupKey, self::KNOWN_GROUPS[$groupKey]);
        }

        return self::humanize($groupKey);
    }

    /**
     * Whether this group key is registered in the canonical list.
     *
     * Useful for UI hints when a developer enters an unrecognized group key.
     */
    public static function isKnown(string $groupKey): bool
    {
        return isset(self::KNOWN_GROUPS[$groupKey]);
    }

    /**
     * All registered group keys, sorted alphabetically.
     *
     * @return string[]
     */
    public static function keys(): array
    {
        return array_keys(self::KNOWN_GROUPS);
    }

    /**
     * All registered groups as [key => label] for the current app locale.
     *
     * Useful for building admin UI suggestion dropdowns.
     *
     * @return array<string, string>
     */
    public static function allLabeled(): array
    {
        $result = [];
        foreach (array_keys(self::KNOWN_GROUPS) as $key) {
            $result[$key] = static::label($key);
        }
        return $result;
    }

    /**
     * The canonical t() translation key for a registered group.
     *
     * Returns null for unknown group keys.
     *
     * @param  string  $groupKey
     * @return string|null
     */
    public static function translationKey(string $groupKey): ?string
    {
        return isset(self::KNOWN_GROUPS[$groupKey])
            ? 'section_groups.' . $groupKey
            : null;
    }

    /**
     * Auto-humanize an unknown group key as a safe English fallback.
     *
     * Examples:
     *   'animations'       → 'Animations'
     *   'pricing_table'    → 'Pricing Table'
     *   'hero-statistics'  → 'Hero Statistics'
     */
    private static function humanize(string $groupKey): string
    {
        return Str::headline(str_replace(['_', '-'], ' ', $groupKey));
    }
}
