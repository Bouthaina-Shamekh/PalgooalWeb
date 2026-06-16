# ADR Execution Readiness Review

> **Purpose:** Final pre-execution architecture review of `ADR_IMPLEMENTATION_PLAN.md`.  
> **Method:** Live code audit against the plan's claimed scope.  
> **Date:** 2026-06-16  
> **Scope:** ADR-006, ADR-005, ADR-003 — all phases.

---

## Executive Verdict

**ADR-006 — READY WITH MINOR ACTIONS**

The plan has the right structure but is incomplete in its file-change list. Two public-facing
Blade section components that directly read the renamed `->feedback` column are entirely absent
from the plan's scope. These will break testimonial rendering on the live site immediately after
the migration runs. The fixes are small (two one-line changes), but they must be added to Sprint 1
before the first commit is made.

**ADR-005 — READY WITH MINOR ACTIONS**

One undocumented controller copy of `normalizeMediaPath()` and a sixth portfolio Blade view were
found. More significantly, the plan's Phase 4 "delete conversion methods" step is logically
incorrect for `AppearanceController.php` — those methods are still required for deferred JSON
columns and cannot be deleted in Phase 4. This must be clarified before Phase 2 begins.

**ADR-003 — NOT READY**

The plan identifies `SubscriptionController` as the subscription write path and `CouponController`
as the coupon write path. A code audit found three additional subscription write paths
(`OrderActivationService`, `CheckoutController`, `ServerController`) that are absent from the
dual-write plan. More critically, **there is no `CouponController` in the codebase at all** —
the plan's Phase 2 coupon work references a file that does not exist. ADR-003 requires a revised
scope before execution begins.

---

## ADR-006 Review

### Verified Correct in Plan

| Item | Status |
|------|--------|
| `app/Models/Testimonial.php` — remove `$table = 'feedbacks'` | ✅ Correct |
| `app/Models/TestimonialTranslation.php` — remove `$table`, update `$fillable` | ✅ Correct |
| `app/Http/Controllers/Admin/TestimonialsController.php` — listed | ✅ Correct (partial, see below) |
| `app/Http/Controllers/Front/TestimonialSubmissionController.php` — listed | ✅ Correct (partial, see below) |
| `app/Livewire/Admin/Testimonials.php` — ⚠ noted as additional file | ✅ Noted |
| `app/Support/Sections/SectionQueryResolver.php:388` — `->feedback` → `->text` | ✅ Correct |
| No `FormRequest` class for testimonials (validation is inline) | ✅ Confirmed — nothing to update |
| `app/Policies/TestimonialPolicy.php` — no feedback references | ✅ Confirmed clean |
| No routes file references to `feedbacks` | ✅ Confirmed clean |
| No seeders reference `feedbacks` | ✅ Confirmed clean |
| No test files test testimonials | ✅ Confirmed — no tests to update |

---

### Finding 1 — Two public Blade components completely missing from scope

**Severity: HIGH — will cause immediate runtime failure on live site after migration.**

Both files directly read `$translation->feedback` or `$translation?->feedback` from the
`testimonial_translations` table. After the column is renamed to `text`, they will return
empty strings silently — no exception, but all testimonial text will disappear from the
public website.

| File | Line | Code | Required Change |
|------|------|------|-----------------|
| `resources/views/components/template/sections/reviews_showcase.blade.php` | 40 | `'text' => $translation->feedback ?? ''` | → `'text' => $translation->text ?? ''` |
| `resources/views/components/template/sections/testimonials.blade.php` | 44 | `$translation?->feedback ?? ''` | → `$translation?->text ?? ''` |

These files are not listed anywhere in the plan. They must be added to the Sprint 1 Day 1
task list alongside the controller and model changes.

---

### Finding 2 — Livewire Blade view also reads `->feedback` (companion to the noted PHP file)

**Severity: MEDIUM — Livewire admin testimonial management will break.**

The plan notes `app/Livewire/Admin/Testimonials.php` as an additional file requiring update,
and lists its PHP-side references. It does not mention the companion Blade view:

| File | Line | Code | Required Change |
|------|------|------|-----------------|
| `resources/views/livewire/admin/testimonials.blade.php` | 137 | `$testimonial->translation()?->feedback` | → `->text` |
| `resources/views/livewire/admin/testimonials.blade.php` | 234 | `wire:model="testimonialTranslations.{{ $index }}.feedback"` | → `.text` (matches PHP component key) |

