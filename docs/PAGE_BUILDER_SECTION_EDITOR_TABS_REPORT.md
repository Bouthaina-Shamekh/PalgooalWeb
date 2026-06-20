# Page Builder Section Editor — Content / Design Tabs

> Phase: Page Builder UX — UI Grouping + UX Enhancements  
> Date: 2026-06-19  
> Updated: 2026-06-19 (Phase A: Remember Active Tab · Phase B: Badge Counts)

---

## Summary

Split the dynamic-field area in the Page Builder sidebar editor into two tabs:

| Tab | Arabic | Purpose |
|-----|--------|---------|
| **Content** | المحتوى | Text, media, CTAs, repeaters — "What does the user see?" |
| **Design** | التنسيق | Layout, spacing, colours, animations — "How does it look?" |

The save pipeline, form payload, and all Blade/PHP infrastructure are **unchanged**.

---

## Files Created / Modified

### New Files

| File | Purpose |
|------|---------|
| `app/Support/Sections/SectionFieldClassifier.php` | PHP classifier — maps `field_key` → `'content'` or `'design'` |
| `docs/PAGE_BUILDER_SECTION_EDITOR_TABS_REPORT.md` | This report |

### Modified Files

| File | Change |
|------|--------|
| `resources/views/dashboard/pages/sections/partials/dynamic-editor/renderer.blade.php` | Added Content/Design tab switcher + split group rendering |
| `database/seeders/DashboardTranslationsSeeder.php` | Added 7 translation keys |

---

## Architecture

### SectionFieldClassifier

`app/Support/Sections/SectionFieldClassifier.php`

A **presentation-layer only** static utility. Contains no database queries or model coupling.

```php
// Classify a single field key
SectionFieldClassifier::classify('title');          // → 'content'
SectionFieldClassifier::classify('image_position'); // → 'design'
SectionFieldClassifier::classify('my_custom_key');  // → 'content'  ← fallback

// Split a flat array of field payloads
['content' => $cf, 'design' => $df] = SectionFieldClassifier::splitFields($fields);

// Split groups (as produced by DynamicSectionEditorRenderer)
['content' => $cg, 'design' => $dg] = SectionFieldClassifier::splitGroups($groups);
```

### Design Field Keys (full list)

```
align               animation           background_color
background_image    button_size         button_style
columns             custom_classes      grid_columns
image_position      layout_style        padding_bottom
padding_top         spacing_bottom      spacing_top
subtitle_size       text_align          theme_variant
title_size
```

Everything else → **Content** (safe fallback — custom fields are never hidden).

### renderer.blade.php changes

The renderer is `@include`d once per active language in `dynamic-editor-form.blade.php`.

**Before**: single `@forelse` loop over `$dynamicGroups`.

**After**:

```
@php — resolve + split groups via SectionFieldClassifier::splitGroups()
<div class="{{ $contentGridClass }}">
    <input type="hidden" ...>   ← always in DOM

    @if empty groups
        empty state
    @else
        [Tab Switcher] — two <button role="tab"> elements
        [Content Panel] — data-field-tab="content" — default visible
        [Design Panel]  — data-field-tab="design"  — hidden initially
    @endif
</div>
<script> — inline IIFE scoped to $fieldTabId = "field-tab-{$code}" </script>
```

Both panels are **always in the DOM**. Switching tabs only toggles `.hidden`. This is critical: `display:none` inputs are still submitted by browsers, so the save payload is 100% unchanged.

---

## How Field Classification Works

### Classification decision

```
"ماذا يظهر للمستخدم؟"  →  Content
"كيف يظهر للمستخدم؟"   →  Design
```

### Group splitting

Groups from `DynamicSectionEditorRenderer::buildGroupsForLocale()` may contain a mix of content and design fields. The classifier splits each group:

```
group "general" [eyebrow, title, image_position, layout_style]
  → Content group "general" [eyebrow, title]
  → Design  group "general" [image_position, layout_style]
```

