# ADR-003 Phase 2 — Subscriptions Price Migration Report

**Date:** 2026-06-16  
**Status:** ✅ Code Complete — pending user-side `php artisan migrate` + backfill  
**Scope:** `subscriptions.price` (decimal) → `subscriptions.price_cents` (BIGINT UNSIGNED)

---

## 1. Migration Created

**File:** `database/migrations/2026_06_16_200004_add_price_cents_to_subscriptions_table.php`

| New Column | Type | Nullable | Comment |
|------------|------|----------|---------|
| `subscriptions.price_cents` | `unsignedBigInteger` | ✅ | ADR-003: price in integer cents |

**Old column NOT dropped:** `price` (decimal, cast: float) is retained — no-drop policy in effect.

---

## 2. Files Modified

### `app/Models/Tenancy/Subscription.php`

- `price_cents` added to `$fillable`
- Cast added: `'price_cents' => 'integer'`
- Two helpers added:

| Method | Returns | Fallback |
|--------|---------|---------|
| `resolvedPriceCents(): int` | `price_cents` as integer | `(int) round(price * 100)` |
| `resolvedPrice(): float` | `resolvedPriceCents() / 100` | *(via above)* |

Both helpers use `getRawOriginal()` to bypass the `'price' => 'float'` Eloquent cast and avoid double-rounding.

---

### `app/Http/Controllers/Admin/Management/SubscriptionController.php`

**`store()`** — dual-write added:
```php
// ADR-003 Phase 2 — dual-write: price (decimal) kept, price_cents added
$data['price_cents'] = (int) round((float) $data['price'] * 100);
$subscription = Subscription::create($data);
```

**`update()`** — same dual-write pattern applied before `$subscription->update($data)`.

---

### `app/Http/Controllers/Front/CheckoutController.php`

**Checkout subscription create** — dual-write added:
```php
'price'       => $config['unit_cents'] / 100,
// ADR-003 Phase 2 — unit_cents is already an integer, no conversion needed
'price_cents' => (int) $config['unit_cents'],
```

Note: this path had the cleanest data flow — `unit_cents` was already the canonical integer, now stored directly.

---

### `app/Http/Controllers/Admin/Management/ServerController.php`

**Admin "add existing account" subscription create** — dual-write added:
```php
'price'       => 0,
'price_cents' => 0, // ADR-003 Phase 2 — dual-write
```

---

### `app/Services/Billing/OrderActivationService.php`

**Order activation subscription create** — dual-write upgraded from Phase 1:
```php
// ADR-003 Phase 2 — dual-write: price (decimal) kept for compatibility
'price'       => $template->resolvedPrice(),
'price_cents' => $template->resolvedPriceCents(),
```

This is the highest-risk path: `resolvedPriceCents()` reads from `templates.price_cents` (Phase 1 column, already backfilled), so no float multiplication occurs. The value is already an integer.

---

### `routes/console.php`

**Added:** `adr003:backfill-subscription-prices` command — see §4.

---

### `resources/views/dashboard/index.blade.php`

**Read switch R1:**
```php
// Before:
@if($sub->price)
    <p ...>${{ number_format($sub->price, 2) }}</p>
@endif

// After:
@if($sub->resolvedPriceCents() > 0)
    <p ...>${{ number_format($sub->resolvedPrice(), 2) }}</p>
@endif
```

---

### `resources/views/dashboard/management/subscriptions/edit.blade.php`

**Read switch R2:**
```php
// Before:
value="{{ old('price', $subscription->price) }}"

// After:
value="{{ old('price', $subscription->resolvedPrice()) }}"
```

---

## 3. Subscription Count (Baseline)

> **Run on your machine before migrating:**

```sql
SELECT COUNT(*) AS subscriptions_count FROM subscriptions;

SELECT
    MIN(price)  AS min_price,
    MAX(price)  AS max_price,
    AVG(price)  AS avg_price
FROM subscriptions;
```

_Results to be filled in after user runs these queries._

---

## 4. Backfill Command

```bash
# 1. Preview — no writes
php artisan adr003:backfill-subscription-prices --dry-run

# 2. Apply
php artisan adr003:backfill-subscription-prices
```

**Algorithm:** chunk(200) → per row: `expected = round(price * 100)` → if `price_cents IS NULL` or mismatch → update → log → summarize.

**Output columns:** `processed` | `updated` | `skipped` | `mismatches` | `dry_run`

