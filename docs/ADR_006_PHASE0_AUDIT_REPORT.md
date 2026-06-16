# ADR-006 Phase 0 Audit Report — Feedbacks vs Testimonials Naming

> **Audit Type:** Read-only pre-migration scan — zero code changes, zero schema changes.  
> **Scope:** Full codebase search for `feedbacks`, `feedback_translations`, `feedback_id`, `feedback`  
> **Audited on:** 2026-06-16  
> **Based on:** ADR-006, ADR_IMPLEMENTATION_PLAN.md (post Phase-0-ready update), ADR_EXECUTION_READINESS_REVIEW.md  
> **Constraint:** No migration created. No code modified. No ADR proposed.

---

## Executive Summary

| Search Term           | Real Matches | False Positives | Total Raw Matches |
|-----------------------|:------------:|:---------------:|:-----------------:|
| `feedbacks`           | 6            | 0               | 6                 |
| `feedback_translations` | 5          | 0               | 5                 |
| `feedback_id`         | 8            | 0               | 8                 |
| `feedback`            | 21           | 22              | 43                |
| **Total**             | **40**       | **22**          | **62**            |

**False positives explained:** The word `feedback` appears 22 times as a Bootstrap CSS class
(`invalid-feedback`), an HTML data attribute (`data-section-editor-feedback`), a JavaScript
UI-element variable (not DB-related), and natural-language strings in `t()` calls and
config keyword lists. None of these require any change.

---

## False Positives Identified (excluded from Required Changes)

| File | Lines | Reason |
|------|-------|--------|
| `resources/views/components/form/textarea.blade.php` | 23 | `invalid-feedback` — Bootstrap CSS class |
| `resources/views/dashboard/management/plans/edit.blade.php` | 109, 373 | `invalid-feedback` — Bootstrap CSS class |
| `resources/views/dashboard/pages/partials/form.blade.php` | 201, 225, 244, 338, 358, 379, 501 | `invalid-feedback` — Bootstrap CSS class |
| `resources/views/dashboard/pages/sections/index.blade.php` | 65 | `'customer feedback'` — natural language string inside `t()` |
| `resources/views/dashboard/pages/sections/index.blade.php` | 865–913 | JS local variable `const feedback` tracking a DOM element — no DB relation |
| `resources/views/dashboard/pages/sections/partials/dynamic-editor-form.blade.php` | 59, 61 | `data-section-editor-feedback` — HTML data attribute for save-state UI |
| `resources/views/dashboard/pages/sections/partials/shell-editor-form.blade.php` | 81–84 | Same as above |
| `resources/views/dashboard/testimonials/index.blade.php` | 214 | `'showcase client feedback'` — natural language in `t()` fallback string |
| `config/sections.php` | 23 | `'keywords' => '... feedback'` — icon picker keyword string |

---

## Categorized Findings

---

### Database Schema

Three migration files reference old names. These migrations have already run in production
and must **not** be edited. The ADR-006 migration creates a new `RENAME TABLE` migration.
They are listed here for completeness.

| File | Line | Current | Required Change |
|------|------|---------|-----------------|
| `database/migrations/2025_06_25_111513_create_feedbacks_table.php` | 11 | `Schema::create('feedbacks', ...)` | Historical — do not edit. New migration renames at runtime. |
| `database/migrations/2025_06_25_111513_create_feedbacks_table.php` | 32 | `Schema::dropIfExists('feedbacks')` | Historical — do not edit. |
| `database/migrations/2025_06_25_111554_create_feedback_translations_table.php` | 14 | `Schema::create('feedback_translations', ...)` | Historical — do not edit. |
| `database/migrations/2025_06_25_111554_create_feedback_translations_table.php` | 16 | `$table->string('feedback')` | Historical — column rename handled by new migration. |
| `database/migrations/2025_06_25_111554_create_feedback_translations_table.php` | 20 | `foreignId('feedback_id')->constrained('feedbacks')` | Historical — FK rename handled by new migration. |
| `database/migrations/2025_06_25_111554_create_feedback_translations_table.php` | 30 | `Schema::dropIfExists('feedback_translations')` | Historical — do not edit. |
| `database/migrations/2026_05_05_000003_add_soft_deletes_to_feedbacks_table.php` | 11, 19 | `Schema::table('feedbacks', ...)` | Historical — do not edit. |

