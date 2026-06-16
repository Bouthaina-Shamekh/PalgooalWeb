# ADR Implementation Roadmap

> **Scope:** Execution planning for ADR-003, ADR-005, and ADR-006.  
> **Purpose:** Convert approved ADRs into a sequenced, risk-ordered plan that can be
> executed immediately after the documentation phase closes.  
> **Ground truth:** All scope figures below are derived from a live code audit, not
> from the ADR text alone. One additional impacted file was found during audit —
> noted under Phase 1.

---

## Executive Summary

Three ADRs are approved and pending implementation. None has a hard technical
dependency on another, but they differ significantly in risk profile, data scope,
and reversibility. The recommended execution order — **ADR-006 → ADR-005 → ADR-003**
— sequences by ascending risk, not by technical necessity.

| ADR | Title | Risk | Complexity | Data Migration | Recommended Order |
|-----|-------|------|-----------|----------------|-------------------|
| ADR-006 | Feedbacks vs Testimonials Naming | **Low** | Low | Rename only — no data transformation | **1st** |
| ADR-005 | Media Storage Format Unification | **Medium** | Medium-High | Backfill with path→ID reverse lookup | **2nd** |
| ADR-003 | Integer Cents Money Storage | **High** | High | Dual write + billing cycle + reconciliation | **3rd** |

**Immediate action available:** ADR-006 can be executed without a Staging cycle.
Its migration is a pure metadata rename — the fastest MySQL operation possible.

**Staging required before production:** ADR-005 (backfill verification) and
ADR-003 (financial dual-write monitoring).

**Highest data risk:** ADR-003. A wrong backfill multiplier on billing data
(÷100 instead of ×100) produces financially incorrect values that may not be caught
until payment reconciliation.

### Current Readiness Status

> Last reviewed: 2026-06-16 — post-execution code audit.

| ADR | Readiness |
|-----|-----------|
| ADR-006 | ✅ **COMPLETE** — Implemented 2026-06-16. All tables renamed, models/controllers/views updated, documentation closed. |
| ADR-005 | **READY FOR WAVE 1 (partial)** — Phase 0 audit complete (2026-06-16); Phase 0.5 Wave 1 Backfill Audit complete (2026-06-16). `clients.avatar`, `portfolios.default_image`, and `general_settings ×7` can start now. **`services.icon` is BLOCKED** — 4/5 stored values are static template asset paths (`/assets/tamplate/images/icons/*.svg`) incompatible with Pattern A; excluded from Wave 1 pending architectural decision. See `docs/ADR_005_PHASE05_WAVE1_BACKFILL_AUDIT.md`. Wave 2 blocked until templates.image backfill is resolved. Wave 3 blocked until JSON storage architecture is decided. |
| ADR-003 | **NOT READY UNTIL MONEY WRITE PATHS ARE FULLY SCOPED** — 3 undocumented subscription write paths found; CouponController does not exist in codebase |

---

## Phase 1 — ADR-006: Feedbacks vs Testimonials Naming Strategy

### Why First?

ADR-006 is the only ADR with zero data transformation risk. `RENAME TABLE` in MySQL
is a metadata-only operation — it does not move or copy rows. The FK constraint requires
a brief drop/recreate, but no row is read or written during the migration. This makes
ADR-006 the safest possible starting point.

Executing ADR-006 first also delivers a clean, consistent DB schema before the
more complex migrations begin. Developers working on ADR-005 and ADR-003 will not
need to mentally translate between "testimonial" (PHP) and "feedbacks" (DB) at any
point during those migrations.

Additionally, the testimonials system (already Pattern A for `image_id`) is a
reference implementation used throughout ADR-005's documentation. Confirming its
correctness and consistency before ADR-005 begins removes one ambiguity from
the media migration scope.

### Affected Tables

| Table | Change |
|-------|--------|
| `feedbacks` | Rename → `testimonials` |
| `feedback_translations` | Rename → `testimonial_translations` |
| `feedback_translations.feedback_id` | Rename column → `testimonial_id` |
| `feedback_translations.feedback` | Rename column → `text` |
| FK on `testimonial_translations.testimonial_id` | Drop then recreate with new name |

**Execution order within the migration:** Drop FK → rename tables → rename columns →
recreate FK. The table rename must precede the FK recreate because the new FK
references `testimonials.id`.

### Affected Models

| File | Change |
|------|--------|
| `app/Models/Testimonial.php` | Remove `protected $table = 'feedbacks'` — convention now matches |
| `app/Models/TestimonialTranslation.php` | Remove `protected $table = 'feedback_translations'` |
| `TestimonialTranslation::$fillable` | `'feedback_id'` → `'testimonial_id'`, `'feedback'` → `'text'` |
| `Testimonial::translations()` | FK argument: `'feedback_id'` → `'testimonial_id'` |

### Affected Controllers and Services

Each controller has **two categories of changes**, not one:

1. **FK column rename:** `feedback_id` → `testimonial_id`
2. **Text column rename:** `'feedback' => $value` (DB write) and `$record->feedback` (DB read) → `'text' => $value` / `$record->text`

Both must be updated in the same commit. A migration that renames the column but leaves a
controller writing `'feedback' => ...` will produce a runtime SQL error on the next write.

| File | FK Change | Text Column Change |
|------|-----------|-------------------|
| `app/Http/Controllers/Admin/TestimonialsController.php` | `'feedback_id' => $testimonial->id` → `'testimonial_id' => ...` | `'feedback' => $value` → `'text' => $value` at lines 123, 224, 385; `$trans?->feedback` → `$trans?->text` at line 165 |
| `app/Http/Controllers/Front/TestimonialSubmissionController.php` | `'feedback_id' => $testimonial->id` → `'testimonial_id' => ...` | `'feedback' => $validated['feedback']` → `'text' => $validated['feedback']` at line 82 |
| `app/Support/Sections/SectionQueryResolver.php` | — | `$translation?->feedback` → `$translation?->text` at line 388 |

**Note on form field names:** The POST parameter `name="feedback"` in Blade forms and the
request array keys like `$translation['feedback']` do **not** need to change — they are
arbitrary input names, not DB column references. Only the keys used in Eloquent
`create()`/`updateOrCreate()` calls must change to `'text'`.

**Architecture Decision — Livewire Testimonial Management is excluded from ADR-006 migration scope:**

A Phase 0 codebase audit confirmed that `app/Livewire/Admin/Testimonials.php` and its
companion Blade view are **not served by any active route**. The sole active admin route is:

```
Route::resource('testimonials', TestimonialsController::class)
```

This routes to `TestimonialsController`, which returns views from `dashboard/testimonials/`
(the directory). The Livewire mount page (`resources/views/dashboard/testimonials.blade.php`)
is already marked deprecated in its own file header (`{{-- deprecated - do not use --}}`)
and is not referenced by any route that can be reached via normal navigation.

**Decision:** Livewire testimonial management is not part of the active architecture.
It must not be migrated as an active path.

The following three files are designated for **deletion** as a pre-migration cleanup step
(Step 0 below), before the ADR-006 migration runs:

```
app/Livewire/Admin/Testimonials.php
resources/views/livewire/admin/testimonials.blade.php
resources/views/dashboard/testimonials.blade.php
```

If any concern arises about discarding these files, move them to `_archive/legacy-livewire/`
instead. However, deletion is the recommended approach since the Controller/Blade flow
is the canonical implementation and the Livewire flow was never completed.

### Affected Blade Views

**⚠ Silent failure risk — not a 500 error.** The following views read `->feedback` directly
from the `testimonial_translations` table. After the column is renamed to `text`, they will
return empty strings silently — testimonial text disappears from the public site with no
exception thrown and no log entry.

