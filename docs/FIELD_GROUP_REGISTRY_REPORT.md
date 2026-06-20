# Field Group Registry — Implementation Report

> Date: 2026-06-20
> Status: Completed — Phase 1 + Phase 2
> Architecture doc: docs/FIELD_GROUP_REGISTRY_ARCHITECTURE.md

---

## Summary

Replaced hardcoded `Str::headline()` group label generation with a central `FieldGroupRegistry` that provides localized group labels (Arabic + English) for the Page Builder section editor. Also propagated semantic `group_name` keys into `ComponentLibrary` so all sections created from templates are auto-grouped.

---

## Files Changed

| File | Change |
|------|--------|
| `app/Support/Sections/FieldGroupRegistry.php` | **Created** — 14 known groups, AR + EN translations, graceful fallback |
| `app/Support/Sections/DynamicSectionEditorRenderer.php` | 1-line fix: `Str::headline()` → `FieldGroupRegistry::label()` |
| `app/Http/Controllers/Admin/SectionDefinitionFieldController.php` | `formViewData()` merges Registry keys into `$groupSuggestions` |
| `app/Support/Sections/ComponentLibrary.php` | Added `'group_name'` to all 16 field definitions across 9 components |

No migration. No seeder. No DB change.

---

## Phase 1 — Core Registry

### `FieldGroupRegistry.php`

Registered 14 canonical group keys:

| Key | Arabic | English |
|-----|--------|---------|
| `background` | الخلفية | Background |
| `content` | المحتوى | Content |
| `cta` | الدعوة للعمل | Call to Action |
| `description` | الوصف | Description |
| `design` | التنسيق | Design |
| `faq` | الأسئلة الشائعة | FAQ |
| `features` | المميزات | Features |
| `general` | عام | General |
| `highlight` | النص المميَّز | Highlight |
| `image` | الصورة | Image |
| `intro` | المقدمة | Introduction |
| `media` | الوسائط | Media |
| `seo` | تحسين البحث | SEO |
| `testimonials` | التقييمات | Testimonials |

Resolution chain for any key:
1. Known group + current locale → exact translation
2. Known group + locale not found → `'en'` label
3. Unknown group → `Str::headline(str_replace(['_', '-'], ' ', $key))`

### `DynamicSectionEditorRenderer.php`

```php
// BEFORE:
'label' => Str::headline(str_replace(['_', '-'], ' ', $groupKey)),

// AFTER:
'label' => FieldGroupRegistry::label($groupKey),
```

### `SectionDefinitionFieldController.php`

```php
// BEFORE: only sibling group_names from DB
$groupSuggestions = $sectionDefinition->fields()
    ->whereNotNull('group_name')
    ->pluck('group_name')->unique()->values()->all();

// AFTER: Registry keys first, then custom sibling keys
$siblingGroups = $sectionDefinition->fields()
    ->whereNotNull('group_name')->pluck('group_name')->unique()->values()->all();

$groupSuggestions = array_values(array_unique(
    array_merge(FieldGroupRegistry::keys(), $siblingGroups)
));
```

---

## Phase 2 — ComponentLibrary Groups

Added `'group_name'` to all 16 field definitions:

| Component | Fields | group_name assigned |
|-----------|--------|---------------------|
| `intro` | eyebrow, title, subtitle | `'intro'` |
| `description` | description | `'description'` |
| `cta` | button_label, button_url, button_target | `'cta'` |
| `image` | image, image_alt, image_position | `'image'` |
| `features` | features (repeater) | `'features'` |
| `highlight` | highlight_text | `'highlight'` |
| `faq` | faqs (repeater) | `'faq'` |
| `testimonials` | testimonials (repeater) | `'testimonials'` |
| `seo` | meta_title, meta_description | `'seo'` |

**Effect**: Any `SectionDefinition` created via `storeFromTemplate()` or `SectionPackageGenerator` will now have fields with correct `group_name` values, because `ComponentLibrary::resolveFields()` passes the full field array (including `group_name`) to `SectionDefinitionField::create()`.

---

## Validation

### Test 1 — `cta` group label in Arabic

**Before:** `Str::headline('cta')` → `'Cta'`
**After:** `FieldGroupRegistry::label('cta')` with locale `ar` → `'الدعوة للعمل'`

### Test 2 — `seo` group label

**Before:** `'Seo'`
**After:** Arabic → `'تحسين البحث'`, English → `'SEO'`

### Test 3 — `faq` group label

**Before:** `'Faq'`
**After:** Arabic → `'الأسئلة الشائعة'`, English → `'FAQ'`

### Test 4 — Unknown group `animations`

```php
FieldGroupRegistry::label('animations')  // → 'Animations'
FieldGroupRegistry::isKnown('animations') // → false
```
No exception. System works normally.

### Test 5 — Unknown group with underscores `pricing_table`

```php
FieldGroupRegistry::label('pricing_table') // → 'Pricing Table'
```

### Test 6 — Existing DB values still work

| Stored value | Before | After |
|--------------|--------|-------|
| `'content'` | `'Content'` | Arabic: `'المحتوى'` / English: `'Content'` |
| `'cta'` | `'Cta'` | Arabic: `'الدعوة للعمل'` / English: `'Call to Action'` |
| `'background'` | `'Background'` | Arabic: `'الخلفية'` / English: `'Background'` |
| `null` → `'general'` | `'General'` | Arabic: `'عام'` / English: `'General'` |

