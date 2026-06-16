# ADR-003 Phase 1 — Template Prices Migration Report

**Date:** 2026-06-16  
**Status:** ✅ Complete — pending user-side `php artisan migrate` + backfill  
**Scope:** `templates.price` / `templates.discount_price` → `templates.price_cents` / `templates.discount_price_cents`

---

## 1. Migration Created

**File:** `database/migrations/2026_06_16_200003_add_price_cents_to_templates_table.php`

| New Column | Type | Nullable | Comment |
|------------|------|----------|---------|
| `templates.price_cents` | `unsignedBigInteger` | ✅ | ADR-003: price in integer cents |
| `templates.discount_price_cents` | `unsignedBigInteger` | ✅ | ADR-003: discount_price in integer cents. NULL = no discount |

**Old columns NOT dropped:** `price` (decimal) and `discount_price` (decimal) are retained — no-drop policy in effect during dual-write period.

---

## 2. Files Modified

### `app/Models/Template.php`

**Additions:**
- `price_cents` and `discount_price_cents` added to `$fillable`
- Casts added: `'price_cents' => 'integer'`, `'discount_price_cents' => 'integer'`
- Four new helpers:

| Method | Returns | Fallback |
|--------|---------|---------|
| `resolvedPriceCents(): int` | `price_cents` as integer | `(int) round(price * 100)` |
| `resolvedDiscountPriceCents(): ?int` | `discount_price_cents` as integer or null | `(int) round(discount_price * 100)` or null |
| `resolvedPrice(): float` | `resolvedPriceCents() / 100` | *(via above)* |
| `resolvedDiscountPrice(): ?float` | `resolvedDiscountPriceCents() / 100` or null | *(via above)* |

All helpers use `getRawOriginal()` to bypass Eloquent float cast and avoid double-rounding risk.

---

### `app/Http/Controllers/Admin/TemplateController.php`

**`store()`** — added dual-write after `$validated` is known:
```php
// Before:
'price'          => $validated['price'],
'discount_price' => $validated['discount_price'] ?? null,

// After:
'price'                => $validated['price'],
'price_cents'          => (int) round((float) $validated['price'] * 100),
'discount_price'       => $discountDecimal,
'discount_price_cents' => $discountDecimal !== null
    ? (int) round((float) $discountDecimal * 100)
    : null,
```

**`update()`** — same dual-write pattern applied to `$template->update([...])`.

---

### `app/Http/Controllers/Front/CheckoutController.php`

**Lines 132–136** — replaced direct property reads with helpers:
```php
// Before:
$basePrice = (float) ($template->price ?? 0);
$discRaw   = $template->discount_price ?? null;
$discPrice = is_null($discRaw) ? null : (float) $discRaw;
$hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;

// After (ADR-003 Phase 1):
$basePriceCents = $template ? $template->resolvedPriceCents() : 0;
$discPriceCents = $template ? $template->resolvedDiscountPriceCents() : null;
$basePrice      = $basePriceCents / 100;   // float — display only
$discPrice      = $discPriceCents !== null ? $discPriceCents / 100 : null;
$hasDiscount    = $discPriceCents !== null && $discPriceCents > 0 && $discPriceCents < $basePriceCents;
```

**Line 274** — eliminated float-multiply conversion:
```php
// Before:
$unitCents = $showDiscount ? (int) ($discPrice * 100) : (int) ($basePrice * 100);

// After:
$unitCents = $showDiscount ? ($discPriceCents ?? $basePriceCents) : $basePriceCents;
```

**Line 283** — `base_cents` now sourced directly from cents column:
```php
// Before:
'base_cents' => (int) (($basePrice ?? 0) * 100),
// After:
'base_cents' => $basePriceCents,
```

**Line 475** — template-specific override in response data:
```php
// Before:
$totalCents = $isDomainOnly ? $domainsTotalCents : ($showDiscount ? (int) ($discPrice * 100) : (int) ($basePrice * 100));
// After:
$totalCents = $isDomainOnly ? $domainsTotalCents : ($showDiscount ? ($discPriceCents ?? $basePriceCents) : $basePriceCents);
```

---

### `app/Services/Billing/OrderActivationService.php`

**Line 210** — replaced direct `$template->price` access with helper:
```php
// Before:
'price' => $template->price,

// After:
'price' => $template->resolvedPrice(),
// Note: still writes to subscriptions.price (decimal) — Phase 2 will switch to price_cents
```

This is forward-compatible: `resolvedPrice()` returns the decimal float sourced from the cents column (or falls back to the decimal column). When Phase 2 adds `subscriptions.price_cents`, this line will be updated to use `resolvedPriceCents()`.

---

### `routes/console.php`

**Fixed:** File was truncated mid-line (bug from a prior session). The `tenancy:runtime-usage` command's `purpose()` string was completed.