| File | Line | Code | Required Change |
|------|------|------|-----------------|
| `resources/views/components/template/sections/reviews_showcase.blade.php` | 40 | `'text' => $translation->feedback ?? ''` | → `$translation->text ?? ''` |
| `resources/views/components/template/sections/testimonials.blade.php` | 44 | `$translation?->feedback ?? ''` | → `$translation?->text ?? ''` |
| `resources/views/livewire/admin/testimonials.blade.php` | — | ~~`->feedback` and `wire:model="...feedback"`~~ | **DELETE FILE** — not an active path |

Both public-facing section components are used in client sites. They must be updated in
the same commit as the migration — **not** after.

**Note on `_form.blade.php` coupling:** `resources/views/dashboard/testimonials/_form.blade.php:230`
contains `$translation['feedback'] ?? ''` as a pre-fill fallback. This value is populated
by `TestimonialsController.php:165` which builds the array with key `'feedback'` (kept as-is
under Option A — form field names not renamed). No change required in `_form.blade.php`.
The coupling is documented here to prevent future breakage if line 165 is ever changed.

### Rollback Complexity

**Very Low.** A down migration that reverses the renames restores full functionality.
No data is at risk. MySQL `RENAME TABLE` is atomic — if the migration fails mid-run
(e.g., the FK recreate fails), the tables retain their new names but the FK is absent.
The down migration must explicitly handle this case.

If rollback is needed at the PHP layer (after models are updated but before migration),
temporarily restoring the `$table` overrides in both models is sufficient to make
the application work against the old table names.

### Estimated Effort

| Task | Effort |
|------|--------|
| Pre-migration: delete 3 Livewire files (or move to `_archive/`) | 10 minutes |
| Migration file | 1–2 hours |
| Model updates (2 files) | 30 minutes |
| Controller updates (TestimonialsController, TestimonialSubmissionController, SectionQueryResolver) | 1 hour |
| Blade view updates (2 views: reviews_showcase, testimonials) + `_form.blade.php` coupling comment | 20 minutes |
| Documentation updates (03-database-architecture.md, 29-content-showcase.md) | 1 hour |
| **Total** | **~4–5 hours** |

---

## Phase 0 (ADR-005) — Media Audit ✅ COMPLETE

> **Completed:** 2026-06-16 — Full report: `docs/ADR_005_PHASE0_MEDIA_AUDIT.md`

### Pattern Inventory Results

| Pattern | Description | Column Count | Notes |
|---------|-------------|-------------|-------|
| **Pattern A** — `media_id` FK | Integer FK → `media.id`; resolved at render time | **3** | testimonials.image_id, section_definitions.preview_media_id, section content JSON media fields |
| **Pattern B** — Raw path string | Path stored as `string` or nested inside `json` column | **14** | Across 5 tables + 2 JSON sub-keys |
| **Pattern C** — ID→Path at write | Media ID accepted at controller, path stored in DB | **5** controller sites | Covers all Pattern B writes |

**Ghost relation discovered:** `Section::image()` declares `belongsTo(Media::class, 'image_id')` in `app/Models/Section.php` but no migration has ever added `image_id` to the `sections` table. The relation silently returns `null`. It must be removed before any related migration work begins.

### Pattern B Column Inventory (full)

