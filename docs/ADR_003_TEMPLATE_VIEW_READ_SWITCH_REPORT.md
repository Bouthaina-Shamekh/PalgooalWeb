# ADR-003 Template View Read Switch — Report

**Date:** 2026-06-16  
**Status:** ✅ Complete  
**Scope:** Switch all template price reads in Blade views from `$template->price` / `$template->discount_price` to `$template->resolvedPrice*()` helpers  
**Impact on billing:** Zero — all switched paths are display-only

---

## 1. Files Modified

| # | File | Changes |
|---|------|---------|
| 1 | `resources/views/front/pages/template-show.blade.php` | Lines 5–16: replaced 5-line decimal block with 8-line cents block. Line 623: `$finalPriceCents = (int) round($finalPrice * 100)` → `$finalPriceCents = $finalCents` |
| 2 | `resources/views/front/pages/template-show-redesign.blade.php` | Lines 6–11: same pattern as file 1 |
| 3 | `resources/views/front/pages/view-template.blade.php` | Lines 19–24: replaced `round($finalPrice * 100)` with `$finalCents` from helper |
| 4 | `resources/views/components/template/sections/templates.blade.php` | Line 33: `<meta itemprop="price">` → helpers. Line 39: ribbon check → `resolvedDiscountPriceCents()`. Lines 85–93: price display block wrapped in `@php` cents block |
| 5 | `resources/views/components/template/sections/templates_listing_showcase.blade.php` | Line 87: `(float)($template->discount_price ?? $template->price ?? 0)` → `$template->resolvedDiscountPrice() ?? $template->resolvedPrice()` |
| 6 | `resources/views/front/sections/templates/templates_listing_showcase.blade.php` | Same as file 5 (duplicate component) |
| 7 | `resources/views/dashboard/templates/index.blade.php` | Lines 265–268: discount check + `$currentPrice` → cents helpers. Lines 350, 360: `$template->price` → `$tPriceCents / 100` |
| 8 | `resources/views/livewire/admin/template/template-management.blade.php` | Lines 203–208: price block → `@php` cents block with `$lwPriceCents` / `$lwDiscCents` |
| 9 | `resources/views/livewire/admin/template/frontend-templates-page.blade.php` | Line 62: `<meta itemprop="price">` → helpers. Lines 85–86: strikethrough + price display → helpers |

---

## 2. Conversion Pattern Applied

### Pages with full price computation block (files 1, 2, 3)

**Before:**
```php
$basePrice = (float) ($template->price ?? 0);
$discRaw = $template->discount_price;
$discPrice = is_null($discRaw) ? null : (float) $discRaw;
$hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
$finalPrice = $hasDiscount ? $discPrice : $basePrice;
```

**After:**
```php
// ADR-003 Read Switch: use helpers instead of decimal columns
$priceCents    = $template->resolvedPriceCents();
$discountCents = $template->resolvedDiscountPriceCents();
$hasDiscount   = $discountCents !== null && $discountCents > 0 && $discountCents < $priceCents;
$finalCents    = $hasDiscount ? $discountCents : $priceCents;
$basePrice     = $priceCents / 100;
$discPrice     = $hasDiscount ? $discountCents / 100 : null;
$finalPrice    = $finalCents / 100;
```

### `$finalPriceCents` (files 1, 3)

**Before:** `$finalPriceCents = (int) round($finalPrice * 100);`  
**After:** `$finalPriceCents = $finalCents;`  

Eliminates the only remaining float multiply on the display path. The value is now taken directly from the integer cents column.

### Listing showcase price variable (files 5, 6)

**Before:** `$price = (float) ($template->discount_price ?? $template->price ?? 0);`  
**After:** `$price = $template->resolvedDiscountPrice() ?? $template->resolvedPrice();`  

Additionally improves semantics: `resolvedDiscountPrice()` returns `null` when discount is zero or negative (not just null), so zero-value discounts are now correctly ignored.

### Dashboard index (file 7)

**Before:**
```php
$hasDiscount = !is_null($template->discount_price)
    && $template->discount_price > 0
    && $template->discount_price < $template->price;
$currentPrice = $hasDiscount ? $template->discount_price : $template->price;
```

**After:**
```php
$tPriceCents  = $template->resolvedPriceCents();
$tDiscCents   = $template->resolvedDiscountPriceCents();
$hasDiscount  = $tDiscCents !== null && $tDiscCents > 0 && $tDiscCents < $tPriceCents;
$currentPrice = ($hasDiscount ? $tDiscCents : $tPriceCents) / 100;
```

---

## 3. Grep Audit Results

Run after all 9 edits were applied:

```
=== ->discount_price ===
CLEAN  ← zero occurrences in all 9 files

=== ->price (raw decimal) ===
CLEAN  ← zero occurrences in all 9 files

=== ->resolvedPrice* (helper usage) ===
20 occurrences confirmed across all 9 files ← every file uses helpers
```

**Result: B5 blocker from ADR_003_CLOSEOUT_REVIEW.md is RESOLVED.**

---

## 4. Scope Boundaries Honored

| Constraint | Status |
|-----------|--------|
| No migration created | ✅ |
| No columns dropped | ✅ |
| `subscriptions` not touched | ✅ |
| `coupons` not touched | ✅ |
| `domain_tld_prices` not touched | ✅ |
| Phase 3 not started | ✅ |
| No `__()` used | ✅ |
| No `with('success', ...)` used | ✅ |
| No Livewire components created/modified (only Blade views) | ✅ |

---

## 5. ADR_003_CLOSEOUT_REVIEW.md Updates

The following sections were updated to reflect B5 resolved:

- Section 5 (Cleanup Readiness): "All view reads switched" → ✅ Done for both `templates.price` and `templates.discount_price`
- Section 6 (Remaining Risks): R1 and R2 marked ✅ RESOLVED
- Section 7 (Recommendation): Item 3 struck through as done
- Section 8 (Final Verdict): Status box updated; B5 row struck through as resolved

---

## 6. Remaining Blockers to Phase 3 (after this session)

| # | Blocker | Owner |
|---|---------|-------|
| B1 | `php artisan migrate` not yet run | User |
| B2 | `adr003:backfill-template-prices` not yet run | User |
| B3 | `adr003:backfill-subscription-prices` not yet run | User |
| B4 | SQL zero-checks not yet verified | User |
| ~~B5~~ | ~~Template view read switch~~ | ✅ Done |
| B6 | Stability window (≥1 week) not yet elapsed | Time |

Phase 3 (column drop) remains blocked on B1–B4 (user-side actions) and B6 (time). No developer actions remain before Phase 3.

---

## 7. User Actions Required Before Phase 3

```bash
# 1. Apply migrations
php artisan migrate

# 2. Preview backfills
php artisan adr003:backfill-template-prices --dry-run
php artisan adr003:backfill-subscription-prices --dry-run

# 3. Apply backfills
php artisan adr003:backfill-template-prices
php artisan adr003:backfill-subscription-prices

# 4. Clear cache
php artisan optimize:clear
```

Then verify in MySQL:
```sql
-- Must return 0
SELECT COUNT(*) FROM templates WHERE price_cents IS NULL;
SELECT COUNT(*) FROM templates WHERE discount_price_cents IS NULL AND discount_price > 0;
SELECT COUNT(*) FROM subscriptions WHERE price_cents IS NULL;
```

Wait ≥1 week with zero checkout incidents, then Phase 3 (drop old columns) may proceed.