**Added:** `adr003:backfill-template-prices` command — see §4.

---

## 3. Backfill Command

```bash
# Preview — no writes
php artisan adr003:backfill-template-prices --dry-run

# Apply
php artisan adr003:backfill-template-prices
```

**What it does:**
- Reads every `templates` row
- Computes `expected_price_cents = (int) round(price * 100)`
- Computes `expected_discount_price_cents = (int) round(discount_price * 100)` or `null` if discount is empty/zero
- If current cents values differ from expected → updates the row
- Detects and warns on mismatches (rows where cents are set but don't match the decimal)
- Prints per-row lines + summary table

**Output columns:** `processed`, `updated`, `skipped`, `mismatches`, `null_discounts`, `dry_run`

---

## 4. User Actions Required

Run the following on your machine:

```bash
# 1. Apply migration (adds price_cents + discount_price_cents columns)
php artisan migrate

# 2. Preview backfill
php artisan adr003:backfill-template-prices --dry-run

# 3. Apply backfill
php artisan adr003:backfill-template-prices

# 4. Clear cache
php artisan optimize:clear
```

---

## 5. Verification Queries (Tinker / phpMyAdmin)

```php
// In php artisan tinker:
\App\Models\Template::select('id','price','price_cents','discount_price','discount_price_cents')->get();
```

**Expected:** Every row has `price_cents = round(price * 100)`. Rows with no discount have `discount_price_cents = null`.

```sql
-- Confirm no rows with price but missing price_cents
SELECT id, price, price_cents FROM templates WHERE price IS NOT NULL AND price_cents IS NULL;
-- Expected: 0 rows

-- Confirm discount_price_cents matches
SELECT id, price_cents, ROUND(price * 100) AS expected_cents
FROM templates
WHERE price_cents != ROUND(price * 100);
-- Expected: 0 rows
```

---

## 6. Audit Checklist

| Question | Answer |
|----------|--------|
| Migration adds `price_cents` and `discount_price_cents`? | ✅ Yes — `2026_06_16_200003_*.php` |
| Old decimal columns kept? | ✅ Yes — `price` and `discount_price` untouched |
| `TemplateController::store()` dual-writes? | ✅ Yes |
| `TemplateController::update()` dual-writes? | ✅ Yes |
| `CheckoutController` no longer multiplies float × 100? | ✅ Yes — uses `$basePriceCents` / `$discPriceCents` directly |
| `OrderActivationService` stopped reading `$template->price` directly? | ✅ Yes — uses `$template->resolvedPrice()` |
| `discount_price = null` / `0` handled correctly? | ✅ Yes — `resolvedDiscountPriceCents()` returns `null`; `discount_price_cents` stored as `null` |
| Backfill command created with `--dry-run`? | ✅ Yes |

---

## 7. Scope Boundaries (Constraints Honored)

| Constraint | Status |
|-----------|--------|
| `subscriptions.price` not touched | ✅ Honored |
| `coupons` not touched | ✅ Honored |
| `domain_tld_prices` not touched | ✅ Honored |
| `invoices` / `orders` not touched | ✅ Honored |
| `templates.price` not dropped | ✅ Honored |
| `templates.discount_price` not dropped | ✅ Honored |
| ADR-003 Phase 2 not started | ✅ Honored |
| No `__()` used | ✅ Honored |
| No `with('success', ...)` used | ✅ Honored |

---

## 8. Phase 1 Stability Check

Before proceeding to Phase 2 (`subscriptions.price` → `price_cents`), verify:

- ✅ Migration ran successfully (`php artisan migrate`)
- ✅ Backfill ran with zero mismatches
- ✅ Admin template create/edit saves `price_cents` correctly (verify in DB after saving a template)
- ✅ Checkout page loads correctly for a template purchase (no 500 errors)
- ✅ Template detail pages show correct prices (no visual regression)
- ✅ ≥1 week production stable with no broken checkout flows

---

## 9. Readiness for Phase 2 (subscriptions.price_cents)

**Phase 1 unblocks Phase 2** by resolving the `OrderActivationService` dependency chain:

- Before Phase 1: `OrderActivationService:210` read `$template->price` (raw decimal)
- After Phase 1: it reads `$template->resolvedPrice()` which sources from `price_cents` ÷ 100

When Phase 2 adds `subscriptions.price_cents`, `OrderActivationService:210` only needs to change to:
```php
// Phase 2 final form:
'price_cents' => $template->resolvedPriceCents(),
```

No other template-related reads need to change in Phase 2. All 5 subscription write paths are clearly mapped in `docs/ADR_003_READINESS_AUDIT.md §3`.

**Phase 2 can begin once Phase 1 stability window (≥1 week) passes with no incidents.**