| Table | Column(s) | Write path |
|-------|-----------|-----------|
| `general_settings` | `logo`, `dark_logo`, `sticky_logo`, `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, `favicon` (×7) | `HomeController::normalizeMediaPath()` |
| `general_settings` | `header_variant_settings.*.logo_override` (JSON sub-key) | `AppearanceController::normalizeMediaPath()` |
| `general_settings` | `header_variant_settings.*.payment_logos` (JSON array sub-key) | `AppearanceController::normalizeMediaPathList()` |
| `portfolios` | `default_image` | `PortfolioController::resolveMediaIdsToPaths()` |
| `portfolios` | `images` (JSON array) | `PortfolioController::resolveMediaIdsToPaths()` |
| `templates` | `image` | `TemplateController` inline (two write paths) |
| `clients` | `avatar` | `ClientController` direct store (path from media picker) |
| `services` | `icon` | Direct store (file path, not CSS class) |

### Critical Findings

**Finding 1 — `templates.image` has two write paths with different media record behaviour:**
- Path A (direct file upload): file stored on disk, **NO `media` record created** → orphaned path
- Path B (media picker): `Media::whereKey()->value('file_path')` → media record exists

Rows written via Path A cannot be migrated to Pattern A without a data backfill first.
**Wave 2 is blocked until this is resolved.**

**Finding 2 — Two divergent `normalizeMediaPath()` implementations:**
- `HomeController` version: strips leading `/` only
- `AppearanceController` version: strips leading `/` AND strips `storage/` prefix

Path format inconsistency is possible across `general_settings` columns depending on
which controller last wrote the value. The backfill command must normalize both sides.

**Finding 3 — `portfolios.images` and `payment_logos` are JSON arrays:**
Migrating these to Pattern A requires either a pivot table (`portfolio_media`) or
storing IDs inside the JSON instead of paths. An architectural decision is required.
**Wave 3 is blocked until this decision is made.**

---

## Phase 2 — ADR-005: Media Storage Format Unification

### Why Second?

ADR-005 is structurally more complex than ADR-006 but carries no financial risk.
The migration targets image references in `portfolios`, `general_settings`, `templates`,
`clients`, and `services` — data that, if temporarily wrong, produces a broken image
display rather than a financial error. This is recoverable and visible immediately.

Placing ADR-005 before ADR-003 means the media system is unified and consistent
before financial data migration begins. This matters because `PortfolioController`
and `AppearanceController` currently contain conversion utilities
(`resolveMediaIdsToPaths()`, `normalizeMediaPath()`) that would otherwise coexist
with the billing migration period, creating unnecessary complexity in the codebase
during an already careful migration window.

**No technical dependency exists between ADR-005 and ADR-003.** They operate on
completely separate tables. ADR-003 can begin at any time after ADR-006 completes.
However, financial migrations must not run in the same deploy window as media schema
changes — the isolation rule below still applies (see Risk Isolation Principle 1).

### Pre-Migration Cleanup Required (Before Any Wave)

**Cleanup 1 — Remove ghost `Section::image()` relation**

`app/Models/Section.php` declares `belongsTo(Media::class, 'image_id')` but no
migration has ever created `image_id` on the `sections` table. The relation returns
`null` silently. Remove it before any media migration work begins to avoid developer
confusion during Wave 1.

**Cleanup 2 — Deduplicate `normalizeMediaPath()`**

`HomeController` (line 874) and `AppearanceController` (line 676) both contain a
private `normalizeMediaPath()` implementation with different behaviour:
- `HomeController` version: strips leading `/` only
- `AppearanceController` version: strips leading `/` AND strips `storage/` prefix

Extract to a shared `MediaPathNormalizer` service or trait before Phase 1 of any
wave, so that Wave 1 dual-write updates are made in one place, not two.

---

### Already Compliant — No Migration Needed

| Table | Column | Pattern | Notes |
|-------|--------|---------|-------|
| `testimonials` | `image_id` | Pattern A ✅ | FK to `media.id` |
| `section_definitions` | `preview_media_id` | Pattern A ✅ | FK to `media.id` |
| `sections.content` (JSON) | media-type fields | Pattern A ✅ | Integer IDs, resolved via `SectionFrontendMediaResolver` |

---

### Wave 1 — Simple FK Columns

> **Status:** READY TO START (after Cleanup 1 and Cleanup 2 above)

#### Scope

| Table | Column | Current type | Target |
|-------|--------|-------------|--------|
| `clients` | `avatar` (string, nullable) | Path from media picker | `avatar_media_id` FK | 2 orphaned rows (old direct-upload `images/clients/1.png`) → NULL on backfill |
| ~~`services`~~ | ~~`icon` (string, nullable)~~ | ~~File path~~ | ~~`icon_media_id` FK~~ | **EXCLUDED** — 4/5 values are `/assets/tamplate/images/icons/*.svg` (static theme assets, not media library items). Cannot be Pattern A. Mark as intentional Pattern B exception. |
| `general_settings` | `logo`, `dark_logo`, `sticky_logo`, `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, `favicon` (×7) | Path via `normalizeMediaPath()` | `logo_media_id`, `dark_logo_media_id`, … (×7 FK columns) | All currently NULL — no backfill needed |
| `portfolios` | `default_image` (string, nullable) | Path via `resolveMediaIdsToPaths()` | `default_image_media_id` FK | 1 orphaned row (portfolio #3, deleted media record) → NULL on backfill |

#### Why Wave 1 First?

All four targets share the same properties:
- One-to-one (single path → single ID)
- No JSON arrays, no pivot tables
- No direct file upload complexity
- Every stored path was written from a `media` record — match via `file_path` is reliable
- Backfill is a straightforward reverse-lookup: `SELECT id FROM media WHERE file_path = ltrim($stored, '/')`

#### Pre-Backfill Audit — RESULTS (2026-06-16, DB: palgoalsnewtest1)

Phase 0.5 audit run against live local DB. Full report: `docs/ADR_005_PHASE05_WAVE1_BACKFILL_AUDIT.md`.

| Column | Total | Non-null | Matched | Orphaned | Note |
|---|---|---|---|---|---|
| `clients.avatar` | 4 | 2 | 0 | **2** | Both: `images/clients/1.png` — old direct upload, no media record |
| `services.icon` | 5 | 5 | 1 | **4** | 4 are `/assets/tamplate/images/icons/*.svg` — static theme assets (EXCLUDED) |
| `portfolios.default_image` | 5 | 5 | 4 | **1** | Portfolio #3: `media/2026/04/69efa028da4677.81471412.png` — deleted media record |
| `general_settings` logos ×7 | 1 | 0 | 0 | **0** | All NULL — no backfill needed |

**Backfill normalization:** Use `AppearanceController::normalizeMediaPath()` logic (strips `storage/` prefix) NOT the `HomeController` version.

```sql
-- Verify on Staging clone before executing Wave 1 migration:
-- clients.avatar — paths with no matching media record
SELECT COUNT(*) FROM clients
WHERE avatar IS NOT NULL
  AND ltrim(avatar, '/') NOT IN (SELECT file_path FROM media);

-- general_settings logo columns (repeat for each of the 7 columns)
SELECT COUNT(*) FROM general_settings
WHERE logo IS NOT NULL
  AND ltrim(logo, '/') NOT IN (SELECT file_path FROM media);

-- portfolios.default_image
SELECT COUNT(*) FROM portfolios
WHERE default_image IS NOT NULL
  AND ltrim(default_image, '/') NOT IN (SELECT file_path FROM media);
```

If any count is non-zero, document the orphaned path and decide:
- Re-register in `media` table (file exists on disk) ✅
- Re-upload and relink ✅
- Accept NULL with fallback image ✅ (explicit decision required)

#### Path Normalisation Note

Before matching, normalise the stored value:
```
ltrim($stored_value, '/')           → removes leading slash (HomeController writes with ltrim)
```
The `AppearanceController` version additionally strips `storage/` prefix. Check the
actual stored values in `general_settings` against both formats before assuming a
clean match.

#### Wave 1 — Blast Radius

**Blade views to update (read-switch, Phase 4 equivalent):**

| File | Old read | New read |
|------|----------|----------|
| `resources/views/components/template/sections/home-works.blade.php` | `asset('storage/' . $work->default_image)` | `$work->defaultImageMedia?->url` |
| `resources/views/components/template/sections/our_work_showcase.blade.php` | `$resolveImageUrl($portfolio->default_image)` | `SectionFrontendMediaResolver::resolve($portfolio->default_image_media_id)` |
| `resources/views/components/template/sections/works.blade.php` | `asset('storage/' . $work->default_image)` | `$work->defaultImageMedia?->url` |
| `resources/views/dashboard/portfolios/_form.blade.php` | `$portfolio->default_image` | `$portfolio->default_image_media_id` |
| `resources/views/dashboard/portfolios/index.blade.php` | `asset('storage/' . $portfolio->default_image)` | `$portfolio->defaultImageMedia?->url` |
| `resources/views/front/pages/portfolio.blade.php` | `asset('storage/' . $portfolio->default_image)` | `$portfolio->defaultImageMedia?->url` |
| `app/Support/Sections/SectionQueryResolver.php:371` | `self::portfolioImageUrl()` (path-based) | `SectionFrontendMediaResolver::resolve($portfolio->default_image_media_id)` |
| General settings layout files (front/layouts/headers/*, footers/*, dashboard/layouts/*) | `asset('storage/' . $settings->logo)` | `$settings->logoMedia?->url` |
| `resources/views/dashboard/clients/*.blade.php` | `asset('storage/' . $client->avatar)` | `$client->avatarMedia?->url` |

**Controllers / methods deletable after Wave 1 read-switch:**

| Method | File | Delete after |
|--------|------|-------------|
| `resolveMediaIdsToPaths()` | `PortfolioController.php` | Wave 1 complete |
| `normalizeMediaPath()` | `HomeController.php` | Wave 1 complete |
| `avatarMedia()` path-based fallback in `ClientController` | `ClientController.php` | Wave 1 complete |

#### Wave 1 — Rollback Complexity

**Low for Phases 1–3 (add columns, dual-write, backfill).** New columns are additive. Old path columns remain intact. Drop the new FK columns to revert.

**Medium for Phase 4 (read-switch).** Old path columns still present — a code-only revert restores reads to path columns.

**Irreversible after Phase 5 (drop old columns).** Do not drop path columns until Phase 4 has been stable for a minimum of one week.

#### Wave 1 — Estimated Effort

| Task | Effort |
|------|--------|
| Pre-migration cleanups (ghost relation, deduplicate normalizeMediaPath) | 1 hour |
| Pre-backfill audit SQL + remediation if needed | 1–2 hours |
| Migration (add FK columns: 4 tables, 10 columns total) | 2 hours |
| Dual-write: HomeController ×7, PortfolioController, ClientController, ServiceController | 3 hours |
| Backfill command + verification | 2 hours |
| Read-switch: controllers + 9 views/files listed above | 4–5 hours |
| Delete conversion methods (resolveMediaIdsToPaths, normalizeMediaPath HomeController copy) | 1 hour |
| Drop old columns migration | 1 hour |
| Testing | 2 hours |
| **Wave 1 total** | **~2–3 hours/day over 5–6 days** |

---

### Wave 2 — Template Image

> **Status:** ⚠ BLOCKED — requires templates.image backfill pre-fix before starting

#### Scope

| Table | Column | Current type | Target |
|-------|--------|-------------|--------|
| `templates` | `image` (string) | Path (two write paths, one without media record) | `image_media_id` FK |

#### Why Separate From Wave 1?

`templates.image` has two write paths in `TemplateController`:

**Path A — Direct file upload (CRITICAL):**
```php
$imagePath = $request->file('image')->store('templates', 'public');
// ↑ writes file to disk, NO Media::create() called
```
Result: `templates.image` stores a path like `templates/abc123.jpg` but `media` table has **no row** for this file. A `SELECT id FROM media WHERE file_path = 'templates/abc123.jpg'` returns zero rows. Migration to Pattern A is impossible without creating the missing `media` record first.

**Path B — Media picker:**
```php
$rawPath = Media::query()->whereKey($id)->value('file_path');
$imagePath = ltrim((string) $rawPath, '/');
```
Result: stored path CAN be matched back to a `media.id`. No backfill blocker here.

#### Required Pre-Wave 2 Fix

Before any Wave 2 migration, run a backfill to create missing `media` records:

```php
// Pseudocode — implement as a seeder or one-time artisan command
Template::whereNotNull('image')->each(function ($template) {
    $normalizedPath = ltrim($template->image, '/');
    $exists = Media::where('file_path', $normalizedPath)->exists();
    if (! $exists && Storage::disk('public')->exists($normalizedPath)) {
        Media::create([
            'file_path'     => $normalizedPath,
            'disk'          => 'public',
            'mime_type'     => 'image/jpeg',   // or detect from file
            'original_name' => basename($normalizedPath),
        ]);
        // Log for audit trail
    } elseif (! $exists) {
        // File missing from disk — log for manual review
    }
});
```

After this runs, re-run the pre-backfill audit SQL:
```sql
SELECT COUNT(*) FROM templates
WHERE image IS NOT NULL
  AND ltrim(image, '/') NOT IN (SELECT file_path FROM media);
```
This count must be zero before Wave 2 migration begins.

Additionally, update `TemplateController` to always create a `media` record for new
direct uploads, so the problem does not recur after migration.

#### Wave 2 — Estimated Effort

| Task | Effort |
|------|--------|
| Backfill script: create missing media records for existing rows | 1–2 hours |
| Verify backfill: count orphaned paths = 0 | 30 minutes |
| TemplateController fix: create Media record on direct upload | 1 hour |
| Migration (add `image_media_id` FK) | 30 minutes |
| Dual-write: TemplateController both paths | 1 hour |
| Backfill command | 1 hour |
| Read-switch: TemplateController + template Blade views | 2 hours |
| Drop old column | 30 minutes |
| **Wave 2 total** | **~3–4 hours** |

---

### Wave 3 — JSON Arrays and Complex Media

> **Status:** ⛔ BLOCKED — requires JSON storage architecture decision before starting

#### Scope

| Table | Column / Key | Type | Blocker |
|-------|-------------|------|---------|
| `portfolios` | `images` (JSON array of path strings) | Array of paths | Pivot table vs JSON-of-IDs decision |
| `general_settings` | `header_variant_settings.*.logo_override` (JSON sub-key) | Nested path inside JSON | Cannot add FK constraint to JSON key |
| `general_settings` | `header_variant_settings.*.payment_logos` (JSON array sub-key) | Array of paths nested in JSON | Same as above |

#### Architecture Decision Required

Two options for `portfolios.images` and the `payment_logos` JSON array:

**Option A — Pivot table (fully Pattern A):**
- Create `portfolio_media` with `(portfolio_id, media_id, sort_order)`
- Drop `portfolios.images` column
- Admin JS (media picker for images array) must be updated to store IDs, not paths

**Option B — JSON of IDs (half-way):**
- Keep `portfolios.images` as JSON, but store integer IDs instead of path strings
- `SectionFrontendMediaResolver::resolveMany()` already handles arrays of IDs
- No new table, no join — simpler migration but not true Pattern A

**Option C — Defer indefinitely (for JSON sub-keys only):**
- `header_variant_settings` is an internal CMS JSON blob; true FK constraint is
  architecturally impossible without a new table
- Acceptable to store IDs inside the JSON (Option B style) and resolve at render time
- `AppearanceController::normalizeMediaPath()` and `normalizeMediaPathList()` remain
  until this is resolved

**Decision must be made and documented before Wave 3 begins.**

#### Wave 3 — Estimated Effort

| Task | Effort |
|------|--------|
| Architecture decision + documentation | 1 hour |
| Option A: `portfolio_media` pivot migration + model + admin JS updates | 4–6 hours |
| Option B: JSON-of-IDs migration + backfill + read-switch | 3–4 hours |
| `header_variant_settings` JSON keys migration (either option) | 2–3 hours |
| Delete `AppearanceController::normalizeMediaPath()` + `normalizeMediaPathList()` | 1 hour |
| Testing | 2 hours |
| **Wave 3 total (Option A)** | **~10–12 hours** |
| **Wave 3 total (Option B)** | **~7–9 hours** |

---

### ADR-005 Overall Effort Estimate (revised from Phase 0 audit)

| Wave | Scope | Effort |
|------|-------|--------|
| Wave 1 — Simple FK columns | clients.avatar, general_settings ×7, portfolios.default_image (services.icon EXCLUDED) | **2–3 hours/day × 5–6 days** |
| Wave 2 — Template image | templates.image (with backfill pre-fix) | **3–4 hours** |
| Wave 3 — JSON arrays | portfolios.images, payment_logos, logo_override | **7–12 hours** (depends on decision) |
| **Total** | | **~11–17 hours of engineering effort** |

The increase from the original ~22–25 hour estimate reflects a rebalancing: the original
estimate included work that turned out to be already done (Pattern A columns), and added
Wave 2 backfill complexity and Wave 3 architectural work that was not previously scoped.
The main sources of complexity are `templates.image` (direct upload gap) and the JSON
array columns (no FK possible, architecture decision needed).

---

### Controllers / Code Deletion Gate (Revised)

| Method | File | Deletable after |
|--------|------|----------------|
| `resolveMediaIdsToPaths()` | `PortfolioController.php` | Wave 1 complete |
| `normalizeMediaPath()` | `HomeController.php` | Wave 1 complete |
| `normalizeMediaPath()` | `AppearanceController.php` | Wave 3 complete |
| `normalizeMediaPathList()` | `AppearanceController.php` | Wave 3 complete |
| `SectionMediaPreviewBuilder` string branch | `SectionMediaPreviewBuilder.php` | Wave 3 complete |
| `Section::image()` ghost relation | `app/Models/Section.php` | Pre-migration cleanup (before Wave 1) |

---

## Phase 0 (ADR-003) — Money Audit

### Objective

Produce a complete inventory of every money-related column, cast, accessor, and helper
in the codebase before any dual-write or backfill begins. The goal is to eliminate the
possibility of an undocumented money column being missed during the migration — a
missed column that continues to store `decimal` values after the migration switches
reads to `price_cents` is a silent correctness bug.

### Required Inventory

Document every occurrence of the following, across all models, migrations, controllers,
and services:

| Category | What to find |
|----------|-------------|
| Decimal money columns | All `decimal`, `float`, or `double` columns storing prices, amounts, or discounts |
| Integer cents columns | All `unsignedInteger`, `integer`, `bigInteger` columns storing money in cents |
| Eloquent money casts | All `'price' => 'float'`, `'price' => 'decimal:2'`, `'price_cents' => 'integer'` in `$casts` arrays |
| Money accessors | All `get*Attribute()` methods that divide or multiply by 100 |
| Money mutators | All `set*Attribute()` methods that convert input to cents |
| Money helpers / services | Any method or function that converts between decimal and cents |

### Required Output Table

| Table | Column | DB Type | PHP Cast | Current Standard | ADR-003 Impact |
|-------|--------|---------|----------|-----------------|----------------|
| `subscriptions` | `price` | `decimal(10,2)` | `float` | ❌ Decimal | Migrate → `price_cents` |
| `coupons` | `discount_value` | `decimal(10,2)` | none | ❌ Decimal | Migrate → `discount_value_cents` + `discount_percent` |
| `domain_tld_prices` | `cost`, `sale` | `decimal(10,2)` | none | ❌ Decimal | Deferred sprint |
| `templates` | `price`, `discount_price` | `decimal(10,2)` | none | ❌ Decimal | Deferred sprint |
| `plans` | `monthly_price_cents`, `annual_price_cents` | `unsignedInteger` | `integer` | ✅ Cents | No change |
| `invoices` | `*_cents` columns | `integer` | none | ✅ Cents | No change |
| `invoice_items` | `*_cents` columns | `integer` | none | ✅ Cents | No change |
| `order_items` | `price_cents` | `unsignedBigInteger` | `integer` | ✅ Cents | No change |
| *(any additional found)* | | | | | |

### Transition Criterion

ADR-003 Phase 1 does not begin until:

```
No undocumented money columns exist.
```

Every decimal money column is in the output table. Every cents column is confirmed
compliant. Any column not in this inventory that surfaces during Phase 2 (dual-write)
represents an undocumented write path — a potential source of financial data
inconsistency.

---

## Phase 3 — ADR-003: Integer Cents Money Storage

### ADR-003 Readiness Blockers

> **ADR-003 is NOT READY for execution** until the two blockers below are resolved.
> Do not begin Phase 0 (Money Audit) until both are addressed.

**Blocker 1 (Critical) — Three undocumented subscription write paths.**

A code audit found three additional locations that write `subscriptions.price`. None was
in the original scope. If these are not updated in Phase 2 (dual-write), `price_cents`
will be NULL for subscriptions created through these paths, and the 30-day monitoring
count (`WHERE price_cents IS NULL`) will never reach zero.

| File | Write | Price written |
|------|-------|---------------|
| `app/Services/Billing/OrderActivationService.php:210` | `'price' => $template->price` | Template's decimal price |
| `app/Http/Controllers/Front/CheckoutController.php:352` | `'price' => $config['unit_cents'] / 100` | Converted from cents |
| `app/Http/Controllers/Admin/Management/ServerController.php:127` | `'price' => 0` | Hardcoded zero |

These must be added to the Phase 2 dual-write scope alongside `SubscriptionController`.

**Blocker 2 (High) — No `CouponController` exists in the codebase.**

The Phase 2 effort line "Phase 2 dual-write for coupons (CouponController) — 2 hours"
references a file that does not exist. A full search of `app/Http/Controllers/**/*.php`
and `routes/**/*.php` found no `CouponController`. Before starting Sprint 5:

1. Determine whether `discount_value` is ever written at runtime (create/update), or only via seeders
2. Identify the actual boundary where coupon data is modified
3. Build dual-write into that boundary — do not estimate 2 hours for updating a controller that does not exist

**Resolution action before Sprint 5:** Replace the "CouponController" line item with a
1–2 hour discovery task: *"Locate all coupon write paths or confirm coupons are seed-only;
then scope dual-write accordingly."*

---

### Why Last?

ADR-003 touches financial data. A mistake — wrong backfill multiplier, missed read
path, or a unit conversion applied twice — produces financially incorrect invoice
amounts or subscription prices. The consequences are:

- Invoices with wrong totals (×100 or ÷100 off)
- Payment amounts charged to customers that don't match the plan price
- Revenue reports with incorrect aggregates

None of these produce an immediate application error. They produce silent financial
discrepancies that surface at reconciliation. This makes ADR-003 the highest-risk
ADR and the one that most requires Staging validation over at least one billing cycle
before production deployment.

Executing ADR-003 last also means the codebase is cleaner: ADR-006 has removed the
feedbacks naming confusion, and ADR-005 has removed the Pattern C media conversion
utilities. The billing migration proceeds in an already-improved codebase.

### Financial Risk Analysis

| Risk | Scenario | Severity | Mitigation |
|------|----------|----------|-----------|
| Backfill error | `price_cents = price` instead of `price * 100` | Critical — prices off by 100× | Pre-backfill report + reconciliation check after backfill |
| Missed read path | Controller still reads `price` after Phase 3 switches reads | High — invoice generated from wrong source | Full grep of `->price` before switching reads |
| Double conversion | UI sends cents, controller multiplies again | High — prices doubled | Input format audit: confirm admin form still sends decimal (e.g., "29.99") not cents |
| Coupon type confusion | Percentage coupons treated as cents after column split | Medium — discounts wrong | Separate `discount_percent` column, clear validation |
| Report joins | Report query joins `subscriptions.price_cents` with old `price` aggregate | Medium — mixed units in reports | Audit all reporting queries before Phase 4 |

### Affected Tables and Columns

| Table | Column | Current Type | Target |
|-------|--------|-------------|--------|
| `subscriptions` | `price` (`decimal(10,2)`) | `float` cast | `price_cents` (`unsignedInteger`) |
| `coupons` | `discount_value` (`decimal(10,2)`) | none | `discount_value_cents` (fixed) + `discount_percent` (percentage) |
| `domain_tld_prices` | `cost`, `sale` (`decimal(10,2)`) | none | `cost_cents`, `sale_cents` |
| `templates` | `price`, `discount_price` (`decimal(10,2)`) | none | `price_cents`, `discount_price_cents` |

**Note on scope priority:** `subscriptions.price` is TD-1 and the most urgent
because it directly affects invoice creation. `coupons.discount_value` is TD-2 and
blocks coupon application (currently hardcoded to `$discount = 0`). `domain_tld_prices`
and `templates` are lower priority because they feed into `order_items.price_cents`
(already correct) at cart-build time and have no direct invoice path.

**Recommended scope for Phase 3 Sprint:** Only `subscriptions` and `coupons`.
`domain_tld_prices` and `templates` can follow in a subsequent sprint once the higher-risk
tables are migrated and stable.

### Backfill Requirements

The backfill formula for `subscriptions.price_cents`:

```
price_cents = ROUND(price * 100)
```

Use `ROUND()`, not integer truncation. `price = 10.005` truncated gives `1000`; rounded
gives `1001`. On `decimal(10,2)` data, values should already have exactly two decimal
places, but rounding is the safe default.

**Pre-backfill report (mandatory):** Before running the backfill, export a CSV of all
subscriptions with `id`, `price`, and the computed `price_cents = ROUND(price * 100)`.
Store this CSV. After the backfill, run a reconciliation count:

```sql
SELECT COUNT(*) FROM subscriptions
WHERE price_cents != ROUND(price * 100);
```

This count must be zero. If non-zero, identify and manually correct the discrepant rows
before switching reads.

For `coupons.discount_value` → `discount_value_cents`:
- Rows where `discount_type = 'fixed'`: `discount_value_cents = ROUND(discount_value * 100)`
- Rows where `discount_type = 'percent'`: `discount_percent = ROUND(discount_value)` (store as integer 0–100)

### Dual Write Period

**Minimum 30 days.** Both `subscriptions.price` (decimal) and `subscriptions.price_cents`
(integer) must be written simultaneously for at least one complete billing cycle. During
this period, `price` remains the read source. This allows confirmation that:

1. Every new subscription created after Phase 2 has a correct `price_cents` value.
2. Every subscription renewal/update also writes `price_cents` correctly.
3. No edge cases (free plans, zero-price internal subscriptions, trial subscriptions)
   produce incorrect `price_cents`.

Do not switch reads (Phase 3) until the dual-write report confirms 100% coverage.

### Reporting Validation

Before Phase 4 (drop old decimal columns), validate all reporting queries:

| Report | Column used | Check |
|--------|-------------|-------|
| `HomeController` revenue | `Invoice::paid()->sum('total_cents')` | Already correct — no change |
| Subscription revenue by period | If joining `subscriptions.price` | Must switch to `price_cents` |
| Plan pricing display | `Plan::monthly_price_cents / 100` | Already correct — no change |
| Coupon application | Currently `$discount = 0` | Must implement after `discount_value_cents` is available |

**Important:** `Plan::monthly_price_cents` is already correct (invoices are already
correct). The ADR-003 migration for subscriptions and coupons does not change how
Plans are displayed or how Invoices are totaled — only the snapshot price stored
at subscription creation time and the coupon discount calculation.

### Rollback Complexity

**Low for Phases 1–2.** Adding columns and dual-writing is additive. Drop the new
columns to revert with no data loss.

**Medium for Phase 3.** Switching reads to `price_cents` while `price` is still
present: revert reads to `price` in controllers, no migration needed.

**High for Phase 4.** Once `price` is dropped from `subscriptions`, restoring it
requires recomputing from `price_cents` (exact reverse of the backfill). This is
deterministic (÷100) but requires a new migration. Any subscriptions created after
the drop will have `price_cents` only.

**Never execute Phase 4 without:** A confirmed-correct backfill report, at least
one billing cycle of dual-write stability, and a tested down migration that restores
the `price` column from `price_cents`.

### Estimated Effort

| Task | Effort |
|------|--------|
| **ADR-003 scoping expansion** (scope all write paths before any code changes) | **+2–4 hours** |
| Phase 1 migration (add `price_cents`, `discount_value_cents`, `discount_percent`) | 2 hours |
| Phase 2 dual-write — `SubscriptionController` store/update/renew | 3 hours |
| Phase 2 dual-write — `OrderActivationService`, `CheckoutController`, `ServerController` (confirmed during audit) | 2 hours |
| Phase 2 dual-write — coupon write paths (discovery task: locate write boundaries or confirm seed-only; scope before estimating) | 1–2 hours discovery, then re-estimate |
| Pre-backfill report and export | 1 hour |
| Phase 3 backfill command | 2 hours |
| Phase 3 reconciliation and verification | 2 hours |
| Dual-write monitoring period | **30 days minimum** (calendar time, not effort) |
| Phase 3 read switch (controllers, model cast, form validation) | 4 hours |
| Reporting query audit | 2 hours |
| Phase 4 drop old columns (after confirmed stable) | 1 hour |
| Coupon application implementation (unlocked by this migration) | 4–6 hours |
| **Total effort** | **~25–30 hours of engineering** (up from ~23–26 after scope expansion) |
| **Total calendar time** | **~45–60 days minimum** (dominated by dual-write monitoring) |

---

## Dependency Graph

```
ADR-006 (Feedbacks Rename) ✅ COMPLETE 2026-06-16
│
│  No technical dependency — executed first by risk profile
│  Pure rename; zero data transformation
│  Eliminated DB/PHP naming mismatch
│
├──────────────────────────────────────────────────────────┐
│                                                          │
▼                                                          ▼
ADR-005 (Media Unification)                    ADR-003 (Integer Cents)
│                                              │
│  No technical dependency on ADR-006          │  No technical dependency on ADR-005
│  Phase 0 audit COMPLETE (2026-06-16)         │  Phase 0 Money Audit: NOT STARTED
│                                              │  Blockers: 3 undocumented write paths;
│  Wave 1: READY TO START                      │  CouponController does not exist
│  Wave 2: blocked on templates.image backfill │
│  Wave 3: blocked on JSON storage decision    │  Can start Phase 0 NOW — parallel
│                                              │  with ADR-005 Wave 1 if needed
│  No constraint from ADR-003 timing           │
│                                              │  Migrations must NOT share a deploy
└──────────────────────────────────────────────┘  window with ADR-005 migrations
```

**Key change from original plan:** ADR-005 and ADR-003 are now shown as **parallel tracks**
rather than a strict sequence. Phase 0 audit work for ADR-003 can begin while ADR-005 Wave 1
is in progress — they touch entirely different tables. However, **migration deploys must
remain isolated**: no ADR-005 migration and ADR-003 migration in the same deploy window.
This is Principle 1 of the Risk Isolation Strategy (financial migrations isolated from
schema cleanup) and remains unchanged.

**Summary:** The three ADRs have no hard technical dependencies on each other.
The order is determined entirely by risk escalation. ADR-003 Phase 0 (read-only audit)
can run in parallel with ADR-005 Wave 1 if business urgency requires it. ADR-003
migration Phases must still run after ADR-005 migrations are deployed and stable.

---

## Pre-Execution Checklist

Before beginning any Phase 1 migration, confirm:

**General:**

- [ ] Full database backup taken and verified restorable
- [ ] Staging environment is a fresh clone of production data (not stale seed data)
- [ ] Migration applied to Staging and tested before production deploy
- [ ] All pending unrelated migrations have been deployed (no migration queue backlog)
- [ ] `php artisan migrate:status` shows all existing migrations as "Ran"
- [ ] No other developer has pending migrations referencing the affected tables

**For ADR-006 specifically:**

- [x] ADR-006 completed. Legacy Livewire testimonial files were deleted and are no longer in scope.
- [ ] No raw SQL in the application references `feedbacks` or `feedback_translations` directly
  (confirmed: code audit found only Eloquent/ORM usage)
- [ ] No stored procedures or DB views reference `feedbacks` (MySQL: `SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW'`)
- [ ] The down migration is implemented and tested on Staging before running up on production

