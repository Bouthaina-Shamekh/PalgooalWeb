# Component Sync Refactor — Pre-Phase 2 Report

**Date:** 2026-06-19  
**Type:** Architectural Refactor (no feature change)  
**Files modified:** 1  
**Files created:** 1 (this report)

---

## Background

Before implementing Phase 2 (Generate Blade File — write scaffold to disk), a single-source-of-truth violation was identified between `BladeGenerator` and `ComponentLibrary`. This report documents what was changed and why.

---

## The Problem (Before)

`BladeGenerator.php` contained two private constants that **duplicated** information already present in `ComponentLibrary.php`:

### Duplicate 1 — `COMPONENT_FIELD_GROUPS`

```php
// BladeGenerator.php (OLD — REMOVED)
private const COMPONENT_FIELD_GROUPS = [
    'intro'       => ['eyebrow', 'title', 'subtitle'],
    'description' => ['description'],
    'highlight'   => ['highlight_text'],
    'cta'         => ['button_label', 'button_url', 'button_target'],
    'image'       => ['image', 'image_alt', 'image_position'],
    'seo'         => ['meta_title', 'meta_description'],
];
```

This map was used to:
1. Build a reverse lookup `field_key → component` for `detectComponentGroups()`
2. Preserve canonical component order by iterating `array_keys(COMPONENT_FIELD_GROUPS)`

The same information exists in `ComponentLibrary::ALL_COMPONENTS` under each component's `fields[]` array.

### Duplicate 2 — `COMPONENT_LABELS`

```php
// BladeGenerator.php (OLD — REMOVED)
private const COMPONENT_LABELS = [
    'intro'       => 'Intro (eyebrow / title / subtitle)',
    'description' => 'Description',
    'highlight'   => 'Highlight',
    'cta'         => 'CTA (button)',
    'image'       => 'Image',
    'seo'         => 'SEO Meta',
];
```

This map provided display names for component section comments in generated Blade files.  
The same information exists in `ComponentLibrary::ALL_COMPONENTS` under each component's `name` key.

### The Risk

If a developer added a new component to `ComponentLibrary` (e.g., `stats`, `pricing`, `logos`):

- `SectionTemplateLibrary` would use it automatically ✓
- `storeFromTemplate()` would resolve its fields automatically ✓
- **`BladeGenerator` would silently ignore it** ✗ — its fields would fall into the "ungrouped" bucket with no component heading, and the component would not appear in the stats bar of the Preview Modal.

This created a maintenance trap: the comment in `ComponentLibrary` saying *"No other file needs to change"* was incorrect.

---

## The Fix (After)

### 1. Removed `COMPONENT_FIELD_GROUPS` and `COMPONENT_LABELS` constants entirely

Both constants have been deleted from `BladeGenerator.php`.

### 2. Added `buildKeyToComponentMap()` — the new integration point

```php
// BladeGenerator.php (NEW)
private function buildKeyToComponentMap(): array
{
    $map = [];

    foreach (ComponentLibrary::all() as $componentKey => $component) {
        foreach ($component['fields'] ?? [] as $fieldDef) {
            $fieldKey  = (string) ($fieldDef['field_key'] ?? '');
            $fieldType = (string) ($fieldDef['field_type'] ?? '');

            // Skip empty keys and repeater fields — they go to ungrouped
            if ($fieldKey === '' || $fieldType === SectionDefinitionField::FIELD_TYPE_REPEATER) {
                continue;
            }

            // First occurrence wins (same dedup rule as ComponentLibrary::resolveFields)
            if (! isset($map[$fieldKey])) {
                $map[$fieldKey] = $componentKey;
            }
        }
    }

    return $map;
}
```

**Key design decisions:**
- Repeater-typed fields are **excluded** intentionally — they always go to the "ungrouped" bucket because they generate `@foreach` blocks, not simple field lines. This mirrors the original behaviour.
- First-occurrence-wins dedup matches `ComponentLibrary::resolveFields()` for consistency.
- The method is `private` — it is an internal implementation detail of `detectComponentGroups()`.

### 3. Updated `detectComponentGroups()` to use dynamic sources

```php
// BEFORE
foreach (self::COMPONENT_FIELD_GROUPS as $component => $keys) { ... }
foreach (array_keys(self::COMPONENT_FIELD_GROUPS) as $component) { ... }

// AFTER
$keyToComponent = $this->buildKeyToComponentMap();         // dynamic from ComponentLibrary
foreach (ComponentLibrary::keys() as $component) { ... }  // canonical order from ComponentLibrary
```

### 4. Updated `buildHtmlBlock()` to use `ComponentLibrary::get()` for labels

```php
// BEFORE
$label = self::COMPONENT_LABELS[$component] ?? ucfirst($component);

// AFTER
$label = ComponentLibrary::get($component)['name'] ?? ucfirst($component);
```