---

### Finding 3 — Plan lists `feedback_id` change for controllers but omits `feedback` column writes

**Severity: HIGH — subscription controller writes will fail with column not found.**

The plan's "Affected Controllers and Services" table shows only `'feedback_id' → 'testimonial_id'`
for `TestimonialsController.php` and `TestimonialSubmissionController.php`. In reality, both
files also write to the `feedback` column, which is being renamed to `text`.

**`TestimonialsController.php` — lines requiring change:**

| Line | Code | Required Change |
|------|------|-----------------|
| 123 | `'feedback' => $translation['feedback']` | → `'text' => $translation['feedback']` |
| 165 | `$trans?->feedback` | → `$trans?->text` |
| 224 | `'feedback' => $translation['feedback']` | → `'text' => $translation['feedback']` |
| 385 | `'feedback' => $feedback` | → `'text' => $feedback` |

**`TestimonialSubmissionController.php` — line requiring change:**

| Line | Code | Required Change |
|------|------|-----------------|
| 82 | `'feedback' => $validated['feedback']` | → `'text' => $validated['feedback']` |

Note: The POST field `name="feedback"` in the frontend form and the request array keys like
`$translation['feedback']` do NOT need to change — they are arbitrary form field names, not
DB column references. Only the DB insert/update keys need to change to `'text'`.

---

### Finding 4 — `app/Livewire/Admin/Testimonials.php` — changes not fully enumerated

**Severity: LOW — the file was already flagged; this adds specificity.**

The plan notes the file exists and lists 4 line references, but does not specify the full
set of changes. For completeness:

| Line | Code | Required Change |
|------|------|-----------------|
| 53 | `'feedback' => ''` (form state reset) | → `'text' => ''` |
| 98 | `$trans?->feedback` (DB read) | → `$trans?->text` |
| 126 | `testimonialTranslations.*.feedback` (validation key) | → `*.text` |
| 145 | `'feedback_id' => $testimonial->id` | → `'testimonial_id' => ...` ← already in plan |
| 146 | `'feedback' => $translation['feedback']` (DB write) | → `'text' => $translation['text']` |

---

### ADR-006 Summary: Complete File Change List

After applying the above corrections, the full list of files to change in Sprint 1 is:

```
app/Models/Testimonial.php                                        (remove $table override)
app/Models/TestimonialTranslation.php                             (remove $table, update $fillable)
app/Http/Controllers/Admin/TestimonialsController.php             (feedback_id + feedback column)
app/Http/Controllers/Front/TestimonialSubmissionController.php    (feedback_id + feedback column)
app/Livewire/Admin/Testimonials.php                               (feedback_id + feedback + text)
app/Support/Sections/SectionQueryResolver.php                     (->feedback → ->text)
resources/views/livewire/admin/testimonials.blade.php             (->feedback + wire:model key)
resources/views/components/template/sections/reviews_showcase.blade.php   ← NEW
resources/views/components/template/sections/testimonials.blade.php       ← NEW
```

**Migration scope is correct** (drop FK → rename tables → rename columns → recreate FK).
**Down migration** must handle the partial state: tables renamed, FK absent — already noted in plan.

---

## ADR-005 Review

### Verified Correct in Plan

| Item | Status |
|------|--------|
| `portfolios.default_image` (string) → `default_image_media_id` FK | ✅ Correct |
| `portfolios.images` (JSON path array) → `images_media_ids` (JSON ID array) | ✅ Correct |
| 7 `general_settings` path columns → `*_media_id` FKs | ✅ Correct |
| `feedbacks.image_id` (now `testimonials`) — already Pattern A | ✅ Confirmed |
| `section_definitions.preview_media_id` — already Pattern A | ✅ Confirmed |
| `PortfolioController.resolveMediaIdsToPaths()` — listed for deletion | ✅ Listed |
| `AppearanceController.normalizeMediaPath()` and `normalizeMediaPathList()` — listed | ✅ Listed (see caveat below) |
| Blast radius: `home-works.blade.php`, `our_work_showcase.blade.php`, `works.blade.php`, `dashboard/portfolios/_form.blade.php`, `dashboard/portfolios/index.blade.php` | ✅ All 5 confirmed present |
| Phase 0 Media Audit required before Phase 1 | ✅ Correct gate |

---

### Finding 5 — `HomeController.php` has its own `normalizeMediaPath()` — not in plan