**For ADR-005 specifically:**

- [ ] Phase 0 audit complete ✅ (2026-06-16) — see `docs/ADR_005_PHASE0_MEDIA_AUDIT.md`
- [ ] Pre-migration cleanups done: ghost `Section::image()` removed, `normalizeMediaPath()` deduplicated
- [ ] Wave 1 pre-backfill audit SQL run on Staging clone: clients, services, portfolios, general_settings — count = 0 or remediated
- [ ] Wave 2 blocker resolved: `templates.image` orphan backfill run, 0 missing media records confirmed
- [ ] Wave 3 blocker resolved: JSON storage architecture decision made and documented
- [ ] `SectionFrontendMediaResolver` is confirmed working on Staging with real media IDs

**For ADR-003 specifically:**

- [ ] Pre-backfill CSV export saved: `id, price, ROUND(price * 100) AS expected_price_cents`
- [ ] Staging billing cycle tested: create a subscription, renew it, generate invoice, confirm amounts
- [ ] All paths that read `subscriptions.price` audited: `grep -r "->price\b" app/`
- [ ] Admin form confirmed to submit decimal input (e.g., "29.99") — not cents
- [ ] `Plan::getMonthlyPriceAttribute()` confirmed still correct (÷100 accessor — no change needed)

---

## Post-Execution Validation Checklist

