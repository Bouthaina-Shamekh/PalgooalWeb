# Pages System

## Overview

The Pages system manages the marketing website's top-level CMS pages. Each page is a
`context = 'marketing'` record that owns zero or more `PageTranslation` rows (one per
active locale) and zero or more `Section` records (its builder blocks). The admin panel
exposes full CRUD, homepage assignment, activation toggle, and builder-mode selection.

---

## Core Files

| Layer | File |
|---|---|
| Model | `app/Models/Page.php` |
| Translation model | `app/Models/PageTranslation.php` |
| Controller | `app/Http/Controllers/Admin/PageController.php` |
| Policy | `app/Policies/PagePolicy.php` |
| Index view | `resources/views/dashboard/pages/index.blade.php` |
| Create view | `resources/views/dashboard/pages/create.blade.php` |
| Edit view | `resources/views/dashboard/pages/edit.blade.php` |
| Routes | `routes/dashboard.php` (prefix `pages`, name prefix `dashboard.pages.`) |

---

## Data Model

### `pages` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `context` | string | `'marketing'` for all admin-managed pages |
| `subscription_id` | FK nullable | Legacy tenant linkage |
| `tenant_id` | FK nullable | Canonical tenant ownership (future) |
| `builder_mode` | string | Always `'sections'` for current pages |
| `is_active` | boolean | `false` = draft, not rendered on front |
| `is_home` | boolean | At most one row should be `true` |
| `published_at` | datetime nullable | Informational; not enforced by scopes yet |
| `created_at` / `updated_at` | timestamps | |

> **No SoftDeletes.** `destroy()` issues a hard delete. Cascade to
> `page_translations` and `sections` depends on database FK constraints or
> must be handled at the application level.

### `page_translations` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `page_id` | FK → pages.id | |
| `locale` | string | e.g. `'ar'`, `'en'` |
| `slug` | string nullable | SEO URL segment, trimmed on set |
| `title` | string | Required |
| `content` | text nullable | Raw content for simple pages |
| `meta_title` | string nullable | |
| `meta_description` | string nullable | |
| `meta_keywords` | json nullable | Cast to array |
| `og_image` | string nullable | OG image path |
| `created_at` / `updated_at` | timestamps | |

The combination `(page_id, locale)` is the effective unique key. The `updateOrCreate`
in `update()` matches on `locale` via the relation scope (which already binds `page_id`)
so duplicates cannot be created by the admin controller.

---

## Page Model

```php
// app/Models/Page.php

// Relationships
$page->translations   // hasMany PageTranslation
$page->sections       // hasMany Section, ordered by `order`
$page->subscription   // belongsTo Subscription (legacy)
$page->tenant         // belongsTo Subscription via tenant_id

// Helper
$page->translation(?string $locale = null): ?PageTranslation
// Returns the translation for the requested locale, with fallback to first
// available translation. N+1 safe when translations are eager-loaded.

// Magic accessors
$page->slug   // → $page->translation()?->slug
$page->title  // → $page->translation()?->title

// Query scopes
Page::marketing()             // where context = 'marketing'
Page::tenant(?$subscription)  // context = 'tenant'  or  tenant_id = $id
Page::active()                // where is_active = true
Page::whereSlug($slug, $locale) // join translations by slug+locale
```

---

## Authorization

Authorization is handled by `PagePolicy` which extends `ModelPolicy`. Ability slugs
follow the pattern `pages.<kebab-method-name>` (with `viewAny` mapped to `view`).

| Controller method | `$this->authorize()` call | Ability slug |
|---|---|---|
| `index()` | `viewAny, Page::class` | `pages.view` |
| `create()` | `create, Page::class` | `pages.create` |
| `store()` | `create, Page::class` | `pages.create` |
| `edit()` | `update, $page` | `pages.update` |
| `update()` | `update, $page` | `pages.update` |
| `destroy()` | `delete, $page` | `pages.delete` |
| `toggleActive()` | `toggleActive, $page` | `pages.toggle-active` |
| `setHome()` | `setHome, $page` | `pages.set-home` |
| `updateBuilderMode()` | `updateBuilderMode, $page` | `pages.update-builder-mode` |

Super-admins bypass all checks through the `Gate::before()` hook registered in
`AppServiceProvider`.

Blade guards in `index.blade.php`:

| UI element | Guard |
|---|---|
| "Add Page" button | `@can('create', \App\Models\Page::class)` |
| Edit icon | `@can('update', $p)` |
| Delete button + hidden form | `@can('delete', $p)` |
| "Make Homepage" form | `@can('setHome', $p)` |
| Toggle-active button | `@can('toggleActive', $p)` (shows read-only badge otherwise) |

---

## Endpoints

All routes are prefixed with `dashboard/pages` and named `dashboard.pages.*`.

