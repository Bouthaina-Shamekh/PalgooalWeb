# Field Group Registry — Architecture Analysis

> Date: 2026-06-20
> Status: Analysis only — no code changes
> Purpose: Design a i18n-safe, extensible group registry before implementing it

---

## 1. Current Architecture

### 1.1 The Column

`group_name` — nullable string column in `section_definition_fields`:

```sql
-- migration: 2026_04_11_000005_add_builder_columns_to_section_definition_fields_table.php
$table->string('group_name')->nullable()->comment('Optional dashboard grouping label for related fields.');
$table->index(['section_definition_id', 'group_name', 'sort_order'], 'section_definition_fields_group_idx');
```

There is no type constraint, no enum, no FK. It accepts any string.

### 1.2 How Groups Are Built for the Editor (Critical Path)

`DynamicSectionEditorRenderer::buildGroupsForLocale()` — line 118:

```php
return $fields
    ->groupBy(fn($field) => $field->group_name ?: 'general')
    ->map(function (Collection $groupFields, string $groupKey) use (...) {
        return [
            'key'    => $groupKey,
            'label'  => Str::headline(str_replace(['_', '-'], ' ', $groupKey)),  // ← THE PROBLEM
            'fields' => [...],
        ];
    })
```

**The label IS a PHP transformation of the stored string.** There is zero translation layer. The stored value `'cta'` becomes label `'Cta'`. The stored value `'background'` becomes `'Background'`. Nothing else.

### 1.3 What Actually Gets Stored (Real-World Audit)

From `2026_04_18_000001_reset_hosting_hero_definition_to_dynamic.php`:

| `group_name` stored | Label produced by `Str::headline()` |
|---------------------|-------------------------------------|
| `'content'`         | `'Content'`                          |
| `'cta'`             | `'Cta'`  ← bad                      |
| `'background'`      | `'Background'`                       |
| `null` / `''`       | → fallback: key `'general'` → `'General'` |

From `ComponentLibrary.php`: **no `group_name` field at all** — every field created via the Component Library falls into `'general'` by default.

### 1.4 Where Groups Are Read or Displayed

| File | How `group_name` is used |
|------|--------------------------|
| `DynamicSectionEditorRenderer::buildGroupsForLocale()` | Groups fields by `group_name`, transforms to label via `Str::headline()` |
| `SectionDefinitionFieldController::index()` | `groupBy(fn($f) => $f->group_name ?: t('dashboard.General', 'عام'))` — for admin fields list |
| `SectionDefinitionFieldController::formViewData()` | Pulls unique `group_name` values as `$groupSuggestions` for `<datalist>` |
| `SectionDefinitionExportService` | Exports raw `group_name` value |
| `SectionDefinitionImportService` | Imports from `group_name` or `group` key |
| `renderer.blade.php` | Renders `$dynamicGroup['label']` — whatever the Renderer built |

### 1.5 Where Groups Are Set

| File | How `group_name` is written |
|------|-----------------------------|
| `SectionDefinitionFieldFormDataFactory` | `'group_name' => $validated['group'] ?? null` (from admin form free-text input) |
| `SectionDefinitionImportService` | From JSON import `group_name` or `group` key |
| `ComponentLibrary` | **NOT SET** — all component fields lack `group_name` |
| `SectionTemplateLibrary` | **NOT SET** — all template extra_fields lack `group_name` |
| Migration `reset_hosting_hero` | Set manually as raw strings: `'content'`, `'cta'`, `'background'` |

---

## 2. Problems

### P1 — No i18n: Labels Are English by Definition

`Str::headline('cta')` → `'Cta'` in all locales. The platform is primarily Arabic.
A developer who stores `'background'` will see `'Background'` on an Arabic UI — no localization.

### P2 — Free Text = No Consistency Guarantee

The field is a plain `string`. Examples of divergence that will happen:
- Developer A writes: `'design'`
- Developer B writes: `'Design'`
- Developer C writes: `'التصميم'`
- Developer D writes: `'Design Settings'`
- Developer E writes: `'background_design'`