**Severity: HIGH — will silently continue Pattern C conversion after AppearanceController is updated.**

`app/Http/Controllers/Admin/HomeController.php` contains an independent private copy of
`normalizeMediaPath()` at line 874. It is called 8 times (lines 309, 449–455, 558) to write
the 7 logo/favicon columns to `general_settings`.

The plan lists `AppearanceController.php` and `PortfolioController.php` for this deletion but
does not mention `HomeController.php` at all.

**Files requiring Phase 4 updates (corrected list):**

| File | Conversion Methods | Also reads path columns |
|------|-------------------|------------------------|
| `app/Http/Controllers/Admin/PortfolioController.php` | `resolveMediaIdsToPaths()` × 2 | ✅ in plan |
| `app/Http/Controllers/Admin/AppearanceController.php` | `normalizeMediaPath()`, `normalizeMediaPathList()` | ✅ in plan (see Finding 6) |
| `app/Http/Controllers/Admin/HomeController.php` | `normalizeMediaPath()` at line 874 | ❌ NOT in plan |

`HomeController.php` also reads the logo columns at lines 72–78 and 141–147 to pass to views.
These reads must also switch from `->logo` to `->logoMedia?->url` (or equivalent) in Phase 4.

---

### Finding 6 — Plan's Phase 4 "delete conversion methods" is wrong for `AppearanceController`

**Severity: HIGH — this is a logical contradiction in the plan that will cause a Phase 4 regression.**

The plan states under "Controllers / Code to Delete After Phase 4":

> `normalizeMediaPath()` and `normalizeMediaPathList()` in `AppearanceController.php`

But `AppearanceController.php` still uses both methods for the **deferred JSON variant columns**:
- Line 190: `'logo_override' => $this->normalizeMediaPath(...)` — inside `header_variant_settings` JSON
- Line 408: `'logo_override' => $this->normalizeMediaPath(...)` — inside `footer_variant_settings` JSON
- Line 409: `'payment_logos' => $this->normalizeMediaPathList(...)` — inside `footer_variant_settings` JSON

These JSON columns are explicitly deferred to **Option B** (a follow-up migration after
ADR-005 Phases 1–5). This means `normalizeMediaPath()` and `normalizeMediaPathList()` in
`AppearanceController.php` **cannot be deleted in Phase 4**. They must remain until the
JSON variant migration is done.

**Correct Phase 4 deletion scope:**
- `PortfolioController.resolveMediaIdsToPaths()` — can be deleted ✅
- `HomeController.normalizeMediaPath()` — can be deleted (once logo reads switch to IDs) ✅
- `AppearanceController.normalizeMediaPath()` — **cannot** be deleted yet ❌
- `AppearanceController.normalizeMediaPathList()` — **cannot** be deleted yet ❌

---

### Finding 7 — Sixth portfolio Blade view missing from plan

**Severity: MEDIUM — public portfolio page will show broken images after Phase 4.**

The plan says "5 views reference portfolio images" and lists them. A code audit found a sixth:

| File | Line | Code |
|------|------|------|
| `resources/views/front/pages/portfolio.blade.php` | 43 | `asset('storage/' . $portfolio->default_image)` |

This view renders individual portfolio items on the public front end. It must be updated in
Phase 4 alongside the other 5 views.

---

### Finding 8 — `SectionQueryResolver.php` reads `default_image` (PHP, not Blade)

**Severity: MEDIUM — section-driven portfolio sections will use stale path data after Phase 4.**

The plan's Phase 4 blast radius lists "5 Blade views." It does not mention:

`app/Support/Sections/SectionQueryResolver.php:371` — `'image' => self::portfolioImageUrl($portfolio->default_image ?? null)`

This support class is called by the Section Engine when rendering portfolio-type sections.
After Phase 4 switches to ID-based reads, this method must also switch to use
`default_image_media_id`. It is a PHP read path, not a Blade view, but functionally
equivalent in terms of Phase 4 impact.

---

### Finding 9 — `SectionMediaPreviewBuilder` string branch remains safe as-is

**Note only — no action required.**

The plan says to remove the string branch from `SectionMediaPreviewBuilder`. The current
implementation at `app/Support/Sections/SectionMediaPreviewBuilder.php:19` handles string
values for backward compatibility. After ADR-005 is complete, all callers will pass numeric
IDs. The string branch can be removed in Phase 4 as planned — no conflict found.

---

### ADR-005 Summary

