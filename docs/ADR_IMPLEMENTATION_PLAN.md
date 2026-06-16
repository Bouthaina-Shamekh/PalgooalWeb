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
| ADR-006 | **READY WITH MINOR ACTIONS** — 2 missing Blade views added to scope; column-rename gap in controllers clarified |
| ADR-005 | **READY AFTER PHASE 0 MEDIA AUDIT AND SCOPE CLARIFICATIONS** — HomeController conversion method and 6th portfolio view added to scope; Phase 4 deletion constraint clarified |
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

## Phase 0 (ADR-005) — Media Audit

### Objective

Verify data quality across all Pattern B and Pattern C columns before any schema
change is made. This phase has no migration and no code change — it is a read-only
diagnostic pass that must complete and pass its exit criterion before Phase 1 begins.

### Required Checks

| Check | Scope |
|-------|-------|
| Match all `portfolios.default_image` paths against `media.file_path` | All non-NULL rows |
| Match all `portfolios.images` path entries (JSON array) against `media.file_path` | All non-NULL entries |
| Match all `general_settings.logo`, `dark_logo`, `sticky_logo`, `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, `favicon` paths against `media.file_path` | All non-NULL columns |
| Detect duplicate paths (same path stored in multiple places) | Cross-table and within-table |
| Detect orphaned paths (path stored, no matching `media` record exists) | Per column, per table |
| Detect inconsistent path formats (leading slash, `storage/` prefix, full URL vs. relative path) | All path columns |

**Path normalisation note:** Before matching, normalise both sides to a canonical
relative format (strip leading `/`, strip `storage/` prefix, lowercase). A mismatch
due to formatting is not a missing media record — it is a format inconsistency that
the backfill command must handle, not remediate manually.

### Required Output Report

Run the audit on a production-data Staging clone and produce the following table:

| Metric | Count |
|--------|-------|
| Matched Paths | |
| Missing Paths (no `media` record found, after normalisation) | |
| Duplicate Paths (same path in two or more rows) | |
| Invalid Paths (empty string, null-like value, or external URL stored in path column) | |

For every row where **Missing Paths > 0**, document:
- Which table and column
- The stored path value
- Whether the file exists on disk but has no `media` record (re-registerable)
- Whether the file is missing entirely (re-upload required or accept NULL)

### Transition Criterion

**ADR-005 Phase 1 does not begin until:**

```
Missing Paths = 0
```

OR every missing path is individually documented with one of:
- "File re-registered in `media` table" ✅
- "File re-uploaded and re-linked" ✅
- "Accepted as NULL — fallback image in use" ✅ (with explicit approval)

No exceptions. A missing path that reaches the backfill phase will produce a NULL
`*_media_id` in a column that was previously non-NULL — a silent demotion from a
stored image to no image.

---

## Phase 2 — ADR-005: Media Storage Format Unification

### Why Second?

ADR-005 is structurally more complex than ADR-006 but carries no financial risk.
The migration targets image references in `portfolios` and `general_settings` — data
that, if temporarily wrong, produces a broken image display rather than a financial
error. This is recoverable and visible immediately.

Placing ADR-005 before ADR-003 means the media system is unified and consistent
before financial data migration begins. This matters because `PortfolioController`
and `AppearanceController` currently contain conversion utilities
(`resolveMediaIdsToPaths()`, `normalizeMediaPath()`) that would otherwise coexist
with the billing migration period, creating unnecessary complexity in the codebase
during an already careful migration window.

### Tables Requiring Migration

| Table | Columns | Change |
|-------|---------|--------|
| `portfolios` | `default_image` (string) | Replace with `default_image_media_id` FK |
| `portfolios` | `images` (JSON array of paths) | Replace with `images_media_ids` (JSON array of IDs) |
| `general_settings` | `logo`, `dark_logo`, `sticky_logo`, `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, `favicon` (7 string columns) | Replace each with `*_media_id` FK columns |

**Already compliant — no migration needed:**

| Table | Column | Pattern |
|-------|--------|---------|
| `feedbacks` (`testimonials` after Phase 1) | `image_id` | Pattern A ✅ |
| `section_definitions` | `preview_media_id` | Pattern A ✅ |
| `section_translations.content` (JSON) | media-type fields | App-layer ID reference ✅ |

### Backfill Requirements

The backfill relies on reverse-lookup: find a `media` record whose `file_path` matches
the stored path string.

**Pre-backfill audit (mandatory before Phase 1 of ADR-005):**

Run the following counts before touching any schema:

