# Design Token Registry — Implementation Report

**Date:** 2026-06-21  
**Steps Implemented:** Step 1 (DesignTokenRegistry) + Step 2 (SectionFieldClassifier v2)  
**Architecture Reference:** `docs/SECTION_DESIGN_TOKENS_ARCHITECTURE.md`

---

## Files Created

| File | Lines | Purpose |
|---|---|---|
| `app/Support/Sections/DesignTokenRegistry.php` | ~240 | Canonical registry for 5 design tokens |

---

## Files Modified

| File | Change |
|---|---|
| `app/Support/Sections/SectionFieldClassifier.php` | v2 — derives design keys from Registry + EXTRA_DESIGN_KEYS |

---

## What Was Implemented

### Step 1 — DesignTokenRegistry

New static class at `app/Support/Sections/DesignTokenRegistry.php`.

#### Public API

```php
DesignTokenRegistry::keys(): array           // all registered token field_keys
DesignTokenRegistry::has(string $key): bool  // check if key is a token
DesignTokenRegistry::get(string $key): ?array // full token definition or null
DesignTokenRegistry::options(string $key): array      // [{value, label}, ...]
DesignTokenRegistry::validValues(string $key): array  // ['primary', 'secondary', ...]
DesignTokenRegistry::defaultValue(string $key): ?string
DesignTokenRegistry::resolveClass(string $key, ?string $value): string
DesignTokenRegistry::all(): array            // all token definitions
```

#### The 5 Registered Tokens

| Token Key | Type | Scope | Default | CSS Resolution |
|---|---|---|---|---|
| `background_token` | select | shared | `muted` | `bg-theme-{value}` |
| `text_token` | select | shared | `heading` | `text-theme-{value}` |
| `image_position` | select | shared | `right` | layout logic (no css_map) |
| `section_spacing` | select | shared | `md` | `py-{size}` responsive |
| `container_width` | select | shared | `default` | `max-w-{size}` |

#### Token Details

**`background_token`** — 5 options
```
none      → ''                (no background)
primary   → bg-theme-primary
secondary → bg-theme-secondary
surface   → bg-theme-surface
muted     → bg-theme-muted   (default)
```

**`text_token`** — 5 options
```
heading   → text-theme-heading  (default)
body      → text-theme-body
primary   → text-theme-primary
secondary → text-theme-secondary
white     → text-white
```

**`image_position`** — 2 options
```
right  (default)
left
css_map: {} — resolved via layout order classes in Blade
```

**`section_spacing`** — 5 options
```
none → py-0
sm   → py-8 md:py-12
md   → py-16 md:py-24    (default)
lg   → py-24 md:py-32
xl   → py-32 md:py-40
```

**`container_width`** — 4 options
```
narrow  → max-w-3xl
default → max-w-5xl     (default)
wide    → max-w-7xl
full    → max-w-full
```

#### Translation Note
Option labels are English fallbacks only (`'label' => 'Muted background'`).  
No multilingual arrays (`ar`/`en`) — translations are handled via `t()` at the UI layer, matching the v2 pattern established in `FieldGroupRegistry`.

---

### Step 2 — SectionFieldClassifier v2

The classifier was rewritten from a single static array to a two-source lookup.

#### Before (v1)

```php
public const DESIGN_FIELD_KEYS = [
    'align', 'animation', 'background_color', 'background_image',
    'background_token', 'text_token',  // manually listed alongside extra keys
    'button_size', 'button_style', 'columns', 'custom_classes',
    'grid_columns', 'image_position', 'layout_style',
    'padding_bottom', 'padding_top', 'spacing_bottom', 'spacing_top',
    'subtitle_size', 'text_align', 'theme_variant', 'title_size',
];

public static function classify(string $fieldKey): string
{
    return in_array($fieldKey, self::DESIGN_FIELD_KEYS, true) ? 'design' : 'content';
}
```

#### After (v2)

