# ADR-008 Phase 1 + 2 — Coupon Foundation Report

**Date:** 2026-06-17  
**Phases:** 1 (Database + Model) and 2 (Server-side Validation API)  
**Scope:** Complete coupon infrastructure foundation — no checkout integration, no admin CRUD

---

## 1. Migrations Created

### Migration 1 — `2026_06_17_175750_add_columns_to_coupons_table.php`

Adds 4 missing columns to the existing `coupons` table:

| Column | Type | Default | Notes |
|--------|------|---------|-------|
| `max_uses` | `UNSIGNED INT` | `NULL` | Max total redemptions; null = unlimited |
| `used_count` | `UNSIGNED INT` | `0` | Running count of successful redemptions |
| `is_active` | `BOOLEAN` | `true` | On/off switch for the coupon |
| `minimum_amount_cents` | `UNSIGNED BIGINT` | `NULL` | Min cart subtotal in cents; null = no minimum |

**Does NOT drop or modify any existing column.**

### Migration 2 — `2026_06_17_175751_add_coupon_id_to_invoices_table.php`

Adds `coupon_id` FK to `invoices`:

| Column | Type | Constraint | Notes |
|--------|------|-----------|-------|
| `coupon_id` | `BIGINT UNSIGNED` | `FK → coupons.id`, nullable, `nullOnDelete` | Links invoice to coupon used |

Placed `after('payment_attempt_id')`.  
`nullOnDelete` preserves `discount_cents` audit trail even if the coupon row is deleted.  
**Does NOT modify `discount_cents`.**

---

## 2. Model Changes

### `app/Models/Coupon.php` — Full Rewrite

#### New `$fillable` additions:
```php
'max_uses', 'used_count', 'is_active', 'minimum_amount_cents'
```

#### New `$casts`:
```php
'expires_at'           => 'datetime',
'is_active'            => 'boolean',
'max_uses'             => 'integer',
'used_count'           => 'integer',
'minimum_amount_cents' => 'integer',
```

#### New relationships:
```php
public function invoices(): HasMany   // inverse of Invoice::coupon()
```

#### New scopes:

**`scopeUsable(?int $subtotalCents = null)`**  
Filters by: `is_active=true`, not expired, `used_count < max_uses`.  
When `$subtotalCents` is provided, also filters by `minimum_amount_cents`.

**`scopeActive()`**  
Alias for `is_active=true` only.

#### New methods:

**`isUsableForSubtotal(int $subtotalCents): bool`**  
Full validation check (all 4 conditions):
```
is_active → expires_at → max_uses/used_count → minimum_amount_cents
```

**`computeDiscountCents(int $subtotalCents): int`**  
```php
// fixed: discount_value is USD decimal → converted to cents
// percent: discount_value is a percentage
// Result: capped at subtotalCents, minimum 0
match ($this->discount_type) {
    'fixed'   => (int) round((float) $this->discount_value * 100),
    'percent' => (int) round($subtotalCents * ((float) $this->discount_value / 100)),
    default   => 0,
}
```

### `app/Models/Invoice.php` — Minimal Changes

- `coupon_id` added to `$fillable`
- New relationship added:
```php
public function coupon(): BelongsTo
{
    return $this->belongsTo(Coupon::class);
}
```

---

## 3. Controller Created

### `app/Http/Controllers/Front/CouponValidationController.php`

Single action: `validate(Request $request): JsonResponse`

**Validation rules:**
```php
'code'           => ['required', 'string', 'max:100'],
'subtotal_cents' => ['required', 'integer', 'min:0'],
```

**Logic flow:**
```
1. strtoupper(trim($code))
2. Coupon::where('code', $code)->first()
3. If not found → invalid + Coupon_Not_Found message
4. $coupon->isUsableForSubtotal($subtotalCents) → if false → specific reason
5. $coupon->computeDiscountCents($subtotalCents)
6. Return valid=true + discount_cents
```

**DOES NOT:** increment `used_count`, attach to any model, create any session state.

---

## 4. Route Added

**File:** `routes/web.php`

```php
Route::post('/checkout/coupon/validate', [CouponValidationController::class, 'validate'])
    ->middleware('throttle:30,1')
    ->name('checkout.coupon.validate');
```

- Throttle: 30 requests/minute/IP (prevents brute-force code guessing)
- Route name: `checkout.coupon.validate`
- Inside the `setLocale` middleware group (same as all front routes)

---

## 5. Translations Added

**File:** `database/seeders/DashboardTranslationsSeeder.php`

7 new Arabic translation keys added:

| Key | Arabic |
|-----|--------|
| `dashboard.Coupon_Not_Found` | رمز الكوبون غير موجود. |
| `dashboard.Coupon_Invalid` | هذا الكوبون غير صالح. |
| `dashboard.Coupon_Inactive` | هذا الكوبون غير مفعّل. |
| `dashboard.Coupon_Expired` | انتهت صلاحية هذا الكوبون. |
| `dashboard.Coupon_Exhausted` | تجاوز هذا الكوبون الحد الأقصى للاستخدام. |
| `dashboard.Coupon_Minimum_Amount` | يتطلب هذا الكوبون حداً أدنى للطلب. |
| `dashboard.Coupon_Applied` | تم تطبيق الكوبون بنجاح. |

