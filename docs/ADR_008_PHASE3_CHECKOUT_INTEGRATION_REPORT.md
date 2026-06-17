# ADR-008 Phase 3 — Checkout Integration Report

**Date:** 2026-06-17 (revised: usage tracking moved to settlement)  
**Phase:** 3 of 4 (ADR-008)  
**Scope:** Wire the coupon system into CheckoutController + checkout.blade.php — no admin CRUD, no payment gateway changes

---

## 1. Files Modified

| File | Type | Change |
|------|------|--------|
| `resources/views/front/pages/checkout.blade.php` | View | Hidden input + API-based JS validation |
| `app/Http/Controllers/Front/CheckoutController.php` | Controller | Coupon resolution, discount calculation, invoice write (no usage tracking) |
| `app/Services/Billing/InvoiceSettlementService.php` | Service | Coupon usage tracking at settlement — `lockForUpdate()` + `increment('used_count')` |

---

## 2. How Validation Works

### Frontend (UI feedback only — not trusted)
```
User types code → clicks "تطبيق"
         │
         ▼
fetch POST /checkout/coupon/validate
{ code, subtotal_cents }
         │
         ▼
API returns { valid, discount_cents, message }
         │
    ┌────┴────┐
  valid=true  valid=false
     │            │
couponHidden  couponHidden.value = ''
.value = code setDiscount(0)
setDiscount(  show error message
 discount_cents)
show ✅ message
```

### Backend (authoritative — on form submit)
```
POST /checkout/process
{ coupon_code: "SUMMER20", ... }
         │
         ▼
$couponCode = strtoupper(trim($request->input('coupon_code', '')))
         │
         ▼
Coupon::usable()->where('code', $couponCode)->first()
  ├── is_active = true
  ├── expires_at is null OR in future
  └── used_count < max_uses (or max_uses is null)
         │
    ┌────┴────┐
  found     not found / invalid
     │            │
$coupon = ...  $coupon = null
                $couponDiscount = 0
```

**Security invariant:** The frontend's `discount_cents` value is never read by the server. The server computes the discount from scratch using `Coupon::computeDiscountCents()`. A user cannot obtain a discount by manipulating hidden fields.

---

## 3. How Discount Is Calculated

### Domain-only checkout
```php
$subtotalCents  = sum of domain item price_cents
$couponDiscount = $coupon
    ? $coupon->computeDiscountCents($subtotalCents)
    : 0;

invoice.subtotal_cents = $subtotalCents
invoice.discount_cents = $couponDiscount
invoice.total_cents    = max(0, $subtotalCents - $couponDiscount)
```

### Subscription / Template checkout
```php
$baseSubtotal      = subscription_base_sum + domain_line_total
$preCouponTotal    = subscription_line_totals + domain_line_total  // after plan discount
$templatePlanDiscount = max(0, $baseSubtotal - $preCouponTotal)    // price vs discount_price
$couponDiscount    = $coupon
    ? $coupon->computeDiscountCents($preCouponTotal)
    : 0;

invoice.subtotal_cents = $baseSubtotal
invoice.discount_cents = $templatePlanDiscount + $couponDiscount   // combined
invoice.total_cents    = max(0, $baseSubtotal - discount_cents_total)
invoice.coupon_id      = $coupon?->id
```

**Coupon stacks on top of plan/template discounts.** The coupon percentage/fixed applies to `preCouponTotal` (after plan discount), not the gross base.

---

## 4. How Invoice Is Created

Both invoice creation paths now include:

```php
\App\Models\Invoice::create([
    ...
    'discount_cents' => $couponDiscount,         // was always 0 before Phase 3
    'total_cents'    => max(0, $subtotal - $couponDiscount),
    'coupon_id'      => $coupon?->id,            // new FK column from Phase 1
]);
```

`Invoice::$fillable` already includes `coupon_id` (added in Phase 1).

---

## 5. How Usage Tracking Works

**Coupon usage is tracked at invoice settlement, not invoice creation.**

Tracking is in `InvoiceSettlementService::markPaid()`, inside the same DB transaction that marks the invoice as paid. It never fires in `CheckoutController`.

```php
// Inside markPaid() transaction, AFTER lockedInvoice.status check:
if ($lockedInvoice->coupon_id) {
    $coupon = Coupon::query()
        ->lockForUpdate()       // prevents race condition on concurrent settlements
        ->find($lockedInvoice->coupon_id);

    if ($coupon) {
        $coupon->increment('used_count');

        $subscriptionIds = $lockedInvoice->items
            ->where('item_type', 'subscription')
            ->pluck('reference_id')
            ->filter()->values()->all();

        if (!empty($subscriptionIds)) {
            $coupon->subscriptions()->syncWithoutDetaching($subscriptionIds);
        }
    }
}
```