### ADR-006 Validation

- [ ] `SHOW TABLES LIKE 'testimonials'` returns one row; `SHOW TABLES LIKE 'feedbacks'` returns zero
- [ ] `DESCRIBE testimonial_translations` shows `testimonial_id` and `text` columns; no `feedback_id` or `feedback` columns
- [ ] Admin testimonial create/edit/delete completes without error
- [ ] Public testimonial submission form submits successfully
- [ ] Testimonials appear correctly on the public marketing page
- [ ] `SectionQueryResolver::testimonials()` returns data (section with testimonials renders)
- [x] Legacy Livewire testimonial component removed; no active route/include remains.
- [ ] `docs/03-database-architecture.md` updated: `feedbacks` → `testimonials`
- [ ] `docs/29-content-showcase.md` updated: critical finding removed

### ADR-005 Validation

**Wave 1 — after backfill, before read-switch:**

- [ ] `SELECT COUNT(*) FROM portfolios WHERE default_image IS NOT NULL AND default_image_media_id IS NULL` = 0
- [ ] `SELECT COUNT(*) FROM clients WHERE avatar IS NOT NULL AND avatar_media_id IS NULL` = 0
- [ ] `SELECT COUNT(*) FROM general_settings WHERE logo IS NOT NULL AND logo_media_id IS NULL` = 0
- [ ] Spot-check: 10 random portfolio `default_image_media_id` values resolve to correct URLs
- [ ] Ghost relation `Section::image()` removed from `app/Models/Section.php`
- [ ] `normalizeMediaPath()` deduplicated into shared service

