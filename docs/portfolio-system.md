# Portfolio System

## Overview

The portfolio system manages the project showcase gallery (معرض الأعمال). Each portfolio
entry stores metadata (images, dates, ordering) plus multilingual translations (title,
description, type, materials, link, status) through a `portfolio_translations` table.

---

## Core Files

| File | Role |
|------|------|
| `app/Models/Portfolio.php` | Eloquent model — fillable fields, casts, SoftDeletes, translation helpers |
| `app/Models/PortfolioTranslation.php` | Translation model — per-locale text fields |
| `app/Http/Controllers/Admin/PortfolioController.php` | Admin CRUD — create, store, edit, update, destroy |
| `app/Policies/PortfolioPolicy.php` | Authorization via `ModelPolicy.__call()` role-slug pattern |
| `resources/views/dashboard/portfolios/index.blade.php` | Listing table with actions |
| `resources/views/dashboard/portfolios/create.blade.php` | Create form wrapper |
| `resources/views/dashboard/portfolios/edit.blade.php` | Edit form wrapper |
| `resources/views/dashboard/portfolios/_form.blade.php` | Shared form partial (media picker, translations) |
| `database/migrations/*_create_portfolios_table.php` | Base schema |
| `database/migrations/2026_05_05_000002_update_portfolios_table.php` | Makes `default_image` nullable, adds `deleted_at` |

---

## Data Model

### `portfolios` table (key columns)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `default_image` | varchar nullable | File path resolved from a `media` record ID |
| `images` | json nullable | Array of file paths (multiple gallery images) |
| `delivery_date` | date | Project delivery date |
| `order` | integer | Display order (lower = first) |
| `implementation_period_days` | integer nullable | Duration of the project in days |
| `slug` | varchar unique | URL-friendly identifier auto-generated from the active-locale title |
| `client` | varchar nullable | Client name |
| `deleted_at` | timestamp nullable | Soft-delete column |

### `portfolio_translations` table (key columns)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `portfolio_id` | bigint FK | References `portfolios.id` |
| `locale` | varchar | Language code (`ar`, `en`, …) |
| `title` | varchar | Project title |
| `description` | text nullable | Full description |
| `type` | varchar nullable | Project type (free-text with autocomplete suggestions) |
| `materials` | varchar nullable | Materials used |
| `link` | varchar nullable | External URL |
| `status` | varchar nullable | e.g. `مكتمل`, `Active` |

---

## Authorization

Authorization uses the `ModelPolicy.__call()` role-slug pattern inherited from `ModelPolicy`.
A role slug is constructed as `portfolios.{ability}`.

| Controller method | Required role slug |
|-------------------|--------------------|
| `index` | `portfolios.view-any` |
| `create`, `store` | `portfolios.create` |
| `edit`, `update` | `portfolios.update` |
| `destroy` | `portfolios.delete` |

`super_admin` users bypass all role checks.

---

## Controller Design

### Lazy Language Loading

`PortfolioController` avoids querying `languages` on every request. The `loadLanguages()`
method is called only from `create()`, `store()`, `edit()`, and `update()` — the actions
that actually need the language list. `index()` and `destroy()` skip it entirely.

```php
protected function loadLanguages(): void
{
    if ($this->languages !== null) {
        return;
    }
    $this->languages = Language::get();
    // build type and status suggestions from already-loaded collection …
}
```

### Translation Validation

`buildTranslationRules()` uses `$this->languages` (already loaded) to determine which
locales are active. Active-locale fields are `required`; inactive-locale fields are
`nullable`.

### Slug Generation

`generateUniqueSlug()` picks the title from the first active-locale translation, then
appends a numeric suffix until a unique value is found:

```
portfolio → portfolio-1 → portfolio-2 …
```

The `portfolios.slug` column carries a `UNIQUE` database constraint. In the rare case
where two concurrent requests race past the existence check and both attempt to insert
the same slug, `store()` and `update()` catch the resulting `QueryException` (SQLSTATE
23000) and retry `generateUniqueSlug()` up to 3 times before re-throwing.

