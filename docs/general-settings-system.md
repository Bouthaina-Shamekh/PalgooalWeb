# General Settings System

## Overview

The general settings system manages global site configuration: logos, favicon, default
language, header/footer variants, contact information, social links, and localized text
content (site title, site description, contact address per language).

There is always exactly **one** row in `general_settings`. All reads use
`GeneralSetting::first()` and all writes are `update()` / `save()` on that single record.

---

## Core Files

| File | Role |
|------|------|
| `app/Models/GeneralSetting.php` | Eloquent model — fillable fields, casts, localized content resolution |
| `app/Http/Controllers/Admin/HomeController.php` | Admin endpoints — view, update, autosave, export, import |
| `app/Policies/GeneralSettingPolicy.php` | Delegates to `ModelPolicy.__call()` role-slug pattern |
| `resources/views/dashboard/general-setting.blade.php` | Layout wrapper (includes page partial) |
| `resources/views/dashboard/general-setting-page.blade.php` | Main settings form (logos, variants, contacts, socials, translations) |
| `database/migrations/2025_06_17_113900_create_general_settings_table.php` | Base schema |
| `database/migrations/2025_10_02_082705_add_contact_info_and_social_links_*.php` | Contact info + social links columns |
| `database/migrations/2026_03_*` | Header/footer variant columns and localized content |

---

## Data Model

### `general_settings` table (key columns)

| Column | Type | Notes |
|--------|------|-------|
| `site_title` | varchar | Primary/default locale site title |
| `site_discretion` | varchar | Primary/default locale site description |
| `logo` / `dark_logo` / `sticky_logo` / `dark_sticky_logo` | varchar nullable | Stored as `file_path` relative to storage |
| `admin_logo` / `admin_dark_logo` | varchar nullable | Dashboard logo variants |
| `favicon` | varchar nullable | Favicon path |
| `default_language` | bigint FK | References `languages.id` |
| `active_header_variant` | varchar | Must be a key in `config('front_layouts.headers')` |
| `active_footer_variant` | varchar | Must be a key in `config('front_layouts.footers')` |
| `header_show_promo_bar` / `header_is_sticky` | boolean | Header behavior flags |
| `header_variant_settings` / `footer_variant_settings` | json | Per-variant settings arrays |
| `footer_show_contact_banner` / `footer_show_payment_methods` | boolean | Footer behavior flags |
| `contact_info` | json | `{phone, email, address}` |
| `social_links` | json | `{facebook, twitter, linkedin, instagram, whatsapp}` |
| `localized_content` | json | `{site_title: {ar:…, en:…}, site_discretion: {…}, contact_address: {…}}` |

---

## Authorization

Uses `ModelPolicy.__call()` role-slug pattern. A global `Gate::before()` in
`AppServiceProvider` grants `super_admin` users unrestricted access.

`ModelPolicy` maps `GeneralSetting` → `generalsettings` (via `Str::plural()`).

| Controller method | Required role slug |
|-------------------|--------------------|
| `general_settings` (view) | `generalsettings.view` |
| `updateGeneralSettings`, `autoSaveGeneralSettings`, `importGeneralSettings` | `generalsettings.update` |
| `exportGeneralSettings` | `generalsettings.view` |

---

## Endpoints

### GET `/admin/general_settings`
Loads the settings form. Builds the `$generalSetting` array (merging DB values with
`baseContactInfo()` / `baseSocialLinks()` defaults) and passes `$languages` and
`$contentLanguages` to the view.

### POST `/admin/general_settings/update`
Full save. Validates all fields, normalizes logo paths via `normalizeMediaPath()`, builds
localized content from `gs_texts[{locale}]` form inputs, and saves to the single row.

### POST `/admin/general_settings/autosave`
Partial save called by the JS auto-save debouncer. Only updates fields that are present
in the request. Returns JSON `{saved: true, saved_at: "HH:MM:SS"}` on success or
`{saved: false, message: "…"}` with HTTP 422 on validation failure.

> **Note:** Because this endpoint returns JSON, Laravel's exception handler automatically
> converts `AuthorizationException` to a JSON 403 response when the caller sends
> `Accept: application/json`.

### GET `/admin/general_settings/export`
Streams a JSON file containing all settings fields plus `meta` (schema version, exported
timestamp, app URL). The file is named `general-settings-YYYYMMDD-HHmmss.json`.

### POST `/admin/general_settings/import`
Accepts a `.json` or `.txt` file, validates all fields (including variant whitelisting via
`Rule::in()`), applies `normalizeMediaPath()` to all logo/favicon fields, normalizes
localized content, and saves. Replaces the existing row if it exists; creates if not.

---

## Logo / Image Handling

All logo and favicon values go through `normalizeMediaPath()` before being persisted:

1. If the value is a pure integer string (e.g. `"42"`) → resolves `Media::find(42)->file_path`.
2. If the value is a full URL → strips the `/storage/` prefix and stores the relative path.
3. If the value is already a relative path → left-strips `/` and `storage/` prefix.
4. Empty string or null → stored as `null`.

This pipeline is applied consistently in both `updateGeneralSettings()` (via direct field
assignment) and `importGeneralSettings()` (via an explicit loop over logo fields before
calling `$setting->update()`).

---

## Localized Content

Site title, site description, and contact address are stored in both their "primary" flat
columns (`site_title`, `site_discretion`, `contact_info.address`) and in the structured
`localized_content` JSON:

```json
{
  "site_title":     {"ar": "…", "en": "…"},
  "site_discretion":{"ar": "…", "en": "…"},
  "contact_address":{"ar": "…", "en": "…"}
}
```

On read, `GeneralSetting::resolveLocalizedContent()` picks the value matching the
current app locale, falling back to the default language, then the fallback locale, then
any available translation.

The flat columns are kept in sync by `extractPrimaryLocalizedValue()` on every save so
that code that reads `$setting->site_title` directly (without going through the resolver)
still gets the default-locale value.

---

## Known Gaps / Future Work

- **`updateGeneralSettings` does not wrap in `DB::transaction()`** — the save is a single
  row so the risk is minimal, but the import path calls `update()` after file parsing;
  adding a transaction would ensure atomicity if the model is ever extended to touch
  related tables.
- **`GeneralSettingPolicy` is empty** — it inherits all behavior from `ModelPolicy.__call()`.
  If fine-grained per-field authorization is ever needed (e.g. only super-admins can change
  the default language), dedicated policy methods should be added here.
- **Social links use the `url` rule** — `whatsapp` is treated as a URL
  (`https://wa.me/…`), which is consistent but means plain phone numbers cannot be stored
  without wrapping them in a `tel:` URL.
- **Export does not include logo files** — only the stored `file_path` strings are
  exported. Importing on a different server requires the same files to exist at the same
  paths in `storage/app/public/`.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-08 | Authorization (`$this->authorize()`) added to all 5 endpoints (view, update, autosave, export, import); `importGeneralSettings()` now applies `normalizeMediaPath()` to all logo/favicon fields before saving (previously saved raw strings); `Language::query()->get()` calls in `updateGeneralSettings()` and `autoSaveGeneralSettings()` deduplicated from 2 queries to 1 per request; `contact_info.address` validation rule (`max:1000`) added to `updateGeneralSettings()` and `autoSaveGeneralSettings()`; flash messages in import wrapped in `__()`; `@can('edit')` corrected to `@can('update')` in `general-setting-page.blade.php`. |
