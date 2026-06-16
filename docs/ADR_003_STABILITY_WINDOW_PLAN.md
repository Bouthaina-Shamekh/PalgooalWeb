# ADR-003 Stability Window Plan

**Date:** 2026-06-16  
**Status:** 🟡 STABILITY WINDOW ACTIVE — awaiting 7-day observation period  
**Scope:** `templates.price` · `templates.discount_price` · `subscriptions.price`  
**Purpose:** Define what to monitor, what gates must pass, and exactly what Phase 3 will drop

---

## Pre-Condition Verification

| Check | Result | Notes |
|-------|--------|-------|
| `templates.price_cents IS NULL` count | ✅ 0 | Confirmed by user |
| `subscriptions.price_cents IS NULL` count | ✅ 0 | Confirmed by user |
| `templates` mismatch count | ✅ 0 | `price_cents != ROUND(price * 100)` = 0 rows |
| `subscriptions` mismatch count | ✅ 0 | Same |
| Template model helpers present | ✅ Confirmed | `resolvedPriceCents()` / `resolvedDiscountPriceCents()` / `resolvedPrice()` / `resolvedDiscountPrice()` all in `app/Models/Template.php` |
| Subscription model helpers present | ✅ Confirmed | `resolvedPriceCents()` / `resolvedPrice()` in `app/Models/Tenancy/Subscription.php` |
| All billing paths on cents | ✅ | `CheckoutController` + `OrderActivationService` read `resolvedPriceCents()` only |

**Stability Window Start Date: 2026-06-16**  
**Earliest Phase 3 Review Date: 2026-06-23 (7 days)**

---

## 1. Legacy Column Usage Audit

> Full codebase scan of `app/`, `resources/`, `routes/`, `database/` — all `.php` and `.blade.php` files.  
> Date: 2026-06-16

### 1A — `templates.price`

| File | Line | Usage | Classification | Impact |
|------|------|-------|----------------|--------|
| `resources/views/front/pages/checkout.blade.php` | 18 | `$basePrice = (float) ($template?->price ?? 0)` | **ACTIVE — display** | Display only. Billing price derived in `CheckoutController` via `resolvedPriceCents()`. Zero billing impact. |
| `resources/views/front/pages/checkout.blade.php` | 1045 | `TEMPLATE_FINAL_CENTS = (int)(($finalPrice ?? 0) * 100)` ← `$finalPrice` from `->price` | **ACTIVE — JS display** | JS UI variable only. Not submitted to server. Actual billed amount recomputed server-side from `resolvedPriceCents()`. |
| `resources/views/dashboard/templates/edit.blade.php` | 153 | `value="{{ old('price', $template->price) }}"` | **ACTIVE — form pre-fill** | Admin edit form. Pre-populates the price input. The submitted value is written back via dual-write controller. |

### 1B — `templates.discount_price`

| File | Line | Usage | Classification | Impact |
|------|------|-------|----------------|--------|
| `resources/views/front/pages/checkout.blade.php` | 19 | `$discRaw = $template?->discount_price ?? null` | **ACTIVE — display** | Display only. Discount eligibility recomputed in `CheckoutController` via `resolvedDiscountPriceCents()`. |
| `resources/views/dashboard/templates/edit.blade.php` | 160 | `value="{{ old('discount_price', $template->discount_price) }}"` | **ACTIVE — form pre-fill** | Admin edit form. Same note as `price` above. |
| `app/Http/Controllers/Front/CheckoutController.php` | 164 | `$discRawPlan = $plan->discount_price` | **DEAD CODE** | `plans` table has no `discount_price` column → `$plan->discount_price` is always `null` → `$showDiscountPlan = false`. No effect. |

### 1C — `subscriptions.price`

| File | Line | Usage | Classification | Impact |
|------|------|-------|----------------|--------|
| `resources/views/dashboard/management/subscriptions/edit.blade.php` | 4 | `$plansArray[$plan->id] = $plan->price ?? 0` | **DEAD CODE** | This reads `plan->price`, NOT `subscription->price`. The `plans` table has no `price` column → always `null` → JS auto-fill sets price to `0`. Pre-existing UX bug, not a billing issue. |