### Image Handling

Images are stored as file paths resolved via `resolveMediaIdsToPaths()`. The controller
accepts a Media `id` (integer) for `default_image` and a comma-separated list of Media IDs
for `images`. All IDs are cast to `intval()` before use to prevent arbitrary string
injection.

```
Input:  default_image = "42"        → stored as: "uploads/abc.jpg"
Input:  images        = "1,2,3"     → stored as: ["uploads/a.jpg","uploads/b.jpg","uploads/c.jpg"]
```

### Exception Handling

All DB writes are wrapped in `DB::transaction()`. On failure:
- `Log::error()` records the full exception with stack trace.
- The user sees a generic Arabic message: `"حدث خطأ أثناء الحفظ، يرجى المحاولة مجدداً."`

---

## Index View

The index view uses the already-eager-loaded `translations` collection (loaded in the
controller via `Portfolio::with('translations')`). The view must **not** call
`$portfolio->translations()` as a query builder — that would trigger an N+1 problem.

```blade
@php
    $trans = $portfolio->translations->firstWhere('locale', app()->getLocale());
@endphp
<td>{{ $trans?->title ?? '—' }}</td>
```

The null-safe operator (`?->`) prevents fatal errors when a translation record is missing
for the current locale.

---

## Form Partial (`_form.blade.php`)

The shared form partial handles:

- **Media picker** — `<x-dashboard.media-picker>` component for both `default_image`
  (single) and `images` (multiple). Accepts `id`, `name`, `value`, `:previewUrls`, and
  optional `multiple="true"`.
- **Translation tabs** — per-language tabbed panels driven by `$languages` and
  `$portfolioTranslations` passed from the controller.
- **Type autocomplete** — suggestions sourced from `$typeSuggestions[$lang->code]` (built
  lazily from existing DB values in `loadLanguages()`).
- **Status select** — dropdown populated from `$statusSuggestions[$lang->code]`.

All JavaScript for the media modal and the language-tab switcher lives inside a single
`@push('scripts')` block. (Two separate `@push` blocks that previously existed have been
merged to ensure scripts are emitted only once per page.)

---

## Soft Deletes

The `Portfolio` model uses `SoftDeletes`. `destroy()` marks the record with `deleted_at`
rather than permanently removing it. To recover or permanently remove a record:

```php
// Restore
Portfolio::withTrashed()->find($id)->restore();

// Permanent delete (also deletes associated translation rows if cascade is set)
Portfolio::withTrashed()->find($id)->forceDelete();
```

---

## Known Gaps / Future Work

- **`forceDelete()` does not cascade to `portfolio_translations`** — add a database-level
  `ON DELETE CASCADE` foreign key or override `forceDelete()` to remove translations first.
- **No audit trail** — large content changes (image swaps, title edits) are not logged.
  Consider an `activity_log` table or a package like `spatie/laravel-activitylog`.
- **Media cleanup** — deleting (or soft-deleting) a portfolio does not mark associated
  `Media` records as unused. Orphaned files accumulate over time.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-08 | `generateUniqueSlug()` callers (`store`, `update`) now retry up to 3 times on `QueryException` SQLSTATE 23000 (concurrent slug collision). |
| 2026-05-05 | Authorization added to all 6 controller methods; `loadLanguages()` extracted to avoid N+1 on every request; `buildTranslationRules()` uses already-loaded language collection; explicit `$portfolioData` array replaces unsafe `$request->except()`; null-safe `?->` operators throughout; `Log::error()` + generic user message replaces exception leakage; `images` field validated as comma-separated integers via regex; `resolveMediaIdsToPaths()` casts IDs to `intval()`; `SoftDeletes` added to `Portfolio` model; migration added to make `default_image` nullable and add `deleted_at`; index view fixed to use eager-loaded collection (no N+1); `@can('edit')` corrected to `@can('update', $portfolio)`; delete confirmation dialog added; duplicate `@push('scripts')` blocks merged in `_form.blade.php`. |