**Wave 1 — after read-switch:**

- [ ] Admin portfolio list renders all images correctly
- [ ] Public portfolio section renders all images correctly
- [ ] Admin general settings page shows all logo/favicon images correctly
- [ ] Public site header/footer logos render correctly
- [ ] Media picker in portfolio form correctly pre-selects the current image
- [ ] Clients list/show renders avatars correctly
- [ ] `resolveMediaIdsToPaths()` deleted from `PortfolioController`
- [ ] `normalizeMediaPath()` deleted from `HomeController`

**Wave 1 — after column drop:**

- [ ] `DESCRIBE portfolios` shows no `default_image` column
- [ ] `DESCRIBE clients` shows no `avatar` column
- [ ] `DESCRIBE general_settings` shows no `logo`, `dark_logo`, `sticky_logo`,
  `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, or `favicon` columns

**Wave 2 — after backfill, before migration:**

- [ ] `SELECT COUNT(*) FROM templates WHERE image IS NOT NULL AND ltrim(image, '/') NOT IN (SELECT file_path FROM media)` = 0
- [ ] New template direct-upload creates a `media` record

**Wave 2 — after column drop:**

- [ ] `DESCRIBE templates` shows no `image` column; `image_media_id` present

**Wave 3 — after completion:**

- [ ] `DESCRIBE portfolios` shows no `images` column (or: JSON stores IDs not paths)
- [ ] `normalizeMediaPath()` deleted from `AppearanceController`
- [ ] `normalizeMediaPathList()` deleted from `AppearanceController`
- [ ] `SectionMediaPreviewBuilder` string branch removed (only numeric ID branch remains)

### ADR-003 Validation

After Phase 2 (dual write, 30 days):

- [ ] `SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL` = 0
- [ ] `SELECT COUNT(*) FROM subscriptions WHERE price_cents != ROUND(price * 100)` = 0
- [ ] `SELECT COUNT(*) FROM coupons WHERE discount_type = 'fixed' AND discount_value_cents IS NULL` = 0

After Phase 3 (read switch):

- [ ] Create new subscription → `price_cents` populated, invoice generated with correct `total_cents`
- [ ] Apply fixed coupon → `discount_cents` on invoice correctly equals `discount_value_cents`
- [ ] Revenue report in HomeController shows correct totals
- [ ] Subscription pricing displayed in client portal shows correct decimal (÷100)

After Phase 4 (drop old decimal columns):

- [ ] `DESCRIBE subscriptions` shows no `price` column; `price_cents` present
- [ ] `DESCRIBE coupons` shows no `discount_value` column; `discount_value_cents` and `discount_percent` present
- [ ] Application starts without errors (no references to dropped columns in compiled queries)

---

## Recommended Milestones

### Sprint 1 — ADR-006 (2–3 days)

| Day | Task |
|-----|------|
| Day 1 | Write migration, update 2 models, update 3 controllers + Livewire component |
| Day 2 | Audit all Blade views for `->feedback` reads; run migration on Staging |
| Day 3 | Staging validation checklist; update 2 documentation files; deploy to production |

**Exit criterion:** All 9 validation checks passed on production. `feedbacks` table no
longer exists.

---

### Sprint 2 — ADR-005 Wave 1 (Simple FK Columns) (~1 week)

> **Pre-conditions:** ADR-006 complete ✅, ghost relation cleaned up, `normalizeMediaPath()` deduplicated.

| Day | Task |
|-----|------|
| Day 1 | Pre-migration cleanups: remove ghost `Section::image()`, deduplicate `normalizeMediaPath()` |
| Day 1–2 | Pre-backfill audit SQL on Staging clone — confirm counts match Phase 0.5 findings (2 orphan avatars, 1 orphan portfolio, 0 gs logos) ✅ already run on local DB |
| Day 3 | Wave 1 migration: add FK columns (9 columns across 3 tables — services.icon excluded) on Staging |
| Day 3–4 | Dual-write: HomeController ×7, PortfolioController, ClientController (ServiceController excluded) |
| Day 5 | Backfill command on Staging; verify all `*_media_id` columns populated |
| Day 6 | Deploy migration + dual-write to production; run backfill on production |
| Day 7 | Read-switch: controllers + 9 Blade/PHP files listed in Wave 1 blast radius |

**Exit criterion:** All Wave 1 FK columns populated and verified. Old path columns still
in place (safe point to pause before read-switch). `resolveMediaIdsToPaths()` and
`HomeController::normalizeMediaPath()` deleted.

---

### Sprint 3 — ADR-005 Wave 1 Drop + Wave 2 Prep (3–4 days)

| Day | Task |
|-----|------|
| Day 1 | Full validation: admin portfolio, public portfolio, logos, favicon, media picker, clients avatars |
| Day 2 | Wave 1 Phase 5: drop old path columns from clients, services, portfolios, general_settings |
| Day 3 | Wave 2 prep: run templates.image orphan audit; write backfill script for missing media records |
| Day 4 | Run backfill on Staging; re-run orphan audit; confirm 0 orphaned template image paths |

**Exit criterion:** Wave 1 old columns dropped. Templates.image orphan audit shows 0
missing media records on Staging. Wave 2 unblocked.

---

### Stability Window — Wave 1 (1 week)

Monitor production after Wave 1 read-switch and column drop. Confirm no broken images,
no error logs. Do not begin Wave 2 migration until stable for at least one week.

**Gate:** Any broken image during this window → pause and investigate before Wave 2.

---

### Sprint 4 — ADR-005 Wave 2 (Template Image) (2–3 days)

| Day | Task |
|-----|------|
| Day 1 | Wave 2 migration: add `image_media_id` FK to `templates`; dual-write in `TemplateController` |
| Day 2 | Backfill command; verify 0 templates with `image_media_id IS NULL`; fix `TemplateController` to create `Media` records for direct uploads |
| Day 3 | Read-switch: `TemplateController` + template Blade views; drop `templates.image` |

**Exit criterion:** `templates.image_media_id` populated. `TemplateController` creates
`Media` record for all new uploads. Old `templates.image` column dropped.

---

### Sprint 5 — ADR-003 Phase 0 + Phases 1–2 (1 week)

> **Note:** ADR-005 Wave 1 column drop must be deployed and stable before ADR-003
> migration phases begin. Financial migrations must not run in parallel with
> schema-cleanup migrations.
>
> **However:** ADR-003 Phase 0 (read-only money audit) can begin in parallel with
> ADR-005 Wave 1 if business urgency requires it — it is a read-only discovery pass
> with no schema changes.

| Day | Task |
|-----|------|
| Day 1–2 | **Phase 0 — Money Audit:** produce complete inventory of all money columns, casts, accessors |
| Day 2 | Verify no undocumented money columns exist; sign off on the output table |
| Day 3 | Phase 1 migration: add `price_cents`, `discount_value_cents`, `discount_percent` |
| Day 4 | Phase 2 dual-write: update `SubscriptionController` (store, update, renewal paths) |
| Day 5 | Phase 2 dual-write: update `CouponController`; save pre-backfill CSV export |

**Exit criterion:** Phase 0 money inventory complete and approved. Dual-write running
on production. **30-day monitoring clock started.**

---

### 30-Day Monitoring Window

Both `subscriptions.price` and `subscriptions.price_cents` are written on every
subscription operation. Monitor:

- Daily: `SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL` → must be 0
- Daily: `SELECT COUNT(*) FROM subscriptions WHERE price_cents != ROUND(price * 100)` → must be 0
- Weekly: Review any new subscription created at `$0.00` or suspiciously round amounts

Do not proceed to Sprint 6 until both counts have been zero for 30 consecutive days.

---

### Sprint 6 — ADR-003 Phases 3–4 (Backfill, Switch, Drop) (~1 week)

| Day | Task |
|-----|------|
| Day 1 | Run final 30-day dual-write report; confirm zero discrepancies |
| Day 2 | Phase 3 backfill on Staging; run reconciliation SQL; spot-check invoice amounts |
| Day 3 | Phase 3 backfill on production; run reconciliation SQL on production |
| Day 4 | Switch reads to `price_cents`; update model casts; update form validation |
| Day 4 | Reporting query audit: confirm no reports still join on `price` |
| Day 5 | Implement coupon application (unblocked by `discount_value_cents`) |
| Day 15+ | Phase 4: drop `price` from `subscriptions`, `discount_value` from `coupons` (after 2-week stability) |

**Exit criterion:** `subscriptions.price` dropped. `coupons.discount_value` dropped.
Coupon application functional. Reconciliation report clean. All ADRs closed.

---

## Risk Isolation Strategy

Three ADRs run in sequence. The isolation strategy exists to ensure that a failure in
any one ADR does not contaminate the others, and that each sprint can be rolled back
independently without affecting already-completed work.

### Principle 1 — Financial Migrations Are Isolated From Schema Cleanup

ADR-003 (financial) must not run in parallel with ADR-005 (schema cleanup) or ADR-006
(rename). The reason is operational, not technical: a financial migration requires
complete developer attention and zero concurrent schema changes. If an unexpected
query behavior surfaces during ADR-003's dual-write period, the investigation must not
be confused by simultaneous schema changes from another ADR.

**Enforcement rule:** ADR-005 Phase 5 (the column-drop migration) must be deployed and
confirmed stable before ADR-003 Phase 1 begins. There must be at least one clean
production deploy between the end of ADR-005 and the start of ADR-003.

### Principle 2 — Media Migrations Must Close Completely Before ADR-003

ADR-005 introduces and then deletes three conversion methods:
`resolveMediaIdsToPaths()`, `normalizeMediaPath()`, and `normalizeMediaPathList()`.
These methods exist to convert between media IDs and path strings. If ADR-003 were to
run while these methods are still active, any interaction between the billing pipeline
and portfolio/appearance data (e.g., a subscription confirmation page that renders a
logo) could create an entangled failure mode where it is unclear whether a bug
originates from the media migration or the billing migration.

**Enforcement rule:** `resolveMediaIdsToPaths()` and `normalizeMediaPath()` must be
confirmed deleted (Phase 4 complete) before ADR-003 Phase 0 begins.

### Principle 3 — Each Sprint Has an Independent Rollback

Each sprint in this plan produces a state from which the system can roll back to the
previous sprint without affecting the state of any other sprint.

| Sprint | Rollback Action | Affects Other Sprints? |
|--------|----------------|----------------------|
| Sprint 1 (ADR-006) | Down migration reverts table renames; restore `$table` overrides | No — DB is clean before this sprint |
| Sprint 2 (ADR-005 Phase 0–3) | Drop the new `*_media_id` columns; revert dual-write controller changes | No — old path columns untouched |
| Sprint 3 (ADR-005 Phase 4) | Revert read-switch in controllers and views; restore conversion methods | No — old path columns still present |
| Sprint 4 (ADR-005 Phase 5) | **Irreversible.** Only proceed after Stability Window. | N/A — no rollback |
| Sprint 5 (ADR-003 Phase 0–2) | Drop `price_cents`, `discount_value_cents`, `discount_percent` columns | No — `price` is still the read source |
| Sprint 6 (ADR-003 Phase 3–4) | Phase 4 drop is **irreversible.** Restore `price` from `price_cents ÷ 100` via new migration | N/A after Phase 4 |

The two **irreversible** points are: ADR-005 Phase 5 (path column drop) and ADR-003
Phase 4 (decimal column drop). Both are explicitly gated behind a stability window of
minimum 1–2 weeks. Neither should be executed as part of the same deploy as the
preceding switch-reads phase.

### Principle 4 — Audit Phases Are Not Optional

Phase 0 for ADR-005 and Phase 0 for ADR-003 are gated entry conditions, not optional
preparatory steps. A migration that begins without a clean Phase 0 report is an
unplanned migration. The 30-day monitoring window for ADR-003 exists for the same
reason: financial data requires proof of correctness across time, not just at the
point of deployment.

**If Phase 0 reveals unexpected findings** (more missing paths than expected, an
undocumented money column), the sprint pauses until those findings are resolved. This
is not a delay — it is the system working correctly.

---

## Post-Completion Summary

### Final Recommended Order

```
ADR-006 ✅ COMPLETE (2026-06-16)
  ↓