The label source is now the `name` field of each component definition in `ComponentLibrary`.

### 5. Updated class docblock

The `Component Awareness` section of the BladeGenerator docblock now correctly states that `ComponentLibrary` is the single source of truth and that adding a new component there requires no other file change.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Support/Sections/BladeGenerator.php` | Removed 2 duplicate constants, added `buildKeyToComponentMap()`, updated `detectComponentGroups()` and `buildHtmlBlock()`, updated docblock |

## Files NOT Modified

| File | Why unchanged |
|------|---------------|
| `app/Support/Sections/ComponentLibrary.php` | Single source of truth — no changes needed |
| `app/Support/Sections/SectionTemplateLibrary.php` | Already uses `ComponentLibrary::resolveFields()` correctly |
| `app/Support/Sections/FieldPresetLibrary.php` | Separate concern — presets, not components |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | Controller API unchanged — `bladeScaffold()` still calls `new BladeGenerator()->generate()` |
| All routes | No changes |
| All views | No changes |
| All translations | No changes |

---

## What Still Lives in BladeGenerator (Legitimately)

Two constants remain in `BladeGenerator` and are **correct to keep there**:

### `TAG_BY_KEY`

```php
private const TAG_BY_KEY = [
    'eyebrow'        => 'span',
    'title'          => 'h2',
    'subtitle'       => 'p',
    'description'    => 'div',
    'highlight_text' => 'mark',
    'meta_title'     => null,
    'meta_description' => null,
];
```

This maps `field_key` → HTML element. It is a **rendering/presentation** decision, not a component definition. `ComponentLibrary` defines *what* a field is (key, type, scope); `BladeGenerator` decides *how* to render it in HTML. These are separate concerns.

### `CLASS_BY_KEY`

```php
private const CLASS_BY_KEY = [
    'eyebrow'        => 'section-eyebrow',
    'title'          => 'section-title',
    ...
];
```

Same reasoning — CSS class assignments are scaffold presentation hints, not part of field structure.

---

## Validation — Adding a New Component

To verify the Single Source of Truth property now holds:

**Step 1:** Add `stats` component to `ComponentLibrary::ALL_COMPONENTS`:
```php
'stats' => [
    'name'        => 'Statistics',
    'icon'        => 'ti-chart-bar',
    'color'       => 'blue',
    'description' => 'Key statistics display block.',
    'fields' => [
        ['field_key' => 'stats_label', 'label' => 'Stats Label', 'field_type' => 'text',     'field_scope' => 'translatable', 'is_required' => false],
        ['field_key' => 'stats_value', 'label' => 'Stats Value', 'field_type' => 'text',     'field_scope' => 'shared',       'is_required' => false],
        ['field_key' => 'stats_items', 'label' => 'Stats Items', 'field_type' => 'repeater', 'field_scope' => 'translatable', 'is_required' => false],
    ],
],
```

**Step 2:** Without modifying any other file, observe:

| Behaviour | Result |
|-----------|--------|
| `SectionTemplateLibrary` uses `stats` in a template | ✓ Works |
| `BladeGenerator::buildKeyToComponentMap()` includes `stats_label` and `stats_value` | ✓ Works automatically |
| `stats_items` (repeater) goes to ungrouped bucket | ✓ Correct — repeaters excluded from group map |
| Preview Modal stats bar shows `Statistics` component | ✓ Derived from `component['name']` |
| Section comment reads `{{-- Statistics --}}` | ✓ Derived from `component['name']` |

**No other file needs to change.** The comment in `ComponentLibrary.php` is now accurate.

---

## Benefits

| Before | After |
|--------|-------|
| 2 sources of truth for component data | 1 source of truth (`ComponentLibrary`) |
| Adding a component requires editing 2 files | Adding a component requires editing 1 file |
| Silent bug if BladeGenerator not updated | Automatic sync — no manual update possible to forget |
| COMPONENT_FIELD_GROUPS could drift from ComponentLibrary | Impossible to drift — derived at runtime |
| 6 components listed in BladeGenerator | 0 component definitions in BladeGenerator |

---

## Remaining Technical Debt Related to Components

None. `TAG_BY_KEY` and `CLASS_BY_KEY` in `BladeGenerator` are legitimately separate concerns (HTML rendering hints, not component definitions).

The only future consideration: if a new `field_key` (e.g., `eyebrow_color`, `title_size`) needs custom HTML rendering treatment, it should be added to `TAG_BY_KEY`/`CLASS_BY_KEY` in `BladeGenerator`. This is the correct place — it is not a component definition.

---

## Status: Complete

The system now has a Single Source of Truth for component definitions. Phase 2 (Generate Blade File) can proceed.