All five produce different group cards in the editor. None are wrong by schema rules.

### P3 — `ComponentLibrary` Does Not Set `group_name`

Fields created from templates (`storeFromTemplate()`) inherit no group assignment.
All fields land in `'general'`, making the editor one flat list for every dynamic section.
This contradicts the semantic intent of Components (intro / cta / image / features...).

### P4 — `SectionTemplateLibrary` Extra Fields Also Lack Groups

Template-specific `extra_fields` (e.g. `background_image` in `cta-banner`) have no `group_name`, again collapsing everything to `'general'`.

### P5 — `Str::headline()` Garbles Abbreviations

- `'cta'` → `'Cta'` (not `'Call To Action'`)
- `'seo'` → `'Seo'` (not `'SEO'`)
- `'faq'` → `'Faq'` (not `'FAQ'`)

The transformation is lossy and looks amateurish in the UI.

### P6 — Export/Import Round-Trip Breaks on Renamed Groups

If today's value is `'background'` and someone decides to standardize on `'design'`, all exported JSON files carry the old value. No mapping exists.

---

## 3. Proposed Registry Architecture

### 3.1 Core Concept

The developer stores a **semantic key** (lowercase, slug-like):

```
group_name = 'intro'
group_name = 'cta'
group_name = 'image'
group_name = 'seo'
group_name = 'animations'    ← custom, not in registry → graceful fallback
```

A **static Registry class** maps known keys to localized labels per app locale. Unknown keys get a safe automatic humanization.

### 3.2 Registry Class (`app/Support/Sections/FieldGroupRegistry.php`)

```php
<?php

namespace App\Support\Sections;

use Illuminate\Support\Str;

/**
 * Central registry that translates group_key → human-readable label.
 *
 * Developers store lowercase slug keys in section_definition_fields.group_name.
 * The registry provides localized labels for the editor UI.
 *
 * Unknown keys never crash — they fall back to Str::headline() in the current locale.
 * Third-party templates and future components can freely add new groups without
 * touching this registry; they will simply render with auto-humanized labels.
 */
class FieldGroupRegistry
{
    /**
     * Known groups with translations.
     *
     * Keys  = values that should be stored in group_name column
     * Values = locale-keyed label arrays.
     *         If a locale is missing, falls back to 'en', then to Str::headline().
     *
     * Keep sorted alphabetically by key for easy maintenance.
     */
    private const KNOWN_GROUPS = [
        'background'   => ['ar' => 'الخلفية',           'en' => 'Background'],
        'content'      => ['ar' => 'المحتوى',           'en' => 'Content'],
        'cta'          => ['ar' => 'الدعوة للعمل',      'en' => 'Call to Action'],
        'description'  => ['ar' => 'الوصف',             'en' => 'Description'],
        'design'       => ['ar' => 'التنسيق',           'en' => 'Design'],
        'faq'          => ['ar' => 'الأسئلة الشائعة',  'en' => 'FAQ'],
        'features'     => ['ar' => 'المميزات',          'en' => 'Features'],
        'general'      => ['ar' => 'عام',               'en' => 'General'],
        'highlight'    => ['ar' => 'النص المميَّز',     'en' => 'Highlight'],
        'image'        => ['ar' => 'الصورة',            'en' => 'Image'],
        'intro'        => ['ar' => 'المقدمة',           'en' => 'Introduction'],
        'media'        => ['ar' => 'الوسائط',           'en' => 'Media'],
        'seo'          => ['ar' => 'تحسين البحث',       'en' => 'SEO'],
        'testimonials' => ['ar' => 'التقييمات',         'en' => 'Testimonials'],
    ];

    /**
     * Resolve a human-readable label for the current app locale.
     *
     * Falls back gracefully:
     *   1. Known group → current locale label
     *   2. Known group → 'en' label
     *   3. Unknown group → Str::headline(str_replace(['_', '-'], ' ', $key))
     */
    public static function label(string $groupKey): string
    {
        $locale = app()->getLocale();

        if (isset(self::KNOWN_GROUPS[$groupKey])) {
            return self::KNOWN_GROUPS[$groupKey][$locale]
                ?? self::KNOWN_GROUPS[$groupKey]['en']
                ?? self::humanize($groupKey);
        }

        return self::humanize($groupKey);
    }

    /**
     * Whether this group key is registered.
     * Useful for validation hints or "new group" warnings in the admin UI.
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
     * All registered groups as [key => label] for current locale.
     * Useful for building admin UI suggestion dropdowns.
     *
     * @return array<string, string>
     */
    public static function allLabeled(): array
    {
        $locale = app()->getLocale();
        $result = [];

        foreach (self::KNOWN_GROUPS as $key => $translations) {
            $result[$key] = $translations[$locale] ?? $translations['en'] ?? self::humanize($key);
        }

        return $result;
    }

    private static function humanize(string $groupKey): string
    {
        return Str::headline(str_replace(['_', '-'], ' ', $groupKey));
    }
}
```