ADR-005 Phase 0 (Media Audit) ✅ COMPLETE (2026-06-16)
  ↓
ADR-005 Wave 1 (Simple FK Columns)     ←── START HERE
  ↓
ADR-005 Wave 1 Drop + Wave 2 Prep
  ↓                                        ←── ADR-003 Phase 0 (Money Audit) can
ADR-005 Wave 2 (Template Image)              begin here in parallel (read-only)
  ↓
[Stability Window: 1 week]
  ↓
ADR-005 Wave 3 (JSON Arrays) — after architecture decision
  ↓
[Confirm ADR-005 Wave 1 columns dropped and stable]
  ↓
ADR-003 Phase 1–2 (Dual Write) → [30-day Monitoring] → Phase 3–4
```

**ADR-005 Verdict:** `READY FOR WAVE 1` — `WAVE 2 REQUIRES TEMPLATE IMAGE BACKFILL` — `WAVE 3 REQUIRES JSON STORAGE DECISION`

### ADRs Executable Immediately (Without Staging Cycle)

**ADR-006** — the migration is a pure DB rename with no data transformation. It can
be run on Staging and production in the same sprint with minimal risk. It requires
no prior conditions. Start here.

### ADRs Requiring Staging First

**ADR-005** — the backfill relies on matching path strings to `media.file_path`
records. The pre-backfill audit must be run against a production-data clone to
determine if any unmatched paths exist. Running it on seed data gives a false sense
of safety because seeds do not reflect real uploaded file paths.

**ADR-003** — always requires Staging with real subscription data and a full billing
cycle test before production. Staging with seed data is insufficient for financial
validation.

### Highest Data Risk

**ADR-003** — wrong backfill multiplier or missed read path produces silent financial
errors. The risk is not a visible 500 error; it is an invoice with a subtly wrong
`total_cents` value. The dual-write monitoring period exists specifically to detect
this class of error before the old `price` column is dropped.

### Reason to Change the Proposed Order

The only strong reason to change the order is if **coupon application** is a
business-critical feature needed urgently. In that case, ADR-003 (specifically the
`coupons` migration) could be prioritized earlier — but only for the coupons table,
not for subscriptions. The subscriptions migration still requires the full 30-day
dual-write period regardless.

ADR-005 and ADR-006 have no business-urgency argument that would justify reordering
them ahead of ADR-003. They address developer experience and data integrity, not a
broken user-facing feature.