### Design decisions

**Why at settlement, not at invoice creation?**
A customer can create an invoice (step 1) and then never pay (step 2). Tracking at creation would consume `used_count` on unpaid, abandoned invoices. Tracking at settlement ensures `used_count` only increments when money actually changes hands.

**`lockForUpdate()` on the coupon row**
Prevents two concurrent settlement calls (e.g. webhook retry + manual mark-paid) from double-incrementing `used_count`. The DB row lock serializes the increments.

**No re-validation of `max_uses` at settlement**
Once `coupon_id` is attached to an invoice, the discount is honored unconditionally. The customer was promised the discount when checkout was submitted. Refusing payment at settlement time because another user redeemed the coupon concurrently would be a poor UX. The `lockForUpdate()` only prevents the `increment` from racing — it does not reject the payment.

**Idempotency**
The existing guard `if ($lockedInvoice->status === 'paid') return;` at the top of the transaction ensures `used_count` is incremented **at most once** per invoice, even if `markPaid()` is called multiple times (e.g. by both a webhook and an admin action).

**`syncWithoutDetaching`** — uses the existing `coupon_subscription` pivot table. Allows a coupon to be tracked across multiple subscriptions without removing prior records.

---

## 6. Frontend Changes

### Added (checkout.blade.php)
```html
<!-- Inside #checkoutForm -->
<input type="hidden" name="coupon_code" id="couponCodeHidden" value="">
```

### Removed (checkout.blade.php)
```javascript
// REMOVED — hardcoded client-side discount logic:
function computeDiscount(code, base) {
    if (c === 'PROMO10') return Math.round(base * 0.10);
    if (c === 'WELCOME20') return 2000;
    if (c === 'FREE') return base;
    return 0;
}
```

### Replaced With
- `fetch POST /checkout/coupon/validate` — async, shows spinner on button
- Response `{ valid, discount_cents, message }` updates `window.__couponDiscountCents`
- `couponHidden.value` is populated with validated code OR cleared on failure
- Enter key in coupon input triggers the same flow
- Erasing the code clears state automatically

---

## 7. What Is Still Not Implemented

| Feature | Status | Phase |
|---------|--------|-------|
| Admin UI to create/edit/delete coupons | ❌ Not done | Phase 4 |
| Admin dashboard coupon list | ❌ Not done | Phase 4 |
| Sidebar nav link to coupons | ❌ Not done | Phase 4 |
| Coupon usage report | ❌ Not in scope | Future |
| Per-user coupon restriction | ❌ Not in scope | Future |
| Cart (processCart) coupon support | ❌ Not done | Phase 4 or separate |
| `InvoiceController::computeTotals()` coupon hookup | ❌ Placeholder `$discount=0` remains | Phase 4 optional |

---

## 8. Is Phase 4 Ready?

**Yes.** Phase 4 (Admin CRUD) requires only the admin layer — the data model, validation logic, and checkout integration are all complete.

| Phase 4 Requirement | Available? |
|--------------------|------------|
| `coupons` table with all columns | ✅ Phase 1 |
| `Coupon` model with all methods | ✅ Phase 1 |
| Server-side coupon validation endpoint | ✅ Phase 2 |
| Checkout writes coupon_id + discount_cents | ✅ Phase 3 |
| Coupon usage tracked on purchase | ✅ Phase 3 |

**Phase 4 scope:**
1. `CouponController` (admin): index, create, store, edit, update, destroy
2. Views: `dashboard/coupons/index.blade.php`, `create.blade.php`, `edit.blade.php`
3. Routes in `routes/dashboard.php`
4. Nav link in sidebar
5. 20+ translation keys in `DashboardTranslationsSeeder`

---

## Security Verification (Step 10)

| Check | Status |
|-------|--------|
| Discount computed server-side only | ✅ `computeDiscountCents()` called in controller |
| Subtotal recomputed from DB items, not from request | ✅ `array_reduce($items, ...)` and `$subscriptionBaseSum` |
| Coupon re-validated on submit even if API said valid | ✅ `Coupon::usable()->where('code', ...)` re-runs on every submit |
| Frontend `discount_cents` value never read by server | ✅ Only `coupon_code` (string) is read from request |
| Hidden field tampering gives no extra discount | ✅ Server ignores any value except the code string |

---

## Summary

```
Phase 1 — Database + Model:    ✅ Complete
Phase 2 — Validation API:      ✅ Complete
Phase 3 — Checkout Integration: ✅ Complete (this phase)
Phase 4 — Admin CRUD:           ⏳ Ready to start
```

**Commands to run (if not already run after Phase 1+2):**
```bash
php artisan migrate
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

**No new commands needed for Phase 3** — only PHP and Blade files were modified.