```sql
-- Portfolios with no matching media record for default_image
SELECT COUNT(*) FROM portfolios
WHERE default_image IS NOT NULL
  AND default_image NOT IN (SELECT file_path FROM media);

-- general_settings rows with no matching media record for logo
SELECT COUNT(*) FROM general_settings
WHERE logo IS NOT NULL
  AND logo NOT IN (SELECT file_path FROM media);
```

**If the count is zero:** The backfill will succeed for all rows.  
**If the count is non-zero:** Those rows have path strings with no matching `media`
record. These must be manually remediated before Phase 3 (switch reads). Options:
re-upload the file, or accept a `NULL` media ID and use a default fallback image.

The most common cause of unmatched paths is storage path format differences — paths
stored with a `storage/` prefix vs. without, or with a leading slash vs. without.
The backfill command must normalize paths before comparing.

### Data Verification Requirements

After the backfill (Phase 3 of ADR-005) and before switching reads (Phase 4):

1. Count rows where `default_image IS NOT NULL AND default_image_media_id IS NULL`.
   This count must be zero (or reviewed and accepted as intentional NULLs).
2. Spot-check 10 portfolio records: confirm that `Media::find($portfolio->default_image_media_id)->url`
   returns the same URL that `asset('storage/' . $portfolio->default_image)` previously returned.
3. Confirm the `general_settings` row has all 7 `*_media_id` columns populated.
4. Load the admin general-settings page and confirm all logo/favicon images render.
5. Load the public marketing page and confirm logos in header/footer render.
6. Load a portfolio page and confirm images render.

### JSON Columns (Appearance Variant Settings)

`header_variant_settings` and `footer_variant_settings` in `general_settings` store
`logo_override` values inside JSON blobs. These are the most complex part of ADR-005.

Two migration options:

**Option A (preferred):** Add dedicated `logo_override_media_id` columns alongside
the JSON. Store the ID in the flat column; the JSON logo_override key becomes ignored
or removed.

**Option B (deferred):** Treat the JSON logo_override values as part of the
Appearance system's own internal state and migrate them in a dedicated Appearance
migration, decoupled from the main ADR-005 migration. This allows Phase 1–4 of
ADR-005 to proceed without touching the complex JSON structure.

**Recommendation:** Option B. Defer the JSON logo_override migration to a follow-up
migration after ADR-005 Phases 1–5 are complete for the flat columns.

### Controllers / Code to Delete After Phase 4

The following methods support Pattern C→B and Pattern B conversion. Their deletion gates
are **not uniform** — two of the three files still need their conversion methods for deferred
JSON columns and cannot be fully cleaned until a follow-up migration.

| Method | File | Can delete after Phase 4? |
|--------|------|--------------------------|
| `resolveMediaIdsToPaths()` | `PortfolioController.php` | ✅ Yes — all portfolio media migrated in ADR-005 |
| `normalizeMediaPath()` | `HomeController.php` | ✅ Yes — all flat `general_settings` columns migrated in ADR-005 |
| `normalizeMediaPath()` | `AppearanceController.php` | ❌ **No** — still required for `logo_override` in `header_variant_settings` and `footer_variant_settings` JSON (deferred scope) |
| `normalizeMediaPathList()` | `AppearanceController.php` | ❌ **No** — still required for `payment_logos` in `footer_variant_settings` JSON (deferred scope) |

`HomeController.php` has its own independent copy of `normalizeMediaPath()` at line 874.
It is called 8 times (lines 309, 449–455, 558) to write the 7 logo/favicon path columns.
This copy must be updated in Phase 2 (dual-write) and deleted in Phase 4 alongside the
`PortfolioController` method. It is a separate method from the one in `AppearanceController`.

`AppearanceController.normalizeMediaPath()` and `normalizeMediaPathList()` survive Phase 4
and are deleted only after the follow-up JSON variant media migration is complete. Until then,
they remain as the write path for `logo_override` and `payment_logos` inside the JSON blobs.

Additionally, the inline `$resolveImageUrl` closure in
`resources/views/components/template/sections/our_work_showcase.blade.php`
must be replaced with `SectionFrontendMediaResolver::resolve()`.

**Blast radius for Blade views (from code audit):** 6 views reference portfolio images
and must be updated in Phase 4:

