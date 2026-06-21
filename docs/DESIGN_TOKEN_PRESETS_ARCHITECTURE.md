# Design Token Presets — Architecture & Implementation

**Status**: Implemented (Phase 3 of Section Design Tokens roadmap)  
**Date**: 2026-06-21  
**Files created/modified**: 4  

---

## What Was Built

One-click buttons that bulk-add pre-defined design token fields to any SectionDefinition.

### Before
A developer creating a new section had to:
1. Open the SectionDefinition fields page
2. Click "Add Field" manually for each design token
3. Fill in field_key, field_type, field_scope, options, default_value — for each one
4. Repeat 5 times for all design tokens

### After
1. Open the SectionDefinition fields page
2. Click one button (e.g. "الألوان" or "كل إعدادات التصميم")
3. Confirm — done. Fields are created in one request.

---

## Architecture

### Core Rule

> **All field attributes must be sourced from `DesignTokenRegistry`.**  
> No field definition is ever hardcoded in the Service, Controller, or View.

This ensures that changing a token's options, default, or group_name in one place (`DesignTokenRegistry`) automatically propagates to the preset fields created by this feature.

---

## Files

### New: `app/Support/Sections/DesignTokenPresetService.php`

Single responsibility: define which token keys belong to each preset, and orchestrate field creation.

```
DesignTokenPresetService
├── ALL_PRESETS (const)         — 5 presets, each with: label, icon, color, tokens[]
├── keys() → list<string>       — all preset keys
├── get(key) → array|null       — full preset definition
├── all() → array               — all presets
├── presetKeys(key) → list      — token keys for a given preset
├── buildFields(keys, startSort)— builds attribute arrays from Registry
├── missingFields(def, keys)    — filters to only absent field_keys (O(1) lookup)
└── apply(key, definition)      — orchestrate: resolve → filter → build → insert (transaction)
```

**Critical design decision**: `buildFields()` calls `DesignTokenRegistry::get($tokenKey)` for every field attribute — field_type, field_scope, default_value, options, group_name. The service has no knowledge of what a `background_token` looks like; that belongs to the Registry.

### Modified: `app/Http/Controllers/Admin/SectionDefinitionFieldController.php`

Added `applyDesignPreset()`:
- Validates `design_preset_key` against `DesignTokenPresetService::keys()` (in-list rule)
- Delegates entirely to `DesignTokenPresetService::apply()`
- Flash message uses `strtr()` for `:count` substitution (per CLAUDE.md — `t()` doesn't support parameters)

### Modified: `routes/dashboard.php`

```php
Route::post('/apply-design-preset', [SectionDefinitionFieldController::class, 'applyDesignPreset'])
    ->name('apply_design_preset');
```

Separate route from the existing `apply-preset` (FieldPresetLibrary) — they serve different purposes and different data sources.

### Modified: `resources/views/dashboard/section_definitions/fields/index.blade.php`

Added two blocks:

1. **Design Token Presets card** — amber icon header + 5 colored buttons (rendered from `DesignTokenPresetService::all()` in Blade). Button colors come from the existing `$presetColors` map.

2. **Shared design preset form** — `id="design-preset-apply-form"`, a hidden POST form with `design_preset_key` input, submitted by JS.

3. **JS handler** — in the existing `@push('scripts')` IIFE: confirm dialog → set input value → submit form.

---

## The 5 Presets

| Preset Key | Label | Tokens Added |
|-----------|-------|-------------|
| `colors` | الألوان 🎨 | `background_token`, `text_token` |
| `spacing` | المسافات ↕️ | `section_spacing` |
| `container` | عرض المحتوى ↔️ | `container_width` |
| `image_layout` | موضع الصورة 🖼️ | `image_position` |
| `all_design` | كل إعدادات التصميم ✨ | All 5 tokens |

---

## Duplicate Protection

`missingFields(SectionDefinition $def, array $tokenKeys): array`

1. Fetches all existing `field_key` values for the definition with `->pluck('field_key')->flip()`
2. `flip()` converts the Collection to `{field_key => index}` for O(1) `has()` lookups
3. Returns only the keys NOT in the existing set

`apply()` calls `missingFields()` before `buildFields()`. If all keys are already present, it returns `0` immediately — no DB write, no transaction.

---

## Data Flow

```
User clicks "الألوان" button
  → JS confirm dialog
  → #design-preset-apply-form submits POST /apply-design-preset?design_preset_key=colors
  → applyDesignPreset() validates 'colors' ∈ DesignTokenPresetService::keys()
  → DesignTokenPresetService::apply('colors', $sectionDefinition)
      → presetKeys('colors')          → ['background_token', 'text_token']
      → missingFields($def, keys)     → e.g. ['background_token'] (text_token already exists)
      → buildFields(['background_token'], $nextSort)
          → DesignTokenRegistry::get('background_token') → full token definition
          → returns [{field_key, label, field_type, field_scope, options, default_value, ...}]
      → DB::transaction { $def->fields()->create($attrs) }
      → returns 1
  → flash "تمت إضافة 1 حقل تصميم بنجاح."
  → redirect back to fields index
```

---

## What Was NOT Changed

Per the implementation spec, these files were left untouched:

- `BladeGenerator.php`
- `ComponentLibrary.php`
- `SectionPackageGenerator.php`
- `SectionDefinitionField` model schema
- Any Blade section file in `front/sections/`
- No migration, no backfill

---

## Adding a New Preset

1. Add an entry to `ALL_PRESETS` in `DesignTokenPresetService`:
   ```php
   'my_preset' => [
       'label'  => 'اسم المجموعة',
       'icon'   => 'ti-icon-name',
       'color'  => 'rose',               // any key in $presetColors
       'tokens' => ['background_token', 'section_spacing'],
   ],
   ```
2. Add translation key `dashboard.Design_Token_Presets_*` in DashboardTranslationsSeeder if needed
3. No other file needs to change

---

## Adding a New Design Token

1. Add the token to `DesignTokenRegistry::ALL_TOKENS`
2. `SectionFieldClassifier::isDesignKey()` immediately recognises it (v2 architecture)
3. Any preset whose `tokens[]` includes the new key automatically picks it up via `buildFields()`

---

## Related Files

- `app/Support/Sections/DesignTokenRegistry.php` — token definitions (Phase 2)
- `app/Support/Sections/SectionFieldClassifier.php` — uses Registry for UI tab classification (Phase 2)
- `docs/SECTION_DESIGN_TOKENS_ARCHITECTURE.md` — full architectural analysis (Phase 1)
- `docs/DESIGN_TOKEN_REGISTRY_REPORT.md` — implementation report for Phases 1–2
