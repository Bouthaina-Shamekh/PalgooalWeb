# ADR-006 Implementation Report

> **Date:** 2026-06-16  
> **Status:** ✅ Complete  
> **ADR:** [006-feedbacks-vs-testimonials-naming-strategy.md](adr/006-feedbacks-vs-testimonials-naming-strategy.md)

---

## Summary

ADR-006 renamed all `feedbacks`/`feedback_translations` DB tables and related columns to align with the PHP model names (`Testimonial` / `TestimonialTranslation`). The implementation was completed in 8 steps across a single session.

---

## What Changed

### Database (via migration)

| Before | After |
|--------|-------|
| Table `feedbacks` | Table `testimonials` |
| Table `feedback_translations` | Table `testimonial_translations` |
| Column `feedback_translations.feedback_id` | Column `testimonial_translations.testimonial_id` |
| Column `feedback_translations.feedback` | Column `testimonial_translations.text` |
| FK `feedback_id → feedbacks.id` | FK `testimonial_id → testimonials.id` |

Migration file: `database/migrations/2026_06_16_112851_rename_feedbacks_to_testimonials.php`  
The migration is idempotent — guarded with `Schema::hasTable()` / `Schema::hasColumn()` checks on every step.

---

## Files Modified

### Step 0 — Delete Legacy Livewire Orphans

| File | Action |
|------|--------|
| `app/Livewire/Admin/Testimonials.php` | **Deleted** — component not served by any active route |
| `resources/views/livewire/admin/testimonials.blade.php` | **Deleted** — Livewire view companion |
| `resources/views/dashboard/testimonials.blade.php` | **Deleted** — flat mount view marked `{{-- deprecated --}}` |

The active route `Route::resource('testimonials', TestimonialsController::class)` serves views from `resources/views/dashboard/testimonials/` (directory), not the flat file. Livewire component had no active invocation path.

---

### Step 1 — Migration

`database/migrations/2026_06_16_112851_rename_feedbacks_to_testimonials.php` — **Created**

**`up()` sequence:**
1. Drop FK `feedback_id → feedbacks.id`
2. Rename `feedback_translations` → `testimonial_translations`
3. Rename `feedbacks` → `testimonials`
4. Rename column `feedback_id` → `testimonial_id`
5. Rename column `feedback` → `text`
6. Recreate FK `testimonial_id → testimonials.id` ON DELETE CASCADE

**`down()` sequence:** Full reversal in reverse order.

---

### Step 2 — Models

**`app/Models/Testimonial.php`**
- Removed `protected $table = 'feedbacks';` — Laravel now discovers `testimonials` automatically
- Changed `hasMany(TestimonialTranslation::class, 'feedback_id')` → `'testimonial_id'`

**`app/Models/TestimonialTranslation.php`**
- Removed `protected $table = 'feedback_translations';`
- Updated `$fillable`: `feedback_id` → `testimonial_id`, `feedback` → `text`

---

### Step 3 — Controllers

**`app/Http/Controllers/Admin/TestimonialsController.php`**

| Method | Change |
|--------|--------|
| `store()` | `'feedback_id'` → `'testimonial_id'`; `'feedback'` → `'text'` in translation create |
| `edit()` | `$trans?->feedback` → `$trans?->text`; array key `'feedback'` → `'text'` |
| `update()` | `updateOrCreate` search key + values updated to new column names |
| `validateTestimonialRequest()` | Validation rule key `.feedback` → `.text`; local `$feedback` → `$text` throughout |
| `extractCompleteTranslations()` | Returned array key `'feedback'` → `'text'`; docblock updated |

**`app/Http/Controllers/Front/TestimonialSubmissionController.php`**
- `'feedback_id'` → `'testimonial_id'` in translation create
- `'feedback' => $validated['feedback']` → `'text' => $validated['feedback']` with explanatory comment
- Validation rule `'feedback' => 'required|string'` **kept unchanged** — `feedback` is the public form POST field name; the mapping to DB column `text` happens only at write time

---

### Step 4 — SectionQueryResolver

**`app/Support/Sections/SectionQueryResolver.php` (line 388)**

```php
// Before — SILENT FAILURE: returned empty string after rename
$text = trim((string) ($translation?->feedback ?? ''));

// After
$text = trim((string) ($translation?->text ?? ''));
```

This was the highest-priority fix — the old column reference would have caused public testimonial sections to silently render blank text with no 500 error.

---

### Step 5 — Blade Views

**`resources/views/components/template/sections/reviews_showcase.blade.php`**
- `$translation->feedback ?? ''` → `$translation->text ?? ''`

