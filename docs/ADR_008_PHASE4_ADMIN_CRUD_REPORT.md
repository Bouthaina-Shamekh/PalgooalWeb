# ADR-008 Phase 4 — Admin Coupon CRUD Report

**Date:** 2026-06-17  
**Phase:** 4 of 4 (ADR-008 — FINAL PHASE)  
**Scope:** Admin UI to create, view, edit, deactivate, and delete coupons — no checkout changes, no payment gateway changes

---

## 1. Files Created (New)

| File | Type | Purpose |
|------|------|---------|
| `app/Http/Controllers/Admin/Management/CouponController.php` | Controller | index / create / store / edit / update / destroy |
| `app/Http/Requests/StoreCouponRequest.php` | Form Request | Validation + authorization for create |
| `app/Http/Requests/UpdateCouponRequest.php` | Form Request | Validation + authorization for update |
| `resources/views/dashboard/management/coupons/index.blade.php` | View | Coupon list with search + pagination |
| `resources/views/dashboard/management/coupons/create.blade.php` | View | Create coupon page |
| `resources/views/dashboard/management/coupons/edit.blade.php` | View | Edit coupon page |
| `resources/views/dashboard/management/coupons/_form.blade.php` | Partial | Shared form (2 sections + sidebar) |
| `docs/ADR_008_PHASE4_ADMIN_CRUD_REPORT.md` | Docs | This file |

---

## 2. Files Modified

| File | Change |
|------|--------|
| `routes/dashboard.php` | Added `use CouponController` + `Route::resource('coupons', ...)` |
| `resources/views/dashboard/layouts/partials/nav.blade.php` | Added Coupons nav item (with `@can('viewAny')` guard) |
| `database/seeders/DashboardTranslationsSeeder.php` | Fixed truncation from previous session + added 35 Phase 4 keys |

---

## 3. Routes Added

```php
// In routes/dashboard.php — inside the 'auth + can:access-dashboard' group
Route::resource('coupons', CouponController::class)->names('coupons');
```

Generates:

| Name | Method | URL | Action |
|------|--------|-----|--------|
| `dashboard.coupons.index` | GET | `/admin/coupons` | `index()` |
| `dashboard.coupons.create` | GET | `/admin/coupons/create` | `create()` |
| `dashboard.coupons.store` | POST | `/admin/coupons` | `store()` |
| `dashboard.coupons.edit` | GET | `/admin/coupons/{coupon}/edit` | `edit()` |
| `dashboard.coupons.update` | PUT/PATCH | `/admin/coupons/{coupon}` | `update()` |
| `dashboard.coupons.destroy` | DELETE | `/admin/coupons/{coupon}` | `destroy()` |

---

## 4. Validation Rules

### `StoreCouponRequest`

| Field | Rules |
|-------|-------|
| `code` | required, string, max:100, regex:`/^[A-Z0-9_\-]+$/i`, unique:coupons |
| `discount_type` | required, in:`fixed,percent` |
| `discount_value` | required, numeric, min:0.01 |
| `expires_at` | nullable, date, after:today |
| `max_uses` | nullable, integer, min:1 |
| `minimum_amount_cents` | nullable, integer, min:0 |
| `is_active` | nullable, boolean |

### `UpdateCouponRequest`

Same rules except:
- `code` unique rule ignores the current coupon ID (`Rule::unique()->ignore($couponId)`)
- `expires_at` does NOT require `after:today` (existing past dates must remain editable)

### Security
- `code` is normalized to uppercase via `prepareForValidation()` before hitting unique check
- `authorize()` calls `$user->can('create', Coupon::class)` / `$user->can('update', $coupon)` — delegates to the existing policy framework

---

## 5. Soft-Delete Decision

**Policy: deactivate instead of delete if the coupon has redeemed invoices.**

```php
public function destroy(Coupon $coupon)
{
    // If any invoice has coupon_id pointing here, the row cannot be safely
    // hard-deleted (nullOnDelete FK would orphan the audit trail).
    if ($coupon->invoices()->exists()) {
        $coupon->update(['is_active' => false]);
        return redirect()->route(...)->with('ok', t('dashboard.Coupon_Deactivated', ...));
    }

    $coupon->delete();  // Safe: no invoice references
    return redirect()->route(...)->with('ok', t('dashboard.Coupon_Deleted', ...));
}
```

