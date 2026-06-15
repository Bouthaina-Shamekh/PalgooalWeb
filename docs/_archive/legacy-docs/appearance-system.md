# Appearance System

## Overview

The appearance system manages the visual configuration of the site's header and footer
variants. It reads and writes directly on the single `general_settings` row (via
`GeneralSetting::firstOrCreate()`). No separate table is involved.

There are two top-level sections — **Header** and **Footer** — each with two operations:
selecting the active variant and updating that variant's settings.

---

## Core Files

| File | Role |
|------|------|
| `app/Http/Controllers/Admin/AppearanceController.php` | All 6 admin endpoints |
| `app/Models/GeneralSetting.php` | Stores variant keys + JSON settings blobs |
| `config/front_layouts.php` | Defines available header/footer variants and color libraries |
| `resources/views/dashboard/appearance/header.blade.php` | Header settings form |
| `resources/views/dashboard/appearance/footer.blade.php` | Footer settings form |

No dedicated policy file exists. Authorization is handled via `GeneralSettingPolicy`
(inherited from `ModelPolicy.__call()`) because `AppearanceController` reads/writes the
`general_settings` row.

---

## Authorization

| Method | Ability | Role slug required |
|--------|---------|--------------------|
| `header()` | `view` | `generalsettings.view` |
| `updateHeaderVariant()` | `update` | `generalsettings.update` |
| `updateHeaderSettings()` | `update` | `generalsettings.update` |
| `footer()` | `view` | `generalsettings.view` |
| `updateFooterVariant()` | `update` | `generalsettings.update` |
| `updateFooterSettings()` | `update` | `generalsettings.update` |

`super_admin` users bypass all checks via `Gate::before()` in `AppServiceProvider`.

---

## Endpoints

### GET `/admin/appearance/header`
Loads the header settings form. Passes `$settings`, `$headerVariants` (from config),
`$activeHeaderSettings` (merged defaults + stored values), and `$headerSettingsLanguages`
(active languages ordered by ID).

### POST `/admin/appearance/header/variant`
Saves the selected header variant key. Validates against `config('front_layouts.headers')`.

### POST `/admin/appearance/header/settings`
Saves the active variant's settings. For `purple_topbar`, normalizes:
- Localized text fields (`announcement_text`, `login_label`, `contact_button_label`) via
  `normalizeLocalizedTextField()` — one entry per active language code.
- Custom HEX colors via `normalizePurpleTopbarCustomColors()` — falls back to stored
  defaults, then hard-coded defaults.
- `logo_override` via `normalizeMediaPath()`.
- `color_theme` whitelisted against `purpleTopbarColorThemeKeys()`.

### GET `/admin/appearance/footer`
Loads the footer settings form. Same pattern as header.

### POST `/admin/appearance/footer/variant`
Saves the selected footer variant key.

### POST `/admin/appearance/footer/settings`
Saves the active variant's settings. For `palgoals_marketing`, normalizes localized text
fields (description, section titles, copyright), custom colors, `logo_override`,
`payment_logos` (comma-separated or array, via `normalizeMediaPathList()`), and dimension
values (`logo_width`, `logo_height`, etc.) via `normalizeDimensionValue()`.

---

## Variant Settings Storage

Variant-specific settings are stored as nested JSON inside `general_settings`:

```
header_variant_settings: {
  "purple_topbar": { "announcement_text": {…}, "color_theme": "…", … }
}

footer_variant_settings: {
  "palgoals_marketing": { "description_text": {…}, "color_theme": "…", … }
}
```

On read, `resolvedHeaderVariantSettings()` / `resolvedFooterVariantSettings()` merge
`array_replace(defaults, storedSettings)` so missing keys always receive their default
value.

---

## Language Query Pattern

Both `updateHeaderSettings()` and `updateFooterSettings()` load active languages **once**:

```php
$languages    = Language::query()->where('is_active', true)->get();
$languageCodes = $languages->pluck('code')->map(…)->filter()->values()->all();
$defaultLocale = $languages->firstWhere('id', $settings->default_language)?->code
                 ?? config('app.locale', 'en');
```

This replaces the previous two-query pattern (separate `pluck` + `find` calls).

---

## Helper Methods

| Method | Purpose |
|--------|---------|
| `settings()` | `GeneralSetting::firstOrCreate()` with safe defaults |
| `resolvedHeaderVariantSettings()` | Merges defaults + stored per-variant settings |
| `resolvedFooterVariantSettings()` | Same for footer |
| `headerVariantDefaults()` | Hard-coded defaults for `purple_topbar` |
| `footerVariantDefaults()` | Hard-coded defaults for `palgoals_marketing` |
| `normalizeMediaPath()` | Media ID / URL / relative path → stored `file_path` |
| `normalizeMediaPathList()` | Comma-separated media list → `array` of paths |
| `normalizeLocalizedTextField()` | Builds `{locale: value}` map from `pv_texts[locale][field]` |
| `normalizePurpleTopbarCustomColors()` | Validates HEX, falls back to stored → defaults |
| `normalizePalgoalsMarketingCustomColors()` | Same for footer variant |
| `normalizeHexColorValue()` | Regex `/^#([A-F0-9]{3}|[A-F0-9]{6})$/` validation |
| `normalizeDimensionValue()` | Clamps integer to `[min, max]`, returns fallback if invalid |

---

## Known Gaps / Future Work

- **`pv_login_url` / `pv_contact_button_url` accept arbitrary strings** — the default
  values (`/client/login`, `#contact`) are relative paths so the Laravel `url` rule cannot
  be applied directly. A permissive regex allowing relative and absolute URLs could be
  added if stricter validation is needed.
- **`header()` and `footer()` each fire a separate Language query** — these view methods
  still run their own `Language::query()->where('is_active', true)->orderBy('id')->get()`
  for the language switcher UI. This is acceptable (1 query per page load) but could be
  unified with a shared scope or eager-loaded via the settings object if Language data is
  ever cached globally.
- **No `DB::transaction()`** — variant settings update a single JSON column on one row;
  risk is minimal, but wrapping in a transaction would be prudent if additional side-effects
  (e.g., cache invalidation, media reference tracking) are added later.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-08 | Authorization (`$this->authorize()`) added to all 6 endpoints; Language queries in `updateHeaderSettings()` and `updateFooterSettings()` deduplicated from 2 queries to 1 per request (using `->get()` + `->firstWhere()` instead of separate `pluck` + `find` calls); `pv_custom_colors` and `fm_custom_colors` now read from `$validated` instead of `$request->input()`, ensuring validation is respected; flash messages wrapped in `__()` for i18n. |