---

## 6. JSON Response Examples

### Success
```json
POST /checkout/coupon/validate
{ "code": "SUMMER20", "subtotal_cents": 5000 }

→ HTTP 200
{
  "valid": true,
  "code": "SUMMER20",
  "discount_cents": 1000,
  "message": "تم تطبيق الكوبون بنجاح."
}
```

### Code not found
```json
{ "code": "BADCODE", "subtotal_cents": 5000 }

→ HTTP 200
{
  "valid": false,
  "discount_cents": 0,
  "message": "رمز الكوبون غير موجود."
}
```

### Expired
```json
{ "code": "OLDPROMO", "subtotal_cents": 5000 }

→ HTTP 200
{
  "valid": false,
  "discount_cents": 0,
  "message": "انتهت صلاحية هذا الكوبون."
}
```

### Below minimum amount
```json
{ "code": "BIGORDER", "subtotal_cents": 1000 }

→ HTTP 200
{
  "valid": false,
  "discount_cents": 0,
  "message": "يتطلب هذا الكوبون حداً أدنى للطلب."
}
```

**Note:** HTTP 200 is always returned (never 422 for invalid coupons).  
This is intentional — the frontend can read `valid` without catching exceptions.

---

## 7. Checkout Integration — Was It Done?

**No.** `CheckoutController` was not touched in Phase 1 or 2.

| Component | Changed? |
|-----------|----------|
| `CheckoutController` | ❌ Not touched |
| `checkout.blade.php` | ❌ Not touched |
| `computeTotals()` in `InvoiceController` | ❌ Not touched |
| Hardcoded JS coupon codes | ❌ Still present (Phase 3 cleanup) |
| `discount_cents` write path | ❌ Still hardcoded to 0 (Phase 3) |

---

## 8. Admin CRUD — Was It Done?

**No.** No admin views, controllers, or routes for coupon management were created in this phase.

| Component | Status |
|-----------|--------|
| `CouponController` (admin) | ❌ Phase 4 |
| `dashboard/coupons/` views | ❌ Phase 4 |
| Coupon routes in `dashboard.php` | ❌ Phase 4 |
| Sidebar nav link | ❌ Phase 4 |

---

## 9. Is Phase 3 Ready?

**Yes.** All prerequisites for Phase 3 are in place.

| Phase 3 Requirement | Available? |
|--------------------|------------|
| `Coupon::isUsableForSubtotal()` — validation | ✅ Phase 1 |
| `Coupon::computeDiscountCents()` — calculation | ✅ Phase 1 |
| `Coupon::scopeUsable()` — DB lookup | ✅ Phase 1 |
| `invoices.coupon_id` FK column | ✅ Phase 1 (after migrate) |
| `Invoice::$fillable coupon_id` | ✅ Phase 1 |
| `Invoice::coupon()` relationship | ✅ Phase 1 |
| API endpoint for frontend validation | ✅ Phase 2 |
| Translation messages | ✅ Phase 2 |

**Phase 3 scope (CheckoutController integration):**
1. Accept `coupon_code` in `CheckoutController::process()` POST request
2. Look up coupon with `Coupon::usable()->where('code', $code)->first()`
3. Validate with `isUsableForSubtotal($subtotalCents)`
4. Compute `discount_cents = computeDiscountCents($subtotalCents)`
5. Write `discount_cents` + `coupon_id` to the invoice
6. `$coupon->increment('used_count')` after invoice creation
7. `$coupon->subscriptions()->attach($subscription->id)` for subscription orders
8. Replace hardcoded JS `computeDiscount()` with `fetch` to `checkout.coupon.validate`
9. Add `<input type="hidden" name="coupon_code">` to checkout form

---

## Summary

```
Phase 1 — Database + Model:    ✅ Complete
Phase 2 — Validation API:      ✅ Complete
Phase 3 — Checkout Integration: ⏳ Ready to start
Phase 4 — Admin CRUD:           ⏳ Blocked by Phase 3
```

**Commands to run after deployment:**
```bash
php artisan migrate
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

**Files created (new):**
- `database/migrations/2026_06_17_175750_add_columns_to_coupons_table.php`
- `database/migrations/2026_06_17_175751_add_coupon_id_to_invoices_table.php`
- `app/Http/Controllers/Front/CouponValidationController.php`
- `docs/ADR_008_PHASE1_2_COUPON_FOUNDATION_REPORT.md`

**Files modified:**
- `app/Models/Coupon.php`
- `app/Models/Invoice.php`
- `routes/web.php`
- `database/seeders/DashboardTranslationsSeeder.php`