**`resources/views/components/template/sections/testimonials.blade.php`**
- `{{ $translation?->feedback ?? '' }}` → `{{ $translation?->text ?? '' }}`

**`resources/views/dashboard/testimonials/_form.blade.php`**
- Error key `.feedback` → `.text`
- `for="feedback_{{ $lang->code }}"` → `for="text_{{ $lang->code }}"`
- `id="feedback_{{ $lang->code }}"` → `id="text_{{ $lang->code }}"`
- `name="testimonialTranslations[...][feedback]"` → `[text]`
- `@error('...feedback')` → `@error('...text')` (both instances)
- `old('...feedback', ...)` → `old('...text', ...)`

---

### Step 6 — Documentation

| File | Changes |
|------|---------|
| `docs/adr/006-feedbacks-vs-testimonials-naming-strategy.md` | Status: `Proposed` → `Accepted — Implemented 2026-06-16` |
| `docs/03-database-architecture.md` | 5 edits: section header, table catalog, soft-deletes table, legacy table note, migration timeline |
| `docs/29-content-showcase.md` | 14 edits: domain overview, mermaid diagram, content types table, full schema blocks, "What is Feedback?" section, public submission code, translation support section, search section, TD-1/TD-2 marked resolved, TD-5/TD-6 table name references, Future Improvements |
| `docs/CHANGELOG.md` | ADR-006 status updated; implementation entry added; Documentation Maturity counts updated (Accepted ADRs: 2→3, Proposed: 3→2); TD-7 marked resolved |

---

## Validation Grep Results

Command run:
```bash
grep -Rn "feedbacks|feedback_translations|feedback_id\b|->feedback\b|\['feedback'\]|\"feedback\"\s*=>" \
  app/ resources/ database/ --include="*.php" --include="*.blade.php"
```

**All remaining matches are allowed exceptions:**

| File | Type | Reason |
|------|------|--------|
| `database/migrations/2025_06_25_111513_create_feedbacks_table.php` | Historical migration | Original table creation — immutable record |
| `database/migrations/2025_06_25_111554_create_feedback_translations_table.php` | Historical migration | Original translation table creation — immutable record |
| `database/migrations/2026_05_05_000003_add_soft_deletes_to_feedbacks_table.php` | Historical migration | SoftDeletes migration — immutable record |
| `database/migrations/2026_06_16_112851_rename_feedbacks_to_testimonials.php` | ADR-006 migration | References old names as part of rename logic — expected |
| `app/Models/Testimonial.php:12` | Comment | ADR-006 note: `// table renamed feedbacks → testimonials` |
| `app/Models/TestimonialTranslation.php:9` | Comment | ADR-006 note: `// table renamed feedback_translations → testimonial_translations` |
| `app/Http/Controllers/Front/TestimonialSubmissionController.php:82` | Comment | `// 'feedback' is the public form field name; maps to DB column 'text'` |

**Zero active code references to old naming remain.**

---

## Run Migration

The user must run the migration on their machine:

```bash
php artisan migrate
php artisan cache:clear
```

Expected output:
```
Migrating: 2026_06_16_112851_rename_feedbacks_to_testimonials
Migrated:  2026_06_16_112851_rename_feedbacks_to_testimonials (XX ms)
```

---

## Technical Debt Resolved

| ID | Description | Status |
|----|-------------|--------|
| TD-1 | `Testimonial` model `$table = 'feedbacks'` mismatch | ✅ Resolved |
| TD-2 | `feedback_translations.feedback` column misnamed | ✅ Resolved |

Remaining technical debt from this domain (still open):
- TD-5: Public testimonial image upload uses non-standard `testimonials/` path (vs `media/YYYY/MM/`)
- TD-6: Soft-delete does not cascade to `testimonial_translations`

---

## ADR-006 Decision Adherence

| Decision point | Implemented? |
|----------------|-------------|
| Pure rename — zero data transformation | ✅ Yes |
| Idempotent migration with schema guards | ✅ Yes |
| `$table` overrides removed from models | ✅ Yes |
| Public form field `feedback` kept unchanged | ✅ Yes — maps to `text` at controller write time |
| Livewire orphan files deleted (not migrated) | ✅ Yes — 3 files deleted |
| Bootstrap references treated as false positives | ✅ Yes — not touched |
| No ADR-005 or ADR-003 work | ✅ Yes — out of scope |
| No `__()` introduced | ✅ Yes — all `t()` |
| No `with('success')` introduced | ✅ Yes — all `with('ok')` |
