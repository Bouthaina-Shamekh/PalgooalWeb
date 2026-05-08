# Menu System

## Overview

The menu system manages navigation menus (called "Headers" internally) and their items.
Each menu has a `slug`, a `location_key` (e.g. `header_primary`, `footer_primary`), and an
ordered list of `HeaderItem` records. Items can be one of three types:

- **link** — a custom label + URL per language
- **page** — bound to an internal `Page`; labels and URLs are derived from page translations
- **dropdown** — a parent label with nested child items (link or page type), stored as a
  JSON `children` array on `HeaderItem`

There is no separate "menu item translation table" for dropdown children — those labels live
in the `children` JSON column of the parent `HeaderItem`.

---

## Core Files

| File | Role |
|------|------|
| `app/Http/Controllers/Admin/MenuController.php` | All 9 admin endpoints |
| `app/Models/Header.php` | Eloquent model — fillable, casts, SoftDeletes, item cascade |
| `app/Models/HeaderItem.php` | Eloquent model — SoftDeletes, label/URL accessors |
| `app/Models/HeaderItemTranslation.php` | Per-locale label + URL for link/page items |
| `app/Policies/HeaderPolicy.php` | Delegates to `ModelPolicy.__call()` |
| `resources/views/dashboard/header.blade.php` | Full menu management UI (single-page) |
| `database/migrations/2025_06_29_221233_create_headers_table.php` | Base schema |
| `database/migrations/2025_06_29_221310_create_header_items_table.php` | Items schema |
| `database/migrations/2025_06_29_221338_create_header_item_translations_table.php` | Translations schema |
| `database/migrations/2026_05_08_000001_add_soft_deletes_to_headers_and_items_tables.php` | `deleted_at` on both tables |

---

## Authorization

`HeaderPolicy` extends `ModelPolicy.__call()`. The class name resolves to `headers`, so
ability slugs are:

| Method | Authorize call | Role slug |
|--------|---------------|-----------|
| `index()` | `viewAny, Header::class` | `headers.view` |
| `store()` | `create, Header::class` | `headers.create` |
| `update($menu)` | `update, $menu` | `headers.update` |
| `destroy($menu)` | `delete, $menu` | `headers.delete` |
| `duplicate($menu)` | `create, Header::class` | `headers.create` |
| `storeItem($menu)` | `update, $menu` | `headers.update` |
| `updateItem($menu, $item)` | `update, $menu` | `headers.update` |
| `destroyItem($menu, $item)` | `update, $menu` | `headers.update` |
| `reorderItems($menu)` | `update, $menu` | `headers.update` |

`super_admin` users bypass all checks via `Gate::before()` in `AppServiceProvider`.

---

## Endpoints

### GET `/admin/menus`
Loads all menus and the selected menu (via `?menu=ID` query param, defaulting to
`header_primary` or first). If no menus exist at all, a default "Main Menu" is created
inside a `DB::transaction()` with a re-check to prevent race conditions.

### POST `/admin/menus`
Creates a new menu. Validates `menu_name` (max:120) and `menu_location` (whitelisted
against `config('front_layouts.menu_locations')`). Generates a unique slug via
`makeUniqueSlug()`.

### PATCH `/admin/menus/{menu}`
Updates menu identity (name, slug, location, is_active). Slug validated with `regex` and
`Rule::unique()->ignore($menu->id)`.

### DELETE `/admin/menus/{menu}`
Soft-deletes the menu (and cascades to its items — see SoftDeletes section). Refuses
deletion if only one menu remains.

### POST `/admin/menus/{menu}/duplicate`
Clones the menu and all its items + translations inside a `DB::transaction()`.

### POST `/admin/menus/{menu}/items`
### PATCH `/admin/menus/{menu}/items/{item}`
Both delegate to `validateAndNormalizeItemPayload()` then call `syncPageTranslations()` or
`syncManualTranslations()`. The ownership check `$item->header_id === $menu->id` is
enforced before authorization.

### DELETE `/admin/menus/{menu}/items/{item}`
Soft-deletes the item after ownership check.

### POST `/admin/menus/{menu}/items/reorder`
Accepts `ids[]` (array of integers). Validates that all IDs belong to the given menu
before updating `order` values inside a transaction. Returns JSON `{ok: true}`.

---

## Item Payload Normalization (`validateAndNormalizeItemPayload`)

Single validation pass — `page_id` is conditionally `required` vs `nullable` based on
the pre-peeked `$request->input('type')`:

```php
'page_id' => $requestedType === 'page'
    ? ['required', 'integer', 'exists:pages,id']
    : ['nullable', 'integer', 'exists:pages,id'],
```

For `page` type: label + URL are derived from the `Page` translations via
`syncPageTranslations()` — the admin does not enter them manually.

For `link` / `dropdown` type: `syncManualTranslations()` upserts one
`HeaderItemTranslation` row per active language.

For `dropdown` type: child items are validated manually (loop over `children` input) and
stored as a JSON array in `HeaderItem.children`.

---

## SoftDeletes

Both `Header` and `HeaderItem` use `SoftDeletes`. When a `Header` is soft-deleted:
- A `deleting` listener cascades `->delete()` to all its items (soft-deletes them).
- A `restoring` listener cascades `->restore()` on trashed items when the menu is restored.

DB-level `onDelete('cascade')` on `header_items.header_id` still fires on hard-delete /
force-delete, so orphan cleanup is covered in both scenarios.

---

## Known Gaps / Future Work

- **`index()` writes to DB on GET** — a default menu is created if none exists. This was
  wrapped in a transaction with a re-check, but the ideal fix is a database seeder or
  migration default.
- **`loadLanguages()` falls back to all languages** — if no languages are `is_active`,
  the controller loads all languages including inactive ones. A log warning or hard failure
  may be preferable.
- **Dropdown children are not individually validatable via `Rule::exists`** — child
  `page_id` values are validated by a manual loop (not Laravel's built-in `exists` rule).
  This is acceptable but means a race condition between page deactivation and menu save
  could store a reference to a now-inactive page.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-08 | `HeaderPolicy` created (extends `ModelPolicy`); `$this->authorize()` added to all 9 methods; `index()` default-menu creation wrapped in `DB::transaction()` with re-check to prevent race condition; `validateAndNormalizeItemPayload()` merged double `validate()` into single call with conditional `page_id` rule + now reads `$validated['page_id']` instead of `$request->input('page_id')`; `@can` directives added to all action sections in `header.blade.php`; `SoftDeletes` added to `Header` and `HeaderItem` models + migration `2026_05_08_000001`; `Header::booted()` cascades soft-delete/restore to items. |