### 3.3 The Single Change in DynamicSectionEditorRenderer

```php
// BEFORE:
'label' => Str::headline(str_replace(['_', '-'], ' ', $groupKey)),

// AFTER:
'label' => FieldGroupRegistry::label($groupKey),
```

One line. Zero other changes in the rendering pipeline.

---

## 4. Storage Strategy

### Decision: Keep `group_name` Column As-Is (No DB Migration)

**Rationale:**

The column name `group_name` is slightly misleading (it stores a key, not a displayable name), but renaming it to `group_key` would require:
- A new migration
- Updates to 8+ files that reference `$field->group_name`
- Import/Export format version bump

**The semantic problem is solved entirely in code**, not in the schema. The column stores `'cta'` just as well whether it's called `group_name` or `group_key`. Since the Registry is the single translation authority, what the column is *called* doesn't matter to users.

**What we DO standardize (no migration needed):**

- Document clearly: **`group_name` stores a slug key, not a displayable label**
- Update the migration comment from "Optional dashboard grouping label" → "Optional group key (slug) resolved to a localized label via FieldGroupRegistry."
- The admin form already uses a free-text `<datalist>` — the suggestions simply become Registry-aware

**Future:** If/when a proper schema migration is desired (v2), rename column to `group_key` at that point.

---

## 5. Translation Strategy

### Two-tier approach:

**Tier 1 — Registry** (covers 95% of cases):
- PHP static class, no DB queries
- Keys hardcoded, labels auto-selected by `app()->getLocale()`
- Works at zero-cost in any Blade render path

**Tier 2 — Auto-humanize** (covers 5% of cases — custom/third-party groups):
- `Str::headline(str_replace(['_', '-'], ' ', $key))`
- Produces English-readable result for unknown keys
- Never crashes. Never hides a group.

### What We Do NOT Do:
- No `dashboard.*` translation key per group — that would require a seeder run on every group addition, creating tight coupling between DB seeder and template authors
- No DB lookup for group labels — no N+1 risk
- No YAML/JSON config file — PHP constants are simpler, typed, and IDE-navigable

---

## 6. Unknown Groups Strategy

The defining principle: **unknown groups must work, not fail.**

```
Developer ships a custom template with:
    group_name = 'animations'
    group_name = 'pricing'
    group_name = 'statistics'
```

These are not in the Registry. The behavior:

```
FieldGroupRegistry::label('animations')  → Str::headline('animations') → 'Animations'
FieldGroupRegistry::label('pricing')     → 'Pricing'
FieldGroupRegistry::label('statistics')  → 'Statistics'
```

The groups render correctly with English labels. The admin can still see and edit all fields. Nothing breaks. No exception thrown.