Phase 0 (Media Audit) gate is correct and must not be skipped. The execution plan is
structurally sound. Three additions are required before Phase 1 begins:
1. Add `HomeController.php` to the Phase 4 controller update list
2. Add `front/pages/portfolio.blade.php` to the Phase 4 Blade view list
3. Add `SectionQueryResolver.php` to the Phase 4 PHP read-path list
4. Clarify that `AppearanceController.normalizeMediaPath()` survives Phase 4 (cannot be deleted until JSON column migration)

---

## ADR-003 Review

### Verified Correct in Plan

| Item | Status |
|------|--------|
| `subscriptions.price` (`decimal(10,2)`, `float` cast) → `price_cents` | ✅ Correct |
| `coupons.discount_value` (`decimal(10,2)`) → `discount_value_cents` + `discount_percent` | ✅ Correct |
| `domain_tld_prices.cost/sale` — deferred | ✅ Correct |
| `templates.price/discount_price` (`float` cast) — deferred | ✅ Correct |
| `plans.monthly_price_cents/annual_price_cents` — already cents | ✅ Confirmed |
| `invoices.*_cents` columns — already cents | ✅ Confirmed |
| `invoice_items.unit_price_cents/total_cents` — already cents | ✅ Confirmed |
| `order_items.price_cents` with `integer` cast — already cents | ✅ Confirmed |
| `ROUND(price * 100)` backfill formula | ✅ Correct |
| Pre-backfill CSV export and reconciliation SQL | ✅ Correct |
| 30-day dual-write monitoring | ✅ Correct |

---

### Finding 10 — Three additional subscription write paths missing from Phase 2 dual-write scope

**Severity: CRITICAL — the 30-day monitoring count will never reach zero without these.**

The plan's Phase 2 says: "update `SubscriptionController` (store, update, renewal paths)."

A code audit found **three additional write paths** that create `Subscription` records with
a `price` value. None is in the plan:

| File | Line | Code | Price Written |
|------|------|------|---------------|
| `app/Services/Billing/OrderActivationService.php` | 210 | `'price' => $template->price` | Template's decimal price |
| `app/Http/Controllers/Front/CheckoutController.php` | 352 | `'price' => $config['unit_cents'] / 100` | Cents converted to decimal |
| `app/Http/Controllers/Admin/Management/ServerController.php` | 127 | `'price' => 0` | Hardcoded zero |

All three must be updated in Phase 2 to also write `price_cents`:
- `OrderActivationService.php:210` → add `'price_cents' => (int) round($template->price * 100)`
- `CheckoutController.php:352` → add `'price_cents' => (int) $config['unit_cents']`
- `ServerController.php:127` → add `'price_cents' => 0`

If these paths are not updated, any subscription created through an order activation or
frontend checkout during the 30-day monitoring window will have `price_cents IS NULL`,
and the mandatory monitoring check will always report non-zero counts. This would force
the 30-day clock to restart — or worse, the discrepancy would go unnoticed.

---

### Finding 11 — No `CouponController` exists in the codebase

**Severity: HIGH — Phase 2 dual-write for coupons references a file that does not exist.**

The plan states: "Phase 2 dual-write for coupons (CouponController) — 2 hours."

A full search of `app/Http/Controllers/**/*.php` and `routes/**/*.php` found **no
`CouponController`**. The `app/Models/Coupon.php` model exists, but there is no CRUD
controller, no route for coupon management, and no existing write path for
`discount_value` beyond the initial seeded or database-level data.

**Implication:** The plan's Phase 2 effort for coupons (2 hours) is based on updating a
controller that does not exist. The actual scope for coupon dual-write is:
1. Determine whether `discount_value` is ever written at runtime (create, update), or only seeded
2. If runtime writes exist, they are not in controllers — they may be in Artisan commands, seeders, or direct Eloquent calls
3. Build the write paths from scratch as part of Phase 2, not just update an existing controller

This is underscoped, not overscoped. The 2-hour estimate for "updating CouponController"
should be replaced with a research task: "Locate all coupon write paths, then dual-write
each one."

---

### Finding 12 — `templates.rating` and `templates.rating_avg` are decimal but not money

**Informational only — but must be explicitly documented in Phase 0 Money Audit.**

`Template.php` has:
- `$casts = ['rating' => 'float']` → `float` column, a star rating (0–5)
- Migration `2025_08_11` adds `rating_avg decimal(3,2)` → a rating average