**Migration verdict:** 3 migration files reference old names. All are historical records of the
original schema. The ADR-006 execution migration must be a new `RENAME TABLE` + `RENAME COLUMN`
migration — it does not modify existing migration files.

---

### Models

| File | Line | Current | Required Change | Risk |
|------|------|---------|-----------------|------|
| `app/Models/Testimonial.php` | 12 | `protected $table = 'feedbacks';` | `'testimonials'` | **High** — affects all DB queries via this model |
| `app/Models/Testimonial.php` | 32 | `$this->hasMany(TestimonialTranslation::class, 'feedback_id', 'id')` | second param: `'testimonial_id'` | **High** — FK mismatch after migration |
| `app/Models/TestimonialTranslation.php` | 9 | `protected $fillable = ['feedback_id', 'locale', 'feedback', 'name', 'major']` | `['testimonial_id', 'locale', 'text', 'name', 'major']` | **High** — mass-assignment writes will fail silently after column rename |
| `app/Models/TestimonialTranslation.php` | 10 | `protected $table = 'feedback_translations';` | `'testimonial_translations'` | **High** — affects all DB queries via this model |

**Execution note:** Models must be updated **before** the migration runs in a single atomic
deploy, or immediately after in the same deploy step. Updating models before migration creates
a window where the app crashes; updating after creates a window with wrong column names.
Safest approach: maintenance mode → migration → model update → clear cache → exit maintenance.

---

### Controllers

#### `app/Http/Controllers/Admin/TestimonialsController.php`

| Line | Current | Required Change | Risk |
|------|---------|-----------------|------|
| 121 | `'feedback_id' => $testimonial->id` | `'testimonial_id'` | **High** — DB write FK |
| 123 | `'feedback' => $translation['feedback']` | `'text' => $translation['feedback']` | **High** — DB write column |
| 165 | `'feedback' => $trans?->feedback ?? ''` | `'feedback' => $trans?->text ?? ''` | **High** — DB read (column name), key stays `feedback` to match form field name |
| 220 | `'feedback_id' => $testimonial->id` | `'testimonial_id'` | **High** — DB write FK |
| 224 | `'feedback' => $translation['feedback']` | `'text' => $translation['feedback']` | **High** — DB write column |
| 303 | `'testimonialTranslations.*.feedback' => 'nullable|string'` | **Keep as-is** — validation key matches form field name | None |
| 322 | `$feedback = trim($translation['feedback'] ?? '')` | **Keep as-is** — reads from form POST array | None |
| 326, 328, 337, 382 | `$feedback !== ''`, `$feedback === ''` etc. | **Keep as-is** — local variable referencing form value | None |
| 339 | `"testimonialTranslations.$code.feedback"` | **Keep as-is** — error key matches form field name | None |
| 370 | Comment: `feedback + name + major` | Optional comment update for clarity | Low |
| 378 | `$feedback = trim($translation['feedback'] ?? '')` | **Keep as-is** — form input array | None |
| 385 | `'feedback' => $feedback` | `'text' => $feedback` | **High** — DB write column (local `$feedback` variable is from form, but key is DB column name) |

**Design decision documented:** Form field names `testimonialTranslations[en][feedback]` are
kept as `feedback` in HTML (Option A). Only Eloquent `create()`/`updateOrCreate()` array
**keys** that map to DB column names are renamed to `text`. Local PHP variables and POST
input array keys are unchanged. This is consistent with the implementation plan note.

#### `app/Http/Controllers/Front/TestimonialSubmissionController.php`

| Line | Current | Required Change | Risk |
|------|---------|-----------------|------|
| 38 | `'feedback' => 'required|string'` | **Keep as-is** — validation rule for public POST field `name="feedback"` | None |
| 50 | Comment: `not a column on feedbacks` | Optional: update comment to `testimonials` | Low |
| 80 | `'feedback_id' => $testimonial->id` | `'testimonial_id'` | **High** — DB write FK |
| 82 | `'feedback' => $validated['feedback']` | `'text' => $validated['feedback']` | **High** — DB write column; POST field name stays `feedback` |

---

### Services

No service files found referencing any of the four search terms.

---

### Livewire — Usage Investigation and Architecture Decision