**Zero active reads of `subscriptions.price` remain.**  
All subscription price reads in the codebase use `$subscription->resolvedPrice()` or `$subscription->resolvedPriceCents()`.

### Summary

| Column | Active Reads | Dead Code Reads | Billing Impact |
|--------|-------------|-----------------|----------------|
| `templates.price` | 3 (2 display, 1 form pre-fill) | 0 | **None** |
| `templates.discount_price` | 2 (1 display, 1 form pre-fill) | 1 | **None** |
| `subscriptions.price` | **0** | 1 (wrong model) | **None** |

> **Billing is safe.** All active reads are display-only or admin form pre-fill. No active read feeds into order creation, invoice generation, or payment calculation.

---

## 2. Runtime Monitoring Checklist

Monitor these user flows daily during the 7-day window. Any pricing anomaly = pause Phase 3.

### Templates

| Flow | What to Verify | Watch For |
|------|---------------|-----------|
| **Template listing** (`/templates`) | Prices shown on cards match DB `price_cents / 100` | Wrong prices, zero prices, or PHP errors |
| **Template details** (`/templates/{slug}`) | Base price, discount price, discount percentage all correct | `resolvedPriceCents()` returning 0 for populated rows |
| **Template checkout** (`/checkout?template_id=X`) | Price on checkout page + JS total matches DB cents | `TEMPLATE_FINAL_CENTS` ≠ `price_cents / 100` (float drift) |
| **Template with discount** | Discount badge shown, crossed-out price correct, final price = discount | Discount logic using wrong source |
| **Template edit (admin)** | Pre-filled price and discount_price correct in edit form | Form shows stale value vs. DB |
| **Template create → save** | Dual-write saves both `price` + `price_cents`, no mismatch | `price_cents IS NULL` after create |

### Subscriptions