_Results to be filled in after user runs backfill._

---

## 5. Mismatch Count

> **Run after backfill:**

```sql
-- Must return 0
SELECT id, price, price_cents, ROUND(price * 100) AS expected
FROM subscriptions
WHERE price_cents != ROUND(price * 100);
```

_Expected: 0 rows. Fill in actual count after running._

---

## 6. SQL Validation Results

> **Run after backfill:**

```sql
-- Test 1: No nulls remaining
SELECT COUNT(*) AS null_count
FROM subscriptions
WHERE price_cents IS NULL;
-- Expected: 0

-- Test 2: No mismatches
SELECT COUNT(*) AS mismatch_count
FROM subscriptions
WHERE price_cents != ROUND(price * 100);
-- Expected: 0

-- Test 3: Sanity check — a few rows
SELECT id, price, price_cents
FROM subscriptions
ORDER BY id
LIMIT 10;
-- Expected: price_cents = price × 100 for each row
```

_Fill in actual results after running._

---

## 7. Read Switch Status

| Location | Old Code | New Code | Status |
|----------|---------|---------|--------|
| `dashboard/index.blade.php:200` | `$sub->price` | `$sub->resolvedPrice()` | ✅ Done |
| `subscriptions/edit.blade.php:94` | `$subscription->price` | `$subscription->resolvedPrice()` | ✅ Done |

---

## 8. Dual-Write Coverage: All Write Paths

| Write Path | `price` written | `price_cents` written | Status |
|-----------|----------------|----------------------|--------|
| W1 `SubscriptionController::store()` | ✅ | ✅ | Complete |
| W2 `SubscriptionController::update()` | ✅ | ✅ | Complete |
| W3 `CheckoutController` (checkout) | ✅ | ✅ (direct from `unit_cents`) | Complete |
| W4 `ServerController` (add account) | ✅ (0) | ✅ (0) | Complete |
| W5 `OrderActivationService` | ✅ (resolvedPrice) | ✅ (resolvedPriceCents) | Complete |

**All 5 write paths covered. Zero paths writing `price` only.**

---

## 9. Blockers

| Blocker | Type | Status |
|---------|------|--------|
| `php artisan migrate` must be run to create `price_cents` column | User action required | ⏳ Pending |
| `php artisan adr003:backfill-subscription-prices` must be run | User action required | ⏳ Pending |
| SQL zero-check: `WHERE price_cents IS NULL` → 0 | Verification | ⏳ Pending |
| SQL zero-check: mismatch query → 0 rows | Verification | ⏳ Pending |
| ≥1 week stability window after backfill before dropping `price` column | Policy | ⏳ Clock starts after backfill |

No code blockers remain. All file changes are complete.

---

## 10. Phase 2 Status

| Question | Answer |
|----------|--------|
| Migration file created? | ✅ `2026_06_16_200004_add_price_cents_to_subscriptions_table.php` |
| `subscriptions.price` kept (no-drop policy)? | ✅ Yes |
| Subscription model updated (fillable + cast + helpers)? | ✅ Yes |
| All 5 write paths dual-writing? | ✅ Yes |
| Backfill command created with `--dry-run`? | ✅ Yes |
| Read switch applied (R1 + R2)? | ✅ Yes |
| `coupons` untouched? | ✅ Yes |
| `domain_tld_prices` untouched? | ✅ Yes |
| `invoices` / `order_items` untouched? | ✅ Yes |
| `__()` used anywhere? | ✅ No |
| `with('success', ...)` used anywhere? | ✅ No |
| **Phase 2 code complete?** | **✅ YES — pending user-side migrate + backfill** |

---

## User Actions Required

Run the following on your machine in order:

```bash
# 1. Apply migration
php artisan migrate

# 2. Preview backfill
php artisan adr003:backfill-subscription-prices --dry-run

# 3. Apply backfill
php artisan adr003:backfill-subscription-prices

# 4. Clear cache
php artisan optimize:clear
```

Then run the SQL validation queries in §6. Both must return 0.

---

## Scope Boundaries Honored

| Constraint | Status |
|-----------|--------|
| `coupons` not touched | ✅ |
| `domain_tld_prices` not touched | ✅ |
| `invoices` / `order_items` not touched | ✅ |
| `subscriptions.price` not dropped | ✅ |
| Phase 3 not started | ✅ |
| No `__()` used | ✅ |
| No `with('success', ...)` used | ✅ |