**Optional enhancement** (not in Phase 1): In the admin field form, when a developer enters a group key not in the Registry, show a subtle hint: "هذه المجموعة غير مسجّلة في الـ Registry — ستظهر باللغة الإنجليزية فقط."

This requires `FieldGroupRegistry::isKnown($key)` — already designed into the class.

---

## 7. Migration Strategy (Data)

### Current DB values audit:

Known values currently in production (from migrations):
- `'content'` ✅ already in Registry
- `'cta'` ✅ already in Registry
- `'background'` ✅ already in Registry
- `null` / `''` → `'general'` fallback ✅ already in Registry

**No data migration needed.** All existing stored values are either already canonical slugs, or `null` (which falls to `'general'`).

### What the developer workflow becomes after the change:

1. Developer opens Section Definition → Fields → Add Field
2. Types in the "المجموعة" field: `intro`
3. The `<datalist>` shows suggestions from the Registry + existing sibling groups
4. Value stored: `'intro'`
5. Editor displays: `'المقدمة'` (Arabic) or `'Introduction'` (English)

No change to the DB schema. No seeder run. No migration.

---

## 8. ComponentLibrary Integration

**The biggest practical win:** updating `ComponentLibrary` to set `group_name` on every field it defines.

Currently: all ComponentLibrary fields → `'general'` group (because `group_name` is null).

After: each component declares its semantic group, matching its component key where appropriate.

**Mapping:**

| Component Key | `group_name` value to add |
|---------------|---------------------------|
| `intro`       | `'intro'`                 |
| `description` | `'description'`           |
| `cta`         | `'cta'`                   |
| `image`       | `'image'`                 |
| `features`    | `'features'`              |
| `highlight`   | `'highlight'`             |
| `faq`         | `'faq'`                   |
| `testimonials`| `'testimonials'`          |
| `seo`         | `'seo'`                   |

Each field definition in `ALL_COMPONENTS` gets one extra key:

```php
// BEFORE:
['field_key' => 'eyebrow', 'label' => 'Eyebrow', 'field_type' => self::TEXT, 'field_scope' => self::T, 'is_required' => false],

// AFTER:
['field_key' => 'eyebrow', 'label' => 'Eyebrow', 'field_type' => self::TEXT, 'field_scope' => self::T, 'is_required' => false, 'group_name' => 'intro'],
```

This propagates automatically to `storeFromTemplate()` and `SectionPackageGenerator` because both call `ComponentLibrary::resolveFields()` and pass the result to `SectionDefinitionField::create()`.

**Existing fields without `group_name`** (already in DB from migrations/manual creation) are unaffected. Their `group_name` remains as-is.

---

## 9. Form UI — Registry-Aware Suggestions

The admin field form currently builds `$groupSuggestions` from existing sibling group names (DB lookup). After the Registry exists:

```php
// SectionDefinitionFieldController::formViewData()
// BEFORE:
$groupSuggestions = $sectionDefinition->fields()
    ->whereNotNull('group_name')
    ->pluck('group_name')
    ->unique()->values()->all();

// AFTER: merge Registry keys with existing sibling keys
$existingSuggestions = $sectionDefinition->fields()
    ->whereNotNull('group_name')
    ->pluck('group_name')
    ->unique()->values()->toArray();

$registrySuggestions = FieldGroupRegistry::keys();  // canonical keys

$groupSuggestions = array_unique(array_merge($registrySuggestions, $existingSuggestions));
sort($groupSuggestions);
```

The `<datalist>` now shows all canonical group keys first, then any custom ones in use.

---

## 10. Export/Import Compatibility

**Export** — no change. `group_name` already exported as-is.

**Import** — The Registry is read-only and doesn't validate imports. Any value imported as `group_name` works as before. Unknown values get auto-humanized labels.

**Optional future enhancement**: An import warning when `group_name` doesn't match the Registry. Not required for Phase 1.

---

## 11. Extensibility to `label_key`, `component_key`