| File | Read used |
|------|-----------|
| `resources/views/components/template/sections/home-works.blade.php` | `asset('storage/' . $work->default_image)` |
| `resources/views/components/template/sections/our_work_showcase.blade.php` | `$resolveImageUrl($portfolio->default_image)` |
| `resources/views/components/template/sections/works.blade.php` | `asset('storage/' . $work->default_image)` |
| `resources/views/dashboard/portfolios/_form.blade.php` | `$portfolio->default_image` |
| `resources/views/dashboard/portfolios/index.blade.php` | `asset('storage/' . $portfolio->default_image)` |
| `resources/views/front/pages/portfolio.blade.php` | `asset('storage/' . $portfolio->default_image)` |

**Additional PHP read path:** `app/Support/Sections/SectionQueryResolver.php:371` reads
`$portfolio->default_image` via `self::portfolioImageUrl()`. This is a PHP support class,
not a Blade view, but it must also be updated in Phase 4. It drives portfolio image rendering
for all Section Engine-driven portfolio sections.

**Blast radius for general_settings logo:** Referenced in `HomeController.php`
(reads at lines 72–78, 141–147) and multiple layout Blade files (front/layouts/footers/*,
front/layouts/headers/*, dashboard/layouts/partials/head.blade.php,
dashboard/layouts/partials/nav.blade.php, dashboard/pages/sections/layouts/workspace.blade.php,
etc.). All references must switch from `$setting->logo` to `$setting->logoMedia?->url`
or equivalent after Phase 4.

### Rollback Complexity

**Low for Phases 1–3.** The new `*_media_id` columns are additive. If Phase 3
(backfill) produces wrong data, truncating the new ID columns and re-running the
backfill is straightforward. The old path columns remain intact as the read source
throughout Phases 1–3.

**Medium for Phase 4.** Once reads switch to ID columns, rolling back requires
reinstating path-based reads in all controllers and views. The old path columns
are still present at this point (not yet dropped), so a code-only rollback restores
full functionality.

**Irreversible after Phase 5.** Dropping the old path columns removes the only
path-based fallback. Do not execute Phase 5 until Phase 4 has been stable for at
least one release cycle (minimum 2 weeks in production).

### Estimated Effort

| Task | Effort |
|------|--------|
| Pre-backfill audit SQL and remediation (if needed) | 2–4 hours |
| Phase 1 migration (add FK columns) | 2 hours |
| Phase 2 dual-write controller updates | 3 hours |
| Phase 3 backfill command + verification | 3 hours |
| Phase 4 read-switch: controllers (PortfolioController + HomeController) + 6 Blade views + SectionQueryResolver + general_settings layouts | 7–9 hours |
| Phase 4 delete PortfolioController::resolveMediaIdsToPaths() + HomeController::normalizeMediaPath() + simplify SectionMediaPreviewBuilder | 2 hours |
| Phase 5 migration (drop old columns) | 1 hour |
| Testing (logo render, portfolio images, admin media picker) | 3 hours |
| **Total** | **~22–25 hours across multiple deploys** |

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
ADR-006 (Feedbacks Rename)
│
│  No technical dependency — recommended first by risk profile
│  Cleanest rename; zero data transformation
│  Eliminates DB/PHP naming mismatch before subsequent migrations
│
▼
ADR-005 (Media Unification)
│
│  No technical dependency on ADR-006
│  Recommended second: no financial risk; visible failures (broken images)
│  Testimonials already Pattern A — confirmed stable before portfolio migration
│
▼
ADR-003 (Integer Cents)
   │
   └── No technical dependency on ADR-005 or ADR-006
       Recommended last: highest risk; requires billing cycle monitoring
       Coupon application (currently $0) unlocks only after this is complete
```

**Summary:** The three ADRs have no hard technical dependencies on each other.
The order is determined entirely by risk escalation. If business priorities require
a different order, ADR-003 can technically be executed before ADR-005 — but ADR-003
should never be executed before ADR-006, because ADR-006 is so low-risk that there
is no justification for deferring it.

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

- [ ] `app/Livewire/Admin/Testimonials.php` is in the update scope (not just the 3 files in ADR-006)
- [ ] No raw SQL in the application references `feedbacks` or `feedback_translations` directly
  (confirmed: code audit found only Eloquent/ORM usage)
- [ ] No stored procedures or DB views reference `feedbacks` (MySQL: `SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW'`)
- [ ] The down migration is implemented and tested on Staging before running up on production

**For ADR-005 specifically:**

- [ ] Pre-backfill audit SQL run:
  `SELECT COUNT(*) FROM portfolios WHERE default_image IS NOT NULL AND default_image NOT IN (SELECT file_path FROM media)`
- [ ] Count is zero OR orphaned paths are documented and a remediation plan is in place
- [ ] Same audit run for `general_settings.logo` (and the other 6 columns)
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
- [ ] Livewire admin testimonials component loads and updates without error
- [ ] `docs/03-database-architecture.md` updated: `feedbacks` → `testimonials`
- [ ] `docs/29-content-showcase.md` updated: critical finding removed

### ADR-005 Validation

After Phase 3 (backfill), before switching reads:

- [ ] `SELECT COUNT(*) FROM portfolios WHERE default_image IS NOT NULL AND default_image_media_id IS NULL` = 0
- [ ] Spot-check: 10 random portfolio `default_image_media_id` values resolve to correct URLs

After Phase 4 (read switch):

- [ ] Admin portfolio list renders all images correctly
- [ ] Public portfolio section renders all images correctly
- [ ] Admin general settings page shows all logo/favicon images correctly
- [ ] Public site header/footer logos render correctly
- [ ] Media picker in portfolio form correctly pre-selects the current image
- [ ] `resolveMediaIdsToPaths()` has been deleted from `PortfolioController`
- [ ] `normalizeMediaPath()` and `normalizeMediaPathList()` have been deleted from `AppearanceController`
- [ ] `SectionMediaPreviewBuilder` string branch removed (only numeric ID branch remains)

After Phase 5 (drop old columns):

- [ ] `DESCRIBE portfolios` shows no `default_image` or `images` columns
- [ ] `DESCRIBE general_settings` shows no `logo`, `dark_logo`, `sticky_logo`,
  `dark_sticky_logo`, `admin_logo`, `admin_dark_logo`, or `favicon` columns

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

### Sprint 2 — ADR-005 Phase 0 + Phases 1–3 (1 week)

| Day | Task |
|-----|------|
| Day 1–2 | **Phase 0 — Media Audit:** run all checks on Staging clone; produce report table |
| Day 2–3 | Remediate any Missing Paths (re-register or re-upload); re-run audit until `Missing Paths = 0` |
| Day 4 | Phase 1 migration (add FK columns) on Staging |
| Day 5 | Phase 2 dual-write controller updates |
| Day 6 | Deploy Phase 1 + 2 to production |
| Day 7 | Phase 3 backfill command run on Staging; backfill verification; deploy to production |

**Exit criterion:** Phase 0 report shows `Missing Paths = 0`. All portfolio and
`general_settings` FK columns populated and verified. Old path columns still in place
(safe point to pause).

---

### Sprint 3 — ADR-005 Phase 4 (Read Switch) (3–4 days)

| Day | Task |
|-----|------|
| Day 1–2 | Phase 4: switch reads in controllers and 5 Blade views to use `*_media_id` columns |
| Day 3 | Phase 4: delete `resolveMediaIdsToPaths()`, `normalizeMediaPath()`, `normalizeMediaPathList()` |
| Day 3 | Phase 4: simplify `SectionMediaPreviewBuilder` (remove string branch) |
| Day 4 | Full validation: admin portfolio, public portfolio, logos, favicon, media picker |

**Exit criterion:** All Phase 4 validation checks passed. No path-based read code
remains in controllers or Blade views. Conversion methods deleted.

---

### Stability Window — 1–2 Weeks

Monitor production after Phase 4. Confirm no broken images, no error logs related to
media resolution. Do not proceed to Phase 5 until the system has been stable for a
minimum of one full week.

**Gate:** If any broken image is reported during this window, pause and investigate
before Phase 5.

---

### Sprint 4 — ADR-005 Phase 5 (Drop Old Columns) (1 day)

| Task | When |
|------|------|
| Phase 5 migration: drop `default_image`, `images` from `portfolios` | Day 1 |
| Phase 5 migration: drop 7 path columns from `general_settings` | Day 1 |
| Post-drop validation: confirm `DESCRIBE` output, confirm no application errors | Day 1 |

**Exit criterion:** Old path columns dropped. ADR-005 fully closed. Media system
is now exclusively ID-based for all flat columns.

---

### Sprint 5 — ADR-003 Phase 0 + Phases 1–2 (1 week)

> **Note:** ADR-005 Phase 5 must be complete before this sprint begins. Financial
> migrations must not run in parallel with schema-cleanup migrations.

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
ADR-006
  ↓
ADR-005 Phase 0 (Media Audit) → Phase 1–3 → Phase 4 → [Stability Window] → Phase 5
  ↓
ADR-003 Phase 0 (Money Audit) → Phase 1–2 → [30-day Monitoring] → Phase 3–4
```

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