**Usage check results:**

A search for all invocation patterns across the full codebase found:

| Search Pattern | Location | Finding |
|---|---|---|
| `Livewire\Admin\Testimonials` | `app/Livewire/Admin/Testimonials.php:180` | Self-reference only |
| `<livewire:admin.testimonials />` | `resources/views/dashboard/testimonials.blade.php:3` | **Only one invocation** |
| `livewire.admin.testimonials` | `app/Livewire/Admin/Testimonials.php:180` | Component's own `render()` |
| Any route returning `dashboard.testimonials` | `routes/dashboard.php` | **No route found** |

**Key finding:** `resources/views/dashboard/testimonials.blade.php` is the only file that
mounts the Livewire component. That file is already marked deprecated in its own header:
```
{{-- deprecated - do not use. Legacy admin Livewire mount retained only for fallback safety. --}}
```

The active admin route (`Route::resource('testimonials', TestimonialsController::class)`)
routes to `TestimonialsController` which returns views from the `dashboard/testimonials/`
**directory** (`index.blade.php`, `create.blade.php`, `edit.blade.php`). The flat file
`dashboard/testimonials.blade.php` is not returned by any controller method.

**Conclusion: The Livewire component is not actively used. No route can reach it.**

**Architecture Decision:** Livewire testimonial management is not part of the active
architecture. It must not be migrated as an active path.

**Decision: Option A — Delete** (component not reachable, already deprecated).

The following three files are designated for deletion as a pre-migration step:

```
app/Livewire/Admin/Testimonials.php
resources/views/livewire/admin/testimonials.blade.php
resources/views/dashboard/testimonials.blade.php
```

These files are **excluded from the ADR-006 Required Changes list**. They require no
migration update — only deletion.

---

### Blade Views

#### Dashboard — Admin Testimonials Form

`resources/views/dashboard/testimonials/_form.blade.php`

| Line | Current | Required Change | Risk |
|------|---------|-----------------|------|
| 114 | `$errors->has("testimonialTranslations.$code.feedback")` | **Keep as-is** — error key matches form field name | None |
| 223 | `for="feedback_{{ $lang->code }}"` | **Keep as-is** — HTML `for` attribute (UI only) | None |
| 226 | `id="feedback_{{ $lang->code }}"` | **Keep as-is** — HTML id (UI only) | None |
| 227 | `name="testimonialTranslations[{{ $lang->code }}][feedback]"` | **Keep as-is** — form POST field name (Option A) | None |
| 229 | `@error('testimonialTranslations.' . $lang->code . '.feedback')` | **Keep as-is** — error key matches form field name | None |
| 230 | `{{ old('testimonialTranslations.' . $lang->code . '.feedback', $translation['feedback'] ?? '') }}` | `$translation['feedback'] ?? ''` **keeps as-is** because controller line 165 also keeps the array key as `feedback`. See coupling note below. | **Medium — coupling dependency** |
| 231 | `@error('testimonialTranslations.' . $lang->code . '.feedback')` | **Keep as-is** | None |

> **⚠ Coupling note (line 230):** The `$translation['feedback'] ?? ''` on line 230 depends
> directly on the array key used in `TestimonialsController.php:165`. The audit confirmed
> line 165 will become `'feedback' => $trans?->text ?? ''` — keeping the array key `feedback`
> but reading the DB column `text`. This means line 230 requires **no change**. However, if
> line 165 is ever changed to `'text' => $trans?->text ?? ''`, line 230 would silently show
> empty values in the edit form. This coupling is not documented in the implementation plan.
> **Recommendation:** Add a comment in both files noting this dependency.

#### Frontend — Public Section Components (Silent Failure Zone)

| File | Line | Current | Required Change | Risk |
|------|------|---------|-----------------|------|
| `resources/views/components/template/sections/reviews_showcase.blade.php` | 40 | `'text' => $translation->feedback ?? ''` | `$translation->text ?? ''` | **High — Silent Failure** |
| `resources/views/components/template/sections/testimonials.blade.php` | 44 | `{{ $translation?->feedback ?? '' }}` | `$translation?->text ?? ''` | **High — Silent Failure** |

After the `feedback` column is renamed to `text`, both of these produce **empty strings**
with no error, no exception, and no log entry. Public-facing testimonials silently disappear.