| Flow | What to Verify | Watch For |
|------|---------------|-----------|
| **Create subscription (admin)** | `price_cents` written, `resolvedPrice()` returns correct amount | NULL `price_cents` after create |
| **Update subscription (admin)** | Both `price` and `price_cents` updated together | Mismatch after update |
| **Activate order** (`OrderActivationService`) | Subscription created with correct `price_cents` from `template->resolvedPriceCents()` | Zero or wrong `price_cents` |
| **Subscription list** (dashboard/client) | Prices displayed via `resolvedPrice()` are correct | Displaying `0` or wrong amounts |
| **Invoice generation** | Invoice amounts match subscription `price_cents` | Invoice totals differ from subscription price |
| **Cancel / expire subscription** | No pricing side-effects | N/A (cancel doesn't recompute price) |

### SQL Monitoring Queries

Run these at the end of the 7-day window before Phase 3:

```sql
-- Must remain 0 throughout the window
SELECT COUNT(*) AS templates_null  FROM templates    WHERE price_cents IS NULL;
SELECT COUNT(*) AS subs_null       FROM subscriptions WHERE price_cents IS NULL;

-- Must remain 0 (new rows created during window must dual-write correctly)
SELECT COUNT(*) AS templates_mismatch
FROM templates
WHERE price_cents != ROUND(price * 100)
  AND price IS NOT NULL
  AND price_cents IS NOT NULL;

SELECT COUNT(*) AS subs_mismatch
FROM subscriptions
WHERE price_cents != ROUND(price * 100)
  AND price IS NOT NULL
  AND price_cents IS NOT NULL;

-- Spot-check: confirm recent rows dual-wrote
SELECT id, price, price_cents, created_at
FROM subscriptions
ORDER BY created_at DESC
LIMIT 10;

SELECT id, price, price_cents, discount_price, discount_price_cents, updated_at
FROM templates
ORDER BY updated_at DESC
LIMIT 10;
```

---

## 3. Phase 3 Readiness Gates

All 5 gates must be GREEN before Phase 3 may begin.

| Gate | Condition | Status |
|------|-----------|--------|
| **G1** — No NULL cents | `SELECT COUNT(*) FROM templates WHERE price_cents IS NULL` = 0 AND same for `subscriptions` | ✅ **PASSED** (2026-06-16) |
| **G2** — No mismatches | `price_cents != ROUND(price * 100)` = 0 rows in both tables | ✅ **PASSED** (2026-06-16) |
| **G3** — No active billing reads from legacy columns | Zero code paths that feed `templates.price` / `templates.discount_price` / `subscriptions.price` into order creation, invoicing, or payment | ✅ **FULLY MET** (2026-06-16) — D1/D2/D3 resolved; zero legacy reads in any view |
| **G4** — 7+ days stability | Minimum 7 calendar days since backfill with no checkout pricing incidents | ⏳ **PENDING** — countdown starts 2026-06-16 → review 2026-06-23 |
| **G5** — No production pricing errors | Zero exceptions or wrong-price reports related to template or subscription pricing in application logs | ⏳ **MONITOR** — check daily during window |

---

## 4. Phase 3 Scope

### Columns to DROP

```sql
-- In a single migration file (Phase 3):
ALTER TABLE templates    DROP COLUMN price;
ALTER TABLE templates    DROP COLUMN discount_price;
ALTER TABLE subscriptions DROP COLUMN price;
```

**Migration file name (suggested):** `2026_06_23_000001_drop_legacy_price_columns_phase3.php`

### Code to SIMPLIFY after column drop

Once the legacy columns no longer exist, the fallback branches in the helpers become dead code and should be removed:

**`app/Models/Template.php`** — `resolvedPriceCents()`:
```php
// Before Phase 3 (current — has fallback):
public function resolvedPriceCents(): int
{
    $raw = $this->getRawOriginal('price_cents');
    if ($raw !== null) {
        return (int) $raw;
    }
    // ← REMOVE THIS FALLBACK after Phase 3:
    return (int) round((float) ($this->getRawOriginal('price') ?? 0) * 100);
}

// After Phase 3 (simplified):
public function resolvedPriceCents(): int
{
    return (int) ($this->getRawOriginal('price_cents') ?? 0);
}
```

**`app/Models/Template.php`** — `resolvedDiscountPriceCents()`:
```php
// Remove fallback to $rawDecimal after Phase 3.
// Simplified: return (int) getRawOriginal('discount_price_cents') or null.
```

**`app/Models/Tenancy/Subscription.php`** — `resolvedPriceCents()`:
```php
// Remove: return (int) round((float)($this->getRawOriginal('price') ?? 0) * 100);
// Simplified: return (int)($this->getRawOriginal('price_cents') ?? 0);
```

### Views to update BEFORE Phase 3 executes

These 2 files still read legacy columns and will break after Phase 3 if not updated:

| File | Column(s) | Fix |
|------|-----------|-----|
| `resources/views/front/pages/checkout.blade.php` (lines 18–29) | `templates.price`, `templates.discount_price` | Replace with `$template->resolvedPriceCents()` / `$template->resolvedDiscountPriceCents()` block |
| `resources/views/front/pages/checkout.blade.php` (line 1045) | derived from `$finalPrice` (from above) | Change to `$template->resolvedDiscountPriceCents() ?? $template->resolvedPriceCents()` |
| `resources/views/dashboard/templates/edit.blade.php` (line 153) | `templates.price` | `value="{{ old('price', number_format($template->resolvedPrice(), 2)) }}"` |
| `resources/views/dashboard/templates/edit.blade.php` (line 160) | `templates.discount_price` | `value="{{ old('discount_price', $template->resolvedDiscountPrice() ? number_format($template->resolvedDiscountPrice(), 2) : '') }}"` |

### What is NOT in scope for Phase 3

| Item | Reason |
|------|--------|
| `coupons` table | ADR-003 explicitly excludes coupons |
| `domain_tld_prices` table | Out of scope |
| `invoices` table | Out of scope |
| `order_items` table | Out of scope |
| `plans.monthly_price_cents` / `plans.annual_price_cents` | Already on cents — no legacy decimal column to drop |
| `plan->discount_price` in `CheckoutController:164` | Dead code (plans has no such column) — safe to remove but not urgent |

---

## 5. Remaining Technical Debt

| # | Debt | File | Severity | Blocks Phase 3? |
|---|------|------|----------|-----------------|
| ~~D1~~ | ~~`checkout.blade.php` reads `$template->price` + `$template->discount_price` for display (lines 18–29)~~ | ~~`resources/views/front/pages/checkout.blade.php`~~ | ~~Medium~~ | ✅ **RESOLVED** 2026-06-16 |
| ~~D2~~ | ~~`checkout.blade.php` derives `TEMPLATE_FINAL_CENTS` from float `$finalPrice` (line 1045)~~ | ~~same~~ | ~~Medium~~ | ✅ **RESOLVED** 2026-06-16 |
| ~~D3~~ | ~~`templates/edit.blade.php` pre-fills price fields from legacy decimal (lines 153, 160)~~ | ~~`resources/views/dashboard/templates/edit.blade.php`~~ | ~~Medium~~ | ✅ **RESOLVED** 2026-06-16 |
| D4 | `plan->discount_price` in CheckoutController (line 164) — dead code, plans has no this column | `app/Http/Controllers/Front/CheckoutController.php` | Low | ❌ No — already dead code |
| D5 | `plan->price` in subscriptions/edit.blade (line 4) — dead code, plans has no `price` column → JS price auto-fill always 0 | `resources/views/dashboard/management/subscriptions/edit.blade.php` | Low | ❌ No — UX bug, not blocking |
| D6 | Fallback branches in `resolvedPriceCents()` / `resolvedDiscountPriceCents()` (Template + Subscription models) | both models | Low | ❌ No — safe to keep during window; simplify in Phase 3 PR |
| D7 | `TemplateSeeder`: `'price' => 12000` comment says "in cents" but inserts into decimal column (stores $12,000 not $120.00) | `database/seeders/TemplateSeeder.php` | Low | ❌ No — seeder only |

**Summary:** D1, D2, D3 are ✅ RESOLVED (2026-06-16). D4–D7 are cleanup items, not blockers. No debt remains that blocks Phase 3 execution.

---

## 6. Final Recommendation

### Is the system stable enough to start the stability window countdown toward Phase 3?

**YES — with conditions.**

**What is solid:**
- G1 (no NULLs) and G2 (no mismatches) have both passed.
- All billing paths (checkout, order activation, admin create/update) are fully on cents. No float arithmetic reaches order creation or invoice generation.
- `subscriptions.price` has zero active reads remaining — that column is ready for Phase 3 the moment G4 and G5 pass.
- Template model and Subscription model helpers are confirmed present and correct.

**What still needs to happen (in order):**

1. **During the 7-day window** — monitor G5 (no pricing errors in logs). Run the SQL monitoring queries at midpoint and end.

2. ~~**Before the Phase 3 migration runs** — fix D1, D2, D3~~ → ✅ **DONE** (2026-06-16)

3. **On Phase 3 day (earliest 2026-06-23)** — if G1–G5 are all GREEN: create and run the Phase 3 migration, simplify the model fallback branches, and close ADR-003.

**Risk if Phase 3 runs now (before G4/G5 pass):**  
Stability window would be cut short. The risk is not a code bug but a process risk — insufficient observation time to catch any edge case that the data audit missed.

**Risk if Phase 3 is delayed:**  
None. The dual-write ensures both columns stay in sync. Delaying Phase 3 is always safe.

---

## Stability Window Timeline

```
2026-06-16  ┬─ Stability Window OPENS
             │   G1 ✅  G2 ✅  G3 ✅  G4 ⏳  G5 ⏳
             │
             │   ✅ D1 resolved — checkout.blade.php price block → helpers
             │   ✅ D2 resolved — TEMPLATE_FINAL_CENTS → $finalCents (no float)
             │   ✅ D3 resolved — edit.blade.php prefill → resolvedPrice*() helpers
             │
             │   Daily: check logs for pricing errors
             │   Daily: watch checkout + template listing in production
             │
2026-06-23  ┴─ EARLIEST Phase 3 Review
                Run SQL monitoring queries
                If G4 ✅ G5 ✅ → proceed to Phase 3 migration
                If any incident → extend window by 7 days
```