No data migration needed — all existing stored values are canonical slugs already in the Registry.

### Test 7 — New section from Features Grid template

After creating a section from the Features Grid template:
- Fields will have `group_name` values: `intro` (3 fields), `features` (1 repeater), `cta` (3 fields)
- Editor shows 3 group cards with Arabic labels: `المقدمة` · `المميزات` · `الدعوة للعمل`
- Instead of: one flat `عام` card with all fields

### Test 8 — Admin field form datalist

`$groupSuggestions` now includes all 14 Registry keys alphabetically first, then any custom keys used in the current definition. Developer sees canonical options immediately without having to remember the keys.

---

## What Did NOT Change

- DB schema — `group_name` column unchanged
- `SectionDefinitionField` model — no change
- `SectionFieldClassifier` — separate concern (Content vs Design tab routing), unaffected
- Import/Export services — read `group_name` raw, still works
- Frontend render path — `SectionRenderer`, `SectionFrontendViewDataFactory`, all Blade files — unaffected
- Any existing section with manually-set `group_name` values — labels now display correctly in Arabic

---

## Backward Compatibility

All existing data is compatible:
- `'content'` → was `'Content'`, now `'المحتوى'` (ar) / `'Content'` (en) — improvement
- `'cta'` → was `'Cta'`, now `'الدعوة للعمل'` (ar) — fix
- Custom values like `'background'` → now `'الخلفية'` (ar) — improvement
- Null values → `'general'` fallback → `'عام'` (ar) / `'General'` (en) — improvement
- Completely unknown values → `Str::headline()` — same as before

---

## Extensibility (v1)

Adding a new group required one PHP array entry with `ar` + `en` translations:
```php
'gallery' => ['ar' => 'المعرض', 'en' => 'Gallery'],
```
This was superseded by v2 below.

---

## Field Group Registry v2 — Full Multi-Language Support (2026-06-20)

### Problem with v1

The original `KNOWN_GROUPS` hardcoded translations for Arabic and English directly in PHP:

```php
'cta' => ['ar' => 'الدعوة للعمل', 'en' => 'Call to Action'],
```

This had two structural problems:
1. **Language-count-locked**: every new platform language (French, Turkish, etc.) required a PHP code change and redeploy.
2. **Bypass of the central translation system**: the `t()` helper and `translation_values` table already handle multi-language content — the Registry duplicated that responsibility.

### Solution

`KNOWN_GROUPS` now maps `group_key → English fallback string`. The translation key is derived automatically as `'section_groups.' . $group_key`. All locale lookups go through the existing `t()` helper:

```php
// v1 (removed)
'cta' => ['ar' => 'الدعوة للعمل', 'en' => 'Call to Action'],

// v2 (current)
'cta' => 'Call to Action',   // English is just the t() fallback
```

`label()` is now a single call:
```php
public static function label(string $groupKey): string
{
    if (isset(self::KNOWN_GROUPS[$groupKey])) {
        return t('section_groups.' . $groupKey, self::KNOWN_GROUPS[$groupKey]);
    }
    return self::humanize($groupKey);   // unknown groups → 'Animations', 'Pricing Table', etc.
}
```

### How it works at runtime

| Locale | Translation in DB? | Result |
|--------|--------------------|--------|
| `ar` | Yes (seeded) | `الدعوة للعمل` |
| `en` | No row in DB | `Call to Action` (English fallback from `KNOWN_GROUPS`) |
| `fr` | Yes (if added) | `Appel à l'action` |
| `fr` | No row in DB | `Call to Action` (fallback) |

### Adding a new language (e.g., French)

No PHP change required. Add rows to `translation_values`:

```sql
INSERT INTO translation_values (key, locale, value) VALUES
  ('section_groups.background',   'fr', 'Arrière-plan'),
  ('section_groups.cta',          'fr', 'Appel à l''action'),
  ('section_groups.design',       'fr', 'Design'),
  -- ... etc.
```

Or via a seeder for locale `'fr'` (same structure as the `'ar'` block in `DashboardTranslationsSeeder`). The `t()` helper picks them up automatically via `app()->getLocale()`.

### Adding a new group (v2 process)

1. Add one entry to `KNOWN_GROUPS` in `FieldGroupRegistry`:
   ```php
   'gallery' => 'Gallery',
   ```
2. Add Arabic translation to `DashboardTranslationsSeeder`:
   ```php
   'section_groups.gallery' => 'المعرض',
   ```
3. Run: `php artisan db:seed --class=DashboardTranslationsSeeder && php artisan cache:clear`

No other PHP file needs to change.

### Files changed in v2

| File | Change |
|------|--------|
| `app/Support/Sections/FieldGroupRegistry.php` | `KNOWN_GROUPS` from `['ar'=>..., 'en'=>...]` → `string` fallback; `label()` uses `t()`; added `translationKey()` helper |
| `database/seeders/DashboardTranslationsSeeder.php` | Added 14 `section_groups.*` Arabic translations |

### What did NOT change

- DB schema — `section_definition_fields.group_name` column unchanged
- `DynamicSectionEditorRenderer` — no change (still calls `FieldGroupRegistry::label()`)
- `SectionFieldClassifier` — unchanged
- `ComponentLibrary` — unchanged
- Any existing `group_name` values in the DB — all still resolve correctly