#### Livewire Admin Blade

| File | Line | Current | Required Change | Risk |
|------|------|---------|-----------------|------|
| `resources/views/livewire/admin/testimonials.blade.php` | 137 | `$testimonial->translation()?->feedback` | `->text` | **High** — displays empty in admin list |
| `resources/views/livewire/admin/testimonials.blade.php` | 234 | `wire:model="testimonialTranslations.{{ $index }}.feedback"` | **Keep as-is** — matches component state key `feedback` | None |

#### Frontend — Public Submission Form

| File | Line | Current | Required Change | Risk |
|------|------|---------|-----------------|------|
| `resources/views/front/testimonials/submit.blade.php` | 115 | `t('frontend.testimonials.form.feedback', 'نص التقييم')` | **Keep as-is** — translation key, not DB column | None |
| `resources/views/front/testimonials/submit.blade.php` | 117 | `name="feedback"` | **Keep as-is** — POST field name; controller remaps on write | None |
| `resources/views/front/testimonials/submit.blade.php` | 118 | `{{ old('feedback') }}` | **Keep as-is** — POST field name | None |
| `resources/views/front/testimonials/submit.blade.php` | 119 | `@error('feedback')` | **Keep as-is** — validation error for POST field | None |

---

### Support Classes

| File | Line | Current | Required Change | Risk |
|------|------|---------|-----------------|------|
| `app/Support/Sections/SectionQueryResolver.php` | 388 | `$translation?->feedback ?? ''` | `$translation?->text ?? ''` | **High — Silent Failure** |

---

### Seeders

No seeders reference `feedbacks`, `feedback_translations`, `feedback_id`, or the `feedback`
column by name. Zero matches found in `database/seeders/`.

---

### Tests

No test files found in `tests/`. Zero matches.

---

### JavaScript

No matches found in `resources/js/`. No compiled asset files in `public/assets/` contained
any of the search terms outside of minified bundles (which are build artifacts, not source).
Zero matches requiring a change.

---

## Required Changes — Consolidated

### Step 0 — Pre-Migration Deletion (before running `php artisan migrate`)

| File | Action | Reason |
|------|--------|--------|
| `app/Livewire/Admin/Testimonials.php` | **Delete** | No active route; already deprecated; not migrated |
| `resources/views/livewire/admin/testimonials.blade.php` | **Delete** | Companion view for deleted component |
| `resources/views/dashboard/testimonials.blade.php` | **Delete** | The only Livewire mount page; marked deprecated; no route serves it |

### Step 1 — Code Changes (same commit as migration)

**Changes that MUST happen in the same deployment as the migration:**

| # | File | Line(s) | Current | Required Change | Risk |
|---|------|---------|---------|-----------------|------|
| 1 | `app/Models/Testimonial.php` | 12 | `protected $table = 'feedbacks'` | `'testimonials'` | High |
| 2 | `app/Models/Testimonial.php` | 32 | FK param `'feedback_id'` | `'testimonial_id'` | High |
| 3 | `app/Models/TestimonialTranslation.php` | 9 | `$fillable` — `'feedback_id'`, `'feedback'` | `'testimonial_id'`, `'text'` | High |
| 4 | `app/Models/TestimonialTranslation.php` | 10 | `protected $table = 'feedback_translations'` | `'testimonial_translations'` | High |
| 5 | `app/Http/Controllers/Admin/TestimonialsController.php` | 121, 220 | `'feedback_id' =>` | `'testimonial_id' =>` | High |
| 6 | `app/Http/Controllers/Admin/TestimonialsController.php` | 123, 224, 385 | `'feedback' =>` (DB write) | `'text' =>` | High |
| 7 | `app/Http/Controllers/Admin/TestimonialsController.php` | 165 | `$trans?->feedback` | `$trans?->text` | High |
| 8 | `app/Http/Controllers/Front/TestimonialSubmissionController.php` | 80 | `'feedback_id' =>` | `'testimonial_id' =>` | High |
| 9 | `app/Http/Controllers/Front/TestimonialSubmissionController.php` | 82 | `'feedback' => $validated['feedback']` | `'text' => $validated['feedback']` | High |
| 10 | `app/Support/Sections/SectionQueryResolver.php` | 388 | `$translation?->feedback` | `$translation?->text` | **High — Silent Failure** |
| 11 | `resources/views/components/template/sections/reviews_showcase.blade.php` | 40 | `$translation->feedback` | `$translation->text` | **High — Silent Failure** |
| 12 | `resources/views/components/template/sections/testimonials.blade.php` | 44 | `$translation?->feedback` | `$translation?->text` | **High — Silent Failure** |