| Method | URI | Route name | Controller method |
|---|---|---|---|
| GET | `/pages` | `dashboard.pages.index` | `index()` |
| GET | `/pages/create` | `dashboard.pages.create` | `create()` |
| POST | `/pages` | `dashboard.pages.store` | `store()` |
| GET | `/pages/{page}/edit` | `dashboard.pages.edit` | `edit()` |
| PUT/PATCH | `/pages/{page}` | `dashboard.pages.update` | `update()` |
| DELETE | `/pages/{page}` | `dashboard.pages.destroy` | `destroy()` |
| POST | `/pages/{page}/toggle-active` | `dashboard.pages.toggle-active` | `toggleActive()` |
| POST | `/pages/{page}/set-home` | `dashboard.pages.set-home` | `setHome()` |
| POST | `/pages/{page}/builder-mode` | `dashboard.pages.builder-mode` | `updateBuilderMode()` |

---

## Controller Design

### Marketing-context guard

Every method that receives a `Page` model binding calls the private helper:

```php
private function isMarketingContext(Page $page): bool
{
    return $page->context === 'marketing';
}
```

If the page is not a marketing page, the method aborts with 404. Authorization runs
first (before the context check) so unauthorized requests receive 403, not 404.

### Translation upsert (P2 fix)

`update()` matches translations on `(page_id + locale)` via the Eloquent relation:

```php
$page->translations()->updateOrCreate(
    ['locale' => $t['locale']],    // match key — page_id is implicit from the relation
    ['title' => ..., 'slug' => ..., ...]
);
```

The old pattern `updateOrCreate(['id' => $t['id'] ?? null], [...])` created a new row
on every save when no `id` was submitted, because `id = null` never matched any existing
record.

### Cross-page ownership check (P3 fix)

Before the transaction, `update()` validates that any submitted `translation.id` belongs
to `$page`:

```php
$ownTranslationIds = $page->translations()->pluck('id')->all();
foreach ($validated['translations'] as $t) {
    if (! empty($t['id']) && ! in_array((int) $t['id'], $ownTranslationIds, true)) {
        abort(403, 'Translation does not belong to this page.');
    }
}
```

The `translations.*.id` validation rule only verifies existence in the table; without
this ownership guard, a user with page-update access could overwrite translations
belonging to other pages.

### Slug uniqueness validation (P4 fix)

The form submits translations as a numerically-indexed array
(`translations[0][slug]`, `translations[1][slug]`). The `$attribute` inside the closure
rule is therefore `"translations.0.slug"` — not `"translations.ar.slug"`. The locale
is resolved via the request:

```php
$parts = explode('.', $attribute); // ['translations', '0', 'slug']
$index = $parts[1] ?? null;
$locale = $request->input("translations.{$index}.locale");
```

The old code read `$parts[1]` directly, which returned `'0'` / `'1'` instead of a
locale code, silently making the uniqueness check a no-op.

### Homepage assignment (`setHome`)

Setting a homepage wraps two writes in a transaction to keep consistency:

```php
DB::transaction(function () use ($page) {
    Page::where('context', 'marketing')->update(['is_home' => false]);
    $page->update(['is_home' => true]);
});
```

There is no database-level unique constraint on `is_home = true`; consistency is
maintained only at the application level.

---

## Known Gaps

| # | Severity | Description |
|---|---|---|
| G1 | Moderate | No database-level unique constraint on `(page_id, locale)` in `page_translations`. A direct DB insert or a concurrent race could still create duplicates. Adding a unique index would enforce this at the storage layer. |
| G2 | Moderate | No SoftDeletes on `Page` or `PageTranslation`. Deleting a page permanently removes its translations. A SoftDeletes migration (similar to other models in the project) would enable recovery. |
| G3 | Low | `published_at` is stored but not enforced by any scope or middleware. The front-end rendering pipeline does not currently gate on this field. |
| G4 | Low | `builder_mode` is validated as `in:sections` only. If additional builder modes are introduced, both the validation rule and the view toggle logic need updating. |
| G5 | Low | The `translations.*.id` field is still accepted in the `update()` request and validated with `exists:page_translations,id`. Since the upsert now matches on `locale`, this field is unused for matching. It can be removed from the validation rules to avoid confusion. |

---

## Changelog

| Date | Change |
|---|---|
| 2026-05-09 | P1: Added `$this->authorize()` to all 9 controller methods; filled `PagePolicy` docblock with ability slug table. |
| 2026-05-09 | P2: Fixed `updateOrCreate` to match on `locale` (via relation scope) instead of `id = null`, preventing duplicate translation rows. |
| 2026-05-09 | P3: Added cross-page ownership check — submitted translation IDs are validated against `$page->translations()` before the transaction. |
| 2026-05-09 | P4: Fixed slug uniqueness closure — locale is now resolved from `$request->input("translations.{$index}.locale")` instead of `$parts[1]`. |
| 2026-05-09 | P5: Added `@can` guards in `index.blade.php` for all action buttons. |
| 2026-05-09 | P6: Wrapped all 5 flash messages in `__()` for translation support. |
| 2026-05-09 | P7: `builder_mode` in `update()` now uses `$validated['builder_mode'] ?? 'sections'` instead of hardcoded `'sections'`. |
| 2026-05-09 | P8: Extracted `isMarketingContext()` private helper; replaced 5 repeated `$page->context !== 'marketing'` checks. |