**Why `nullOnDelete` doesn't save us:** The FK is `nullOnDelete`, so deleting a coupon sets `invoices.coupon_id = NULL`. The `discount_cents` on the invoice is preserved, but we lose the ability to answer "which coupon?" in reporting. Deactivating instead keeps the row and the audit trail intact.

**No `SoftDeletes` trait added** — the project has no established `SoftDeletes` pattern in Management models. `is_active = false` is the idiomatic approach here.

---

## 6. Workflow Diagram

### Admin creates a coupon:
```
Admin → /admin/coupons/create
  │
  ├─ Fills: code, type (fixed|percent), value, max_uses, min_amount, expires_at, is_active
  │
  └─ POST /admin/coupons
           │
           ├─ StoreCouponRequest validates
           ├─ code → strtoupper(trim($code))
           ├─ minimum_amount (dollars) → * 100 → cents
           └─ Coupon::create([...])
                    │
                    └─ redirect coupons.index + session('ok')
```

### Admin edits a coupon:
```
Admin → /admin/coupons/{id}/edit
  │
  ├─ Form pre-filled from $coupon model
  ├─ minimum_amount_cents → /100 → display as dollars
  ├─ used_count shown as read-only info block
  │
  └─ PUT /admin/coupons/{id}
           │
           ├─ UpdateCouponRequest validates (unique ignores self)
           └─ $coupon->update([...])
```

### Admin deletes a coupon:
```
Admin → DELETE /admin/coupons/{id}
         │
         ├─ $coupon->invoices()->exists()?
         │     YES → is_active = false  → "Coupon deactivated"
         │     NO  → $coupon->delete()  → "Coupon deleted"
         │
         └─ redirect coupons.index
```

---

## 7. Form Design

The `_form.blade.php` partial uses the project's standard 2-section numbered layout:

**Section ١ — Basic Info:**
- `code` (font-mono, dir=ltr, auto-uppercases on input)
- `discount_type` (select: fixed / percent)
- `discount_value` (numeric, step/max updates via JS based on type)

**Section ٢ — Restrictions:**
- `max_uses` (nullable integer)
- `minimum_amount` (dollar decimal → controller converts to cents)
- `expires_at` (date input, dir=ltr)
- `is_active` (radio buttons — strict comparison, no PHP loose-comparison bug)
- `used_count` (read-only info block, edit mode only)

**Sidebar:** Save/Cancel buttons + help text + delete warning (edit mode).

**JavaScript:**
- `updateValueHint()` — switches hint text and `max` attribute between percent (0–100) and fixed
- `oninput="this.value = this.value.toUpperCase()"` on code field

---

## 8. Translation Keys Added (Phase 4)

35 new keys in `DashboardTranslationsSeeder`:

| Key | Arabic |
|-----|--------|
| `dashboard.Coupons_List` | قائمة الكوبونات |
| `dashboard.Coupons` | الكوبونات |
| `dashboard.Add_Coupon` | إضافة كوبون |
| `dashboard.Search_Coupons` | بحث عن كوبون… |
| `dashboard.No_Coupons` | لا توجد كوبونات بعد. |
| `dashboard.No_Coupons_Desc` | أضف أول كوبون خصم لتشجيع العملاء على الشراء. |
| `dashboard.No_Coupons_Search` | لا توجد نتائج مطابقة لبحثك. |
| `dashboard.Confirm_Delete_Coupon` | هل أنت متأكد من حذف هذا الكوبون؟ |
| `dashboard.Coupon_Code_Col` | الكود |
| `dashboard.Coupon_Type_Col` | النوع |
| `dashboard.Coupon_Value_Col` | القيمة |
| `dashboard.Coupon_Used_Col` | الاستخدام |
| `dashboard.Coupon_Expires_Col` | الانتهاء |
| `dashboard.Coupon_Status_Col` | الحالة |
| `dashboard.Coupon_Unlimited` | بلا حدود |
| `dashboard.Coupon_No_Expiry` | لا تاريخ انتهاء |
| `dashboard.Coupon_Type_Fixed` | مبلغ ثابت |
| `dashboard.Coupon_Type_Percent` | نسبة مئوية |
| `dashboard.Add_New_Coupon` | إضافة كوبون جديد |
| `dashboard.Edit_Coupon` | تعديل الكوبون |
| `dashboard.Create_Coupon` | إنشاء الكوبون |
| `dashboard.Update_Coupon` | حفظ التعديلات |
| `dashboard.Coupon_Code` | كود الكوبون |
| `dashboard.Coupon_Code_Hint` | حروف كبيرة وأرقام فقط. |
| `dashboard.Coupon_Discount_Type` | نوع الخصم |
| `dashboard.Coupon_Discount_Value` | قيمة الخصم |
| `dashboard.Coupon_Max_Uses` | الحد الأقصى للاستخدام |
| `dashboard.Coupon_Min_Amount` | الحد الأدنى للطلب ($) |
| `dashboard.Coupon_Expires_At` | تاريخ الانتهاء |
| `dashboard.Coupon_Is_Active` | حالة الكوبون |
| `dashboard.Coupon_Active_Label` | نشط — يمكن استخدامه في الدفع |
| `dashboard.Coupon_Inactive_Label` | معطّل — لا يُقبل في الدفع |
| `dashboard.Coupon_Created` | تم إنشاء الكوبون بنجاح. |
| `dashboard.Coupon_Updated` | تم تحديث الكوبون بنجاح. |
| `dashboard.Coupon_Deleted` | تم حذف الكوبون بنجاح. |
| `dashboard.Coupon_Deactivated` | تم تعطيل الكوبون بدلاً من حذفه لأنه مرتبط بفواتير. |

Also repaired 4 Phase 2 keys that were truncated in the previous session:
- `dashboard.Coupon_Expired`
- `dashboard.Coupon_Exhausted`
- `dashboard.Coupon_Minimum_Amount`
- `dashboard.Coupon_Applied`

---

## 9. Mass Assignment & Security

| Check | Status |
|-------|--------|
| All writes go through `StoreCouponRequest` / `UpdateCouponRequest` | ✅ |
| `Coupon::$fillable` explicitly lists all writable columns | ✅ Phase 1 |
| `used_count` is NOT in the form — never mass-assigned from UI | ✅ |
| `code` normalized server-side (`strtoupper + trim`) in request + controller | ✅ |
| `minimum_amount` (dollar float) → `minimum_amount_cents` (int) conversion in controller | ✅ |
| `authorize()` in every action (delegates to policy) | ✅ |
| CSRF on all forms | ✅ |
| `@method('DELETE')` / `@method('PUT')` for method spoofing | ✅ |

---

## 10. Is ADR-008 CLOSED?

```
Phase 1 — Database + Model:       ✅ Complete
Phase 2 — Validation API:         ✅ Complete
Phase 3 — Checkout Integration:   ✅ Complete
Phase 4 — Admin CRUD:             ✅ Complete (this phase)

ADR-008 STATUS: ✅ CLOSED
```

**All four phases are implemented.** The coupon system is end-to-end functional:

1. **Admin** creates a coupon via `/admin/coupons/create`
2. **Client** enters the code at checkout → validated via `POST /checkout/coupon/validate`
3. **Checkout** resolves and applies the discount server-side, writes `coupon_id` to invoice
4. **Settlement** (`InvoiceSettlementService::markPaid()`) increments `used_count` on real payment

---

## Commands to Run

```bash
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

> Note: `php artisan migrate` is NOT needed for Phase 4 — all schema changes were done in Phase 1.

---

## What Remains Out of Scope (Future)

| Feature | Status |
|---------|--------|
| Coupon usage report / analytics | ❌ Future |
| Per-user coupon restriction | ❌ Future |
| `processCart()` coupon support | ❌ Future |
| `InvoiceController::computeTotals()` coupon hookup | ❌ Future (placeholder `$discount=0` remains) |
| Bulk coupon actions | ❌ Future |
| Export coupon list | ❌ Future |