> **SectionQueryResolver.php:388 — blocker resolved:** This file was missing from the
> original ADR-006 plan scope. It is now included as Required Change #10. Failure to
> update it causes silent empty testimonial text in any page built with the sections
> builder — no exception, no 500, no log entry.

**Changes that are NOT required (keep as-is with justification):**

| File | Line(s) | Why Keep |
|------|---------|----------|
| `app/Http/Controllers/Front/TestimonialSubmissionController.php` | 38 | POST field validation key — not a DB column reference |
| `app/Http/Controllers/Admin/TestimonialsController.php` | 303, 339 | Validation keys tied to form POST field name |
| `app/Http/Controllers/Admin/TestimonialsController.php` | 322, 326, 328, 337, 378, 382 | Local `$feedback` variable reading from form POST input, not DB |
| `app/Livewire/Admin/Testimonials.php` | 53 | Component state key matching `wire:model` |
| `app/Livewire/Admin/Testimonials.php` | 126 | Validation key matching `wire:model` |
| `resources/views/livewire/admin/testimonials.blade.php` | 234 | `wire:model="...feedback"` matches component state key |
| `resources/views/dashboard/testimonials/_form.blade.php` | 114, 223, 226, 227, 229, 230, 231 | Form field names + error keys — all valid under Option A |
| `resources/views/front/testimonials/submit.blade.php` | 115–119 | Public POST field name + translation key — independent from DB column |
| All `invalid-feedback` instances | Various | Bootstrap CSS class — unrelated |

---

## Unexpected References

References found during this audit that were **not listed** in `ADR_IMPLEMENTATION_PLAN.md`:

### 1. `app/Support/Sections/SectionQueryResolver.php:388` — CRITICAL

```php
$text = trim((string) ($translation?->feedback ?? ''));
```

This PHP support class reads the `feedback` column directly from the translation model.
It was present in the implementation plan's blast radius table (added in the readiness review
update as "SectionQueryResolver.php:371") but was documented only as a blast radius item
for ADR-005, **not** as an ADR-006 required change. The actual line confirmed in this audit
is **388** (not 371 as listed in the plan).

**Impact:** After renaming `feedback_translations.feedback` to `text`, this line silently
returns empty string for any section that renders testimonial text via the section resolver.
This is the same silent-failure class as `reviews_showcase.blade.php` and `testimonials.blade.php`.

**Action required:** Add line 388 of `SectionQueryResolver.php` to ADR-006's Required Changes list.

### 2. `resources/views/dashboard/testimonials/_form.blade.php:230` — Coupling Dependency

```blade
{{ old('testimonialTranslations.' . $lang->code . '.feedback', $translation['feedback'] ?? '') }}
```

The `$translation['feedback']` fallback value depends on the array key used by
`TestimonialsController.php:165`. If line 165 is changed to use key `'text'` instead of
`'feedback'` when populating the view data, this Blade line silently shows empty pre-filled
values in the testimonial edit form. The coupling is implicit and undocumented.

**Action required:** The implementation plan does not list this dependency. It is safe to
leave as-is because the design decision (Option A: keep form field names as `feedback`)
ensures line 165 preserves the `feedback` key. However, a comment should be added to both
files noting the dependency to prevent future breakage.

### 3. `app/Http/Controllers/Front/TestimonialSubmissionController.php:50` — Minor

```php
// Previously the code wrote 'image' => path which is not a column on feedbacks.
```

A code comment references the old `feedbacks` table name. No functional impact — cosmetic
only. Not in the plan. Safe to update or leave.

---

## Blocking Findings

### Blocking — Silent Failure Risk

The following are **production-silent failures**: no 500, no exception, no log entry.
The only symptom is testimonial text rendering as empty string on the live site.