Groups with no fields in a given bucket are omitted from that bucket (no empty group cards rendered).

### Fallback rule

Any `field_key` **not listed** in `DESIGN_FIELD_KEYS` → `'content'`. This means:

- Custom fields defined by developers for specific templates appear in Content by default.
- New standard keys added to Presets/Components that haven't been classified yet are visible (not silently dropped into a hidden Design tab).

To reclassify a future key, add it to `SectionFieldClassifier::DESIGN_FIELD_KEYS`.

---

## Tab Switching — UX Details

- **No page refresh** — pure JS `classList.toggle('hidden', ...)`
- **No value loss** — both panels stay in the DOM; inputs retain their values
- **Active language preserved** — language tabs are a separate switcher at the parent level (`dynamic-editor-form.blade.php`); the field tab switcher is nested inside each locale panel and does not interact with the language switcher
- **Save button unchanged** — the Save button is outside the renderer, in `sidebar-editor.blade.php`, and submits the single `data-section-editor-form` form which covers both panels

### Tab IDs

Each locale panel gets a unique `$fieldTabId` (e.g. `field-tab-ar`, `field-tab-en`). This allows a page with multiple locale panels to have independent Content/Design states without JS interference.

---

## Translation Keys Added

| Key | Arabic Value |
|-----|-------------|
| `dashboard.Content_Tab` | المحتوى |
| `dashboard.Design_Tab` | التنسيق |
| `dashboard.No_Design_Fields` | لا توجد إعدادات تنسيق لهذا السكشن حالياً. |
| `dashboard.No_Design_Fields_Hint` | أضف حقول تنسيق (layout_style، image_position…) لتظهر هنا. |
| `dashboard.No_Content_Fields` | لا توجد حقول محتوى لهذا السكشن. |
| `dashboard.No_Dynamic_Fields` | لا توجد حقول ديناميكية مسجلة لهذه اللغة بعد. |
| `dashboard.Section_Editor_Fields_Desc` | حقول مدفوعة من تعريف القسم. |

Also replaces the old `__()` calls in `renderer.blade.php` with `t()`.

---

## Validation Examples

### Content Showcase (`content_showcase`)

Fields: `eyebrow`, `title`, `subtitle`, `features`, `highlight_text`, `button_label`, `button_url`, `image`, `image_alt`, `image_position`

| Tab | Fields |
|-----|--------|
| Content | eyebrow, title, subtitle, features, highlight_text, button_label, button_url, image, image_alt |
| Design | image_position |

### Features Grid (`features_grid`)

Fields: `eyebrow`, `title`, `subtitle`, `features`

| Tab | Fields |
|-----|--------|
| Content | eyebrow, title, subtitle, features |
| Design | *(empty state shown)* |

### Hero Main (`hero_main`)

Fields: `eyebrow`, `title`, `subtitle`, `description`, `button_label`, `button_url`, `image`, `image_alt`, `layout_style`, `background_color`, `text_align`

| Tab | Fields |
|-----|--------|
| Content | eyebrow, title, subtitle, description, button_label, button_url, image, image_alt |
| Design | layout_style, background_color, text_align |

---

## What Did NOT Change

| Concern | Status |
|---------|--------|
| DB schema | Unchanged |
| `SectionDefinitionField` model | Unchanged |
| `DynamicSectionEditorRenderer` | Unchanged |
| Save payload (`translatable` + `shared` fields) | Unchanged |
| Frontend Blade renderer (`SectionRenderer`) | Unchanged |
| Frontend section views | Unchanged |
| Language tab switching | Unchanged |
| `sidebar-editor.blade.php` | Unchanged |
| `dynamic-editor-form.blade.php` | Unchanged |

---

---

## Phase A — Remember Active Tab

### Problem

Every time a section was opened or the page was refreshed, the tab always reset to "Content" — even if the user had been working on "Design" fields. This was especially disruptive when adjusting `layout_style`, `background_color`, etc. across multiple saves.

### Solution

`localStorage` keyed per section:

```
section-editor-tab-{sectionId}
```