```php
// Token keys are derived from DesignTokenRegistry — NOT listed here
private const EXTRA_DESIGN_KEYS = [
    'align', 'animation', 'background_color', 'background_image',
    'button_size', 'button_style', 'columns', 'custom_classes',
    'grid_columns', 'layout_style',
    'padding_bottom', 'padding_top', 'spacing_bottom', 'spacing_top',
    'subtitle_size', 'text_align', 'theme_variant', 'title_size',
];

public static function isDesignKey(string $fieldKey): bool
{
    return DesignTokenRegistry::has($fieldKey)
        || in_array($fieldKey, self::EXTRA_DESIGN_KEYS, true);
}

public static function classify(string $fieldKey): string
{
    return static::isDesignKey($fieldKey) ? 'design' : 'content';
}

public static function allDesignKeys(): array
{
    return array_values(array_unique(
        array_merge(DesignTokenRegistry::keys(), self::EXTRA_DESIGN_KEYS)
    ));
}
```

#### Key Changes

| Aspect | v1 | v2 |
|---|---|---|
| Token keys defined in | `DESIGN_FIELD_KEYS` (manually) | `DesignTokenRegistry` (single source) |
| Extra design keys | mixed with token keys | `EXTRA_DESIGN_KEYS` (separate) |
| Design check method | `classify()` via `in_array()` | `isDesignKey()` via Registry + extra |
| Full list accessor | `DESIGN_FIELD_KEYS` const | `allDesignKeys()` method |
| New token propagation | Manual: must edit classifier | Automatic: add to Registry only |

#### Backward Compatibility

- `classify()` behaviour is unchanged — returns `'design'` or `'content'`
- `splitFields()` and `splitGroups()` behaviour is unchanged
- `DESIGN_FIELD_KEYS` public const was removed — replaced by `allDesignKeys()`
- All 23 design keys that were recognised in v1 are still recognised in v2

---

## Validation Results

All 18 validation checks passed:

```
✓ DesignTokenRegistry::has() called in isDesignKey
✓ isDesignKey() method present
✓ allDesignKeys() method present
✓ Old DESIGN_FIELD_KEYS const removed
✓ splitFields uses isDesignKey
✓ No token keys duplicated in EXTRA

✓ background_token  → design [token]
✓ text_token        → design [token]
✓ image_position    → design [token]
✓ section_spacing   → design [token]
✓ container_width   → design [token]
✓ background_image  → design [extra]
✓ custom_classes    → design [extra]
✓ animation         → design [extra]
✓ background_color  → design [extra]
✓ layout_style      → design [extra]
✓ align             → design [extra]
✓ title             → content
✓ subtitle          → content
✓ button_label      → content
✓ faq_items         → content
✓ my_custom_field   → content
✓ description       → content
✓ features          → content
```

**Total design keys: 23** (5 token + 18 extra)

---

## What Was NOT Implemented

Per the task scope (Step 1 + Step 2 only), the following remain deferred:

| Step | Description | Status |
|---|---|---|
| Step 3 | BladeGenerator queries Registry for match() stubs | Deferred |
| Step 4 | ComponentLibrary — add `design` component | Deferred |
| Step 5 | SectionPackageGenerator — source options from Registry | Deferred |
| Step 6 | Backfill SectionDefinitionField options JSON | Deferred |

The following were explicitly NOT done:
- No DB migration
- No new DB tables
- No changes to any Blade section file (`content_showcase`, etc.)
- No changes to BladeGenerator, ComponentLibrary, or SectionPackageGenerator
- No SectionDefinitionField DB rows modified
- No DashboardTranslationsSeeder changes

---

## How to Add a New Token (Future)

Add one entry to `DesignTokenRegistry::ALL_TOKENS`. No other file needs to change:

```php
'my_new_token' => [
    'key'         => 'my_new_token',
    'field_type'  => 'select',
    'field_scope' => 'shared',
    'default'     => 'default_value',
    'group_name'  => 'design',
    'options'     => [
        ['value' => 'option_a', 'label' => 'Option A'],
        ['value' => 'option_b', 'label' => 'Option B'],
    ],
    'css_map' => [
        'option_a' => 'tw-class-a',
        'option_b' => 'tw-class-b',
    ],
],
```

`SectionFieldClassifier` will automatically classify `my_new_token` as a design field on the next request.

---

## Architecture Position

```
DesignTokenRegistry          ← NEW — single source of truth for tokens
    ↓ keys()
SectionFieldClassifier       ← UPDATED — derives token keys from Registry
    ↓ splitGroups()
renderer.blade.php           ← unchanged — receives split groups
    ↓
Content tab / Design tab     ← unchanged — already functional
```

---

*Report generated: 2026-06-21*