The Registry pattern established here is naturally extensible:

```
SectionDefinitionField
  ├── field_key      → FieldGroupRegistry::label($group_name)    ← THIS DOC
  ├── group_name     → (stores slug key)
  ├── label          → label_key in future: FieldLabelRegistry
  └── component_key  → ComponentLibrary::get($component_key)     ← already exists
```

All three follow the same pattern: **store a slug key, resolve to localized string at render time via a static registry**. No DB joins, no N+1, no seeder requirement for every new entry.

---

## 12. Recommended Implementation Order

### Phase 1 — Core Registry (minimal risk, maximum impact)

**Files to create:**
- `app/Support/Sections/FieldGroupRegistry.php`

**Files to edit (single-line changes):**
- `app/Support/Sections/DynamicSectionEditorRenderer.php` — replace `Str::headline()` with `FieldGroupRegistry::label()`
- `app/Http/Controllers/Admin/SectionDefinitionFieldController.php` — merge Registry keys into `$groupSuggestions`

**Zero migration, zero seeder, zero data change.**

---

### Phase 2 — ComponentLibrary Groups

**Files to edit:**
- `app/Support/Sections/ComponentLibrary.php` — add `'group_name' => '<component_key>'` to every field in `ALL_COMPONENTS`

**Benefit:** Every new section created from a template gets semantically grouped fields automatically.

**Risk:** Existing fields in DB are unchanged — only new fields created after this change will have correct groups. Existing definitions need manual group assignment via the admin UI, or a targeted data backfill.

---

### Phase 3 — Admin UI Hint for Unknown Groups (Optional)

**Files to edit:**
- `resources/views/dashboard/section_definitions/fields/form.blade.php` — add inline hint when entered group is not in Registry

**Benefit:** Guides developers toward canonical keys.
**Risk:** None — purely additive UI.

---

### Phase 4 — SectionTemplateLibrary Extra Fields (Optional)

Set `group_name` on the `extra_fields` in `SectionTemplateLibrary` (e.g. `background_image → 'background'`).

---

## 13. Summary

| Question | Answer |
|----------|--------|
| أين تُخزَّن المجموعات؟ | `section_definition_fields.group_name` — nullable string |
| ما هو المشكلة الجوهرية؟ | `Str::headline()` هي الترجمة الوحيدة — لا i18n |
| هل يحتاج الحل إلى migration؟ | **لا** — الحل في PHP فقط |
| هل يحتاج إلى seeder؟ | **لا** — Registry هي PHP constants |
| ما الكلاس المقترح؟ | `FieldGroupRegistry` في `app/Support/Sections/` |
| كيف تُعالَج المجموعات غير المعروفة؟ | `Str::headline()` auto-fallback — لا crash |
| أول تغيير فعلي مطلوب؟ | سطر واحد في `DynamicSectionEditorRenderer::buildGroupsForLocale()` |
| الكسب الأكبر؟ | إضافة `group_name` لـ `ComponentLibrary` → كل section جديد تلقائياً مُجمَّع |
| هل يكسر البيانات الحالية؟ | **لا** — القيم الموجودة (`content`, `cta`, `background`) مُسجَّلة في Registry |

---

## 14. Files Summary

| الملف | نوع التغيير |
|-------|------------|
| `app/Support/Sections/FieldGroupRegistry.php` | **إنشاء جديد** |
| `app/Support/Sections/DynamicSectionEditorRenderer.php` | تعديل سطر واحد |
| `app/Http/Controllers/Admin/SectionDefinitionFieldController.php` | تعديل `formViewData()` |
| `app/Support/Sections/ComponentLibrary.php` | إضافة `group_name` لكل حقل |
| `app/Support/Sections/SectionTemplateLibrary.php` | إضافة `group_name` لـ `extra_fields` (Phase 4) |
| DB / migrations | **لا تغيير** |
| DashboardTranslationsSeeder | **لا تغيير** — الترجمات في PHP constants |