Neither is a money column. The Phase 0 Money Audit table must include a row for each with
`ADR-003 Impact = Not Money — excluded`. Without this explicit exclusion, a future developer
auditing the `decimal` columns could mistakenly include them.

---

### Finding 13 — `plan->discount_price` reference in CheckoutController

**Informational — likely already null; confirm during Phase 0.**

`CheckoutController.php:162` references `$plan->discount_price`. The `Plan` model has no
`discount_price` column (it has `monthly_price_cents` and `annual_price_cents`). This will
silently return `null` due to Eloquent's `__get()` fallback. This is not an ADR-003 issue,
but it is a latent bug in the checkout flow that should be noted during the Money Audit.

---

### ADR-003 Summary

ADR-003 is **NOT READY** for execution. Before Sprint 5 begins:
1. Phase 2 dual-write scope must be expanded to include `OrderActivationService`, `CheckoutController`, and `ServerController`
2. The "CouponController" line item must be researched: locate actual coupon write paths or confirm `discount_value` is never written at runtime
3. `templates.rating` and `templates.rating_avg` must be explicitly excluded from the Money Audit table

---

## Rollback Review

| Sprint | Plan's Rollback | Actual Assessment |
|--------|----------------|-------------------|
| Sprint 1 — ADR-006 | Down migration reverts renames; restore `$table` overrides | ✅ Correct. MySQL RENAME TABLE is atomic. Down migration must handle partial state (tables renamed, FK absent) — already noted in plan. |
| Sprint 2 — ADR-005 Phase 0–3 | Drop `*_media_id` columns; revert dual-write controller changes | ✅ Correct. Old path columns untouched throughout. |
| Sprint 3 — ADR-005 Phase 4 | Revert read-switch; restore conversion methods | ⚠ Partially correct. `AppearanceController` conversion methods were never fully deleted (Finding 6), so "restore" does not apply for that file. For `HomeController` and `PortfolioController`, revert is straightforward. |
| Sprint 4 — ADR-005 Phase 5 | **Irreversible** | ✅ Correct. Explicit Stability Window gate is correct. |
| Sprint 5 — ADR-003 Phase 0–2 | Drop `price_cents`, `discount_value_cents`, `discount_percent` | ✅ Correct. `price` is still the read source. Note: with 3 additional write paths now in scope, dropping the cents columns is still safe (they were additive). |
| Sprint 6 — ADR-003 Phase 3–4 | Phase 4 **Irreversible** after `price` drop | ✅ Correct. The down migration must compute `price = price_cents / 100`. |

**One rollback gap identified:** The plan does not mention taking a pre-drop backup of
`default_image` / `images` / `logo` columns before Phase 5. Best practice before an
irreversible column drop is: `CREATE TABLE portfolios_path_backup AS SELECT id, default_image, images FROM portfolios`. This takes seconds and provides a safety net if data corruption is discovered post-drop. Not in the plan.

---

## Validation Review

### Current validation checklists are generally solid. Gaps found:

**ADR-006 validation — missing items:**
- [ ] `reviews_showcase.blade.php` renders testimonial text correctly (public section)
- [ ] `testimonials.blade.php` section component renders testimonial text correctly (public section)
- [ ] Livewire admin testimonials Blade view loads and `wire:model` bindings work

**ADR-005 validation — missing items:**
- [ ] `HomeController` general_settings page: logo/favicon inputs show current values correctly (Phase 4)
- [ ] `front/pages/portfolio.blade.php` renders portfolio images correctly (Phase 4) — the plan's checklist says "public portfolio section" but this refers to the section component; the standalone portfolio page is separate
- [ ] `SectionQueryResolver` portfolio section renders image correctly (Phase 4)

**ADR-003 validation — missing items:**
- [ ] Create subscription via frontend checkout → `price_cents` populated
- [ ] Activate an order via `OrderActivationService` → subscription `price_cents` populated
- [ ] Provision a subscription via `ServerController` → `price_cents = 0` confirmed
- [ ] During 30-day window: run count check for each write path separately, not just the aggregate

---

## Hidden Assumptions

The following assumptions are embedded in the plan but not stated explicitly. Each one
represents a condition that, if false, would invalidate a phase or require replanning.