| # | File | Line | Failure Mode |
|---|------|------|--------------|
| 1 | `resources/views/components/template/sections/reviews_showcase.blade.php` | 40 | Testimonial text empty on all sites using this section |
| 2 | `resources/views/components/template/sections/testimonials.blade.php` | 44 | Testimonial text empty on all sites using this section |
| 3 | `app/Support/Sections/SectionQueryResolver.php` | 388 | Testimonial text empty in any page built with the sections builder |
| 4 | `resources/views/livewire/admin/testimonials.blade.php` | 137 | Admin testimonial list shows blank text column |

**All four must be updated in the same deployment as the migration. There is no safe
window to have the migration done and these files not yet updated.**

### Blocking — Data Write Failure Risk

The following cause DB write failures (FK constraint violation or wrong column name error)
if the migration runs but models/controllers are not updated:

| # | File | Lines | Failure Mode |
|---|------|-------|--------------|
| 5 | `app/Models/Testimonial.php` | 12 | All queries fail — table name mismatch |
| 6 | `app/Models/TestimonialTranslation.php` | 10 | All queries fail — table name mismatch |
| 7 | `app/Models/Testimonial.php` | 32 | FK relationship fails — wrong FK column |
| 8 | `app/Http/Controllers/Admin/TestimonialsController.php` | 121, 220 | FK constraint violation on insert |
| 9 | `app/Http/Controllers/Front/TestimonialSubmissionController.php` | 80 | FK constraint violation on insert |
| 10 | `app/Livewire/Admin/Testimonials.php` | 145 | FK constraint violation on updateOrCreate |

### No Data Loss Risk Found

No raw SQL queries (`DB::statement`, `DB::table()`, `PDO::exec()`) were found referencing
`feedbacks` or `feedback_translations`. All DB access is through Eloquent models. Once
the models are updated, all queries route through the correct table and column names.
`RENAME TABLE` in MySQL is atomic and preserves all existing rows, indexes, and constraints.

---

## Final Verdict

```
READY FOR MIGRATION
```

**All blockers resolved:**

1. ✅ `SectionQueryResolver.php:388` — added to Required Changes (Step 1, item #10).
2. ✅ Livewire component confirmed **not actively used** — designated for deletion (Step 0),
   not migration. The only invocation is in a deprecated, route-orphaned view.
3. ✅ All 12 required code changes are now fully scoped and assigned to specific files and lines.
4. ✅ The `_form.blade.php` coupling dependency is documented; no change required under Option A.

**Pre-conditions before running `php artisan migrate`:**
- Delete (or archive) the 3 Livewire files listed in Step 0.
- Deploy all 12 code changes from Step 1 in the same release.
- Enter maintenance mode first (`php artisan down`).

**No unexpected blockers remain.**

---

## Recommended Execution Order (Migration Day)

```
1. Enable maintenance mode
2. Run: php artisan down
3. Deploy code changes (all 16 Required Changes above)
4. Run migration: php artisan migrate   (RENAME TABLE feedbacks→testimonials, feedback_translations→testimonial_translations, RENAME COLUMN feedback_id→testimonial_id, feedback→text)
5. php artisan config:clear && php artisan cache:clear && php artisan view:clear
6. Smoke test: load a page using reviews_showcase section, verify testimonial text appears
7. Smoke test: open admin testimonial list, verify text column populated
8. Smoke test: submit a testimonial via public form, verify it saves
9. php artisan up
```

---

## Summary Counts

| Metric | Count |
|--------|-------|
| Total raw grep matches | 62 |
| False positives (Bootstrap CSS, UI vars, natural language) | 22 |
| Real references requiring a decision | 40 |
| Required code changes (Step 1 — update) | 12 |
| Pre-migration deletions (Step 0 — Livewire files) | 3 files |
| Keep-as-is (confirmed safe under Option A) | 15 |
| Unexpected references not in original plan | 3 |
| High-risk silent-failure lines | 3 (reviews_showcase, testimonials section, SectionQueryResolver) |
| High-risk data-write-failure lines | 5 |
| Remaining migration-blocking items | **0** |
| Files with zero matches (seeders, tests, JS) | 3 categories |
| Can migration run safely after Step 0 + Step 1? | **Yes — READY FOR MIGRATION** |
| Files to delete before migration | 3 (Livewire component + view + deprecated mount page) |
| Files to update in same commit as migration | 8 files |