Values stored: `'content'` or `'design'`.

### Behaviour

| Moment | What happens |
|--------|-------------|
| Page load | Read `localStorage.getItem('section-editor-tab-83')`; if `'design'` → activate Design tab immediately (before user interaction) |
| Tab click | `localStorage.setItem('section-editor-tab-83', 'design')` |
| No saved value | Default to Content (original behaviour) |
| localStorage unavailable (e.g. private browsing) | `try/catch` absorbs the error silently; falls back to Content |

### Cross-locale behaviour

The storage key is per-section, **not** per-locale (e.g. `section-editor-tab-83`, not `section-editor-tab-83-ar`). All locale panels for the same section restore to the same tab. Each locale panel's inline IIFE runs independently at page load and reads the same key — so all language panels are initialised to the correct tab state before the user sees anything.

### Code location

Inline `<script>` at the bottom of `renderer.blade.php`, inside the locale panel:

```javascript
var STORAGE_KEY = '{{ $storageKey }}'; // e.g. 'section-editor-tab-83'

// On init:
var saved = null;
try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
if (saved === 'design' || saved === 'content') { activateTab(saved); }

// On click:
try { localStorage.setItem(STORAGE_KEY, target); } catch (e) {}
```

`$storageKey` is computed in PHP: `'section-editor-tab-' . ($section->id ?? '0')`.  
`$section` is available in the renderer via Blade's shared `@include` scope.

---

## Phase B — Tab Badge Counts

### Problem

After the tab split, the user could not tell at a glance how many fields existed in each tab. With large sections (10+ fields), this made it unclear whether "Design" had anything before clicking.

### Solution

Each tab button shows a badge with the field count:

```
المحتوى (9)    ←  9 content fields
التنسيق (2)    ←  2 design fields
```

Or with empty Design:

```
المحتوى (4)    ←  4 content fields
التنسيق (0)    ←  0 design fields (empty state shown in panel)
```

### Implementation

Counts are computed **in PHP** from the already-split groups — zero additional JS:

```php
// In renderer.blade.php @php block:
$contentFieldCount = array_sum(array_map(fn ($g) => count($g['fields']), $contentGroups));
$designFieldCount  = array_sum(array_map(fn ($g) => count($g['fields']), $designGroups));
```

Rendered inline into the button HTML via `{{ $contentFieldCount }}`.

### Badge colours

Badges update colour dynamically when switching tabs (via `activateTab()` in the inline script):

| Tab state | Badge classes |
|-----------|--------------|
| Active | `bg-indigo-100 text-indigo-700` |
| Inactive | `bg-slate-200 text-slate-500` |

### Validation examples (updated)

#### Content Showcase (`content_showcase`)

Fields: `eyebrow`, `title`, `subtitle`, `features` (repeater × 3 items = 1 field), `highlight_text`, `button_label`, `button_url`, `image`, `image_alt`, `image_position`

| Tab | Count | Fields |
|-----|-------|--------|
| Content | 9 | eyebrow, title, subtitle, features, highlight_text, button_label, button_url, image, image_alt |
| Design | 1 | image_position |

Displayed as: **المحتوى (9)** · **التنسيق (1)**

#### Features Grid (`features_grid`)

Fields: `eyebrow`, `title`, `subtitle`, `features`

| Tab | Count | Fields |
|-----|-------|--------|
| Content | 4 | eyebrow, title, subtitle, features |
| Design | 0 | *(empty state)* |

Displayed as: **المحتوى (4)** · **التنسيق (0)**

---

## Artisan Command (after deployment)

```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

---

## Future Enhancements

- **Remember active tab per section**: persist selected tab to `localStorage` keyed by section ID so Design stays selected after page refresh.
- **Tab badge**: show a count badge on Design tab when design fields exist (e.g. "التنسيق (3)") to hint there are settings available.
- **Developer-defined tab**: allow `SectionDefinitionField::$group_tab` column (future DB migration) to explicitly assign a field to a tab, overriding the classifier.
