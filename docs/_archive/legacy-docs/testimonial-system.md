# Testimonial System

## Overview

The testimonial system manages customer reviews (شهادات العملاء). Each testimonial
stores a star rating, display order, approval status, and an optional photo (linked via
the `media` table), plus multilingual translations (name, job title, review text) through
a `feedback_translations` table.

There are two entry points:
- **Admin dashboard** (`/dashboard/testimonials`) — full CRUD by admins.
- **Public submission form** (`/testimonials/submit`) — unauthenticated clients submit
  reviews that land in a `pending` state until approved.

---

## Core Files

| File | Role |
|------|------|
| `app/Models/Testimonial.php` | Eloquent model — fillable fields, casts, SoftDeletes, scopes, image accessor |
| `app/Models/TestimonialTranslation.php` | Translation model — per-locale text fields |
| `app/Http/Controllers/Admin/TestimonialsController.php` | Admin CRUD |
| `app/Http/Controllers/Front/TestimonialSubmissionController.php` | Public submission form |
| `app/Policies/TestimonialPolicy.php` | Delegates to `ModelPolicy.__call()` role-slug pattern |
| `resources/views/dashboard/testimonials/index.blade.php` | Listing table |
| `resources/views/dashboard/testimonials/create.blade.php` | Create form wrapper |
| `resources/views/dashboard/testimonials/edit.blade.php` | Edit form wrapper |
| `resources/views/dashboard/testimonials/_form.blade.php` | Shared form partial |
| `resources/views/front/testimonials/submit.blade.php` | Public submission page |
| `database/migrations/2025_06_25_111513_create_feedbacks_table.php` | Base schema |
| `database/migrations/2026_05_05_000003_add_soft_deletes_to_feedbacks_table.php` | Adds `deleted_at` |

---

## Data Model

### `feedbacks` table (key columns)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `image_id` | bigint FK nullable | References `media.id`; `nullOnDelete` |
| `star` | integer nullable | Rating 1–5 |
| `order` | integer | Display order (lower = first) |
| `is_approved` | boolean | `true` = visible on front-end |
| `deleted_at` | timestamp nullable | Soft-delete column |

### `feedback_translations` table (key columns)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `feedback_id` | bigint FK | References `feedbacks.id` — `onDelete cascade` |
| `locale` | varchar | Language code (`ar`, `en`, …) |
| `name` | varchar | Reviewer's name |
| `major` | varchar | Job title / specialisation |
| `feedback` | text | Review text |

---

## Authorization

Uses the `ModelPolicy.__call()` role-slug pattern. A global `Gate::before()` in
`AppServiceProvider` grants `super_admin` users unrestricted access.

| Controller method | Required role slug |
|-------------------|--------------------|
| `index` | `testimonials.view` |
| `create`, `store` | `testimonials.create` |
| `edit`, `update` | `testimonials.update` |
| `destroy` | `testimonials.delete` |

The public submission route has no auth guard — it is intentionally open to all visitors.

---

## Admin Controller Design

### Lazy Language Loading

`TestimonialsController` avoids a constructor DB query. `loadLanguages()` is called only
from `create()`, `store()`, `edit()`, and `update()`. `index()` and `destroy()` skip it.

```php
protected function loadLanguages(): void
{
    if ($this->languages !== null) {
        return;
    }
    $this->languages = Language::all();
}
```

### Translation Validation

`validateTestimonialRequest()` requires **at least one complete translation** (all three
fields: `name`, `feedback`, `major`). Incomplete-but-non-empty translations produce
per-field error messages pointing to the offending language tab.

### Image Handling

`featured_image_id` is validated as `nullable|integer|exists:media,id`. The image is
linked via the `image_id` FK. The `getImageUrlAttribute()` accessor on the model resolves
the URL via the `Media` relation.

### Exception Handling

All DB writes are wrapped in `DB::transaction()`. On failure:
- `Log::error()` records the full exception with stack trace.
- The user sees a generic Arabic message.

---

## Public Submission Controller

`TestimonialSubmissionController` handles unauthenticated reviews. Key behaviors:

- All submitted testimonials land with `is_approved = false`.
- If a photo is uploaded it is stored via the `Media` model (`image_id` FK) — **not** as
  a raw file path in a non-existent `image` column.
- If the DB transaction fails after the file was uploaded, the orphaned file is cleaned up
  from disk via `Storage::disk('public')->delete($path)`.
- The POST route is throttled at **5 requests per minute per IP**
  (`middleware('throttle:5,1')`).

---

## Soft Deletes

The `Testimonial` model uses `SoftDeletes`. `destroy()` marks the record with `deleted_at`
rather than permanently removing it. Because `feedback_translations` has
`onDelete('cascade')` at the DB level, translation rows are **not** automatically
soft-deleted — they are hard-deleted when the parent is soft-deleted. This is intentional:
translations are purely structural and have no independent lifecycle.

To restore a soft-deleted testimonial:
```php
Testimonial::withTrashed()->find($id)->restore();
```

To permanently purge:
```php
Testimonial::withTrashed()->find($id)->forceDelete();
```

---

## Known Gaps / Future Work

- **`order` uniqueness is not enforced** — two testimonials can share the same display
  order value, leading to undefined sort order. Consider enforcing uniqueness or
  providing a drag-and-drop reorder UI.
- **No admin notification on new submission** — when a visitor submits a review, no
  email or dashboard notification is sent to admins. Consider dispatching a
  `NewTestimonialSubmitted` event.
- **Public image upload has no dimension validation** — very large images (e.g. 10 000 ×
  10 000 px) pass the 2 MB size check. Consider adding `dimensions` validation rules.
- **`feedback_translations` cascade is hard-delete** — restoring a soft-deleted
  testimonial will not restore its translations (they were hard-deleted). If recovery is
  needed, add `SoftDeletes` to `TestimonialTranslation` and adjust the cascade strategy.

---

## Changelog

| Date | Change |
|------|--------|
| 2026-05-05 | Authorization (`$this->authorize()`) added to all 5 admin controller methods; `Language::all()` moved from constructor to lazy `loadLanguages()` method; `$th->getMessage()` replaced with `Log::error()` + generic user message in both `store()` and `update()`; duplicate raw `$request->input('featured_image_id')` block in `update()` removed — single validated assignment only; front submission controller fixed to store image via `Media` model (`image_id` FK) instead of non-existent `image` column; orphaned file cleanup added on transaction failure; `@can('edit')` corrected to `@can('update', $testimonial)` in index view; `@can('delete')` updated to pass model instance; `SoftDeletes` added to `Testimonial` model; migration added to add `deleted_at` to `feedbacks` table; POST `/testimonials/submit` throttled at 5 req/min. |