| # | Assumption | Confidence | Action if Wrong |
|---|-----------|-----------|-----------------|
| A1 | All `feedback*` column reads in the application go through Eloquent (no raw SQL) | **High** — confirmed by code audit | No raw SQL found in routes, controllers, or services |
| A2 | `media.file_path` values match the strings stored in `portfolios.default_image` and `general_settings.logo` | **Unknown** — this is what Phase 0 measures | Run the Phase 0 audit query before any Phase 1 work |
| A3 | `SubscriptionController` is the only write path for `subscriptions.price` | **FALSE** — contradicted by Finding 10 | Expand Phase 2 scope before starting |
| A4 | A `CouponController` exists and can be updated for dual-write | **FALSE** — no such controller exists | Research actual coupon write paths before Phase 5 |
| A5 | Staging environment has real production data | **Unverified** — plan requires it but does not enforce it | Explicitly verify and document before each Phase 0 audit |
| A6 | `AppearanceController.normalizeMediaPath()` can be deleted after Phase 4 | **FALSE** — still used by deferred JSON columns | These methods survive Phase 4; delete only after JSON migration |
| A7 | No MySQL views or stored procedures reference `feedbacks` | **High confidence** — plan checklist includes this check | Run `SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW'` before migration |
| A8 | `Plan.discount_price` attribute in CheckoutController is safely null | **Likely** — Plan model has no such column | Confirm during Phase 0 Money Audit |
| A9 | The test suite is not affected by any ADR | **True** — no tests cover testimonials, media storage, or money columns | No action needed |
| A10 | Backfill formula `ROUND(price * 100)` is safe for all existing `decimal(10,2)` values | **High** — decimal storage precludes floating-point rounding errors | Verify with `SELECT price, ROUND(price * 100), price * 100 FROM subscriptions LIMIT 20` before running |

---

## Final Verdict

### 1. Is ADR-006 ready for execution today?

**Yes, with two pre-commit additions.**

Before writing the first line of the migration, add to the Sprint 1 Day 1 task list:
- `resources/views/components/template/sections/reviews_showcase.blade.php` — `->feedback` → `->text`
- `resources/views/components/template/sections/testimonials.blade.php` — `->feedback` → `->text`

Also ensure the complete controller changes are understood: both files need the
`'feedback' => ...` *column write* changed to `'text' => ...`, not just the `feedback_id`
column rename. This distinction is understated in the current plan.

Total additional effort: approximately 30 minutes. Sprint 1 is otherwise correctly scoped.

---

### 2. Is ADR-005 ready after Media Audit?

**Yes, after three clarifications are made to the plan before Phase 1 begins:**

1. Add `HomeController.php` to the Phase 4 controller list (its own copy of `normalizeMediaPath()`)
2. Add `front/pages/portfolio.blade.php` to the Phase 4 Blade view list (6th portfolio view, not 5th)
3. Revise the Phase 4 "delete conversion methods" note: `AppearanceController.normalizeMediaPath()` and `normalizeMediaPathList()` survive Phase 4 and are deleted only after the follow-up JSON variant migration

These are documentation clarifications, not scope expansions. No additional engineering work.

---

### 3. Is ADR-003 ready after Money Audit?

**No.** Two blockers require resolution before Sprint 5 begins:

**Blocker 1 (Critical):** Three subscription write paths outside `SubscriptionController` must
be added to the Phase 2 dual-write scope:
- `OrderActivationService.php` (subscription created on order activation)
- `CheckoutController.php` (subscription created at frontend checkout)
- `ServerController.php` (subscription created at server provisioning)

Without these, `price_cents` will be NULL for a large class of subscriptions and the 30-day
monitoring count will never clear.

**Blocker 2 (High):** There is no `CouponController`. The Phase 2 coupon line item must be
replaced with a discovery task: "Locate all paths that write `coupons.discount_value` at
runtime and add dual-write to each." This may turn out to be a shorter task (if coupons are
only created via seeder/admin tooling) or a longer one (if there's a hidden CRUD interface).

Estimated additional effort to resolve blockers: 2–4 hours of scoping, then the implementation
effort is unchanged.

---

### 4. What is the last thing to do before writing the first migration?

**For ADR-006 (first migration):**

Add the two missing Blade component files to the Sprint 1 Day 1 checklist. Verify that all
`'feedback' =>` DB writes in both `TestimonialsController.php` and
`TestimonialSubmissionController.php` are also in scope (not just the `feedback_id` rename).

Then: run `SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW'` on the production database to confirm
no MySQL views reference `feedbacks`. This check is already in the plan's pre-execution
checklist — it just needs to be run.

After those two steps, Sprint 1 can begin.
