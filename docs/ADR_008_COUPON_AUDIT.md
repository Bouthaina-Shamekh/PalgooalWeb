# ADR-008 Phase 0 — Coupon System Audit

**Date:** 2026-06-17  
**Type:** Pre-implementation audit (no code changes in this document)  
**Scope:** Full codebase inventory of coupon/discount infrastructure

---

## 1. هل نظام الكوبونات موجود جزئياً أم كاملاً؟

**جزئياً — نسبة الاكتمال: ~25%**

الموجود هو البنية التحتية الأساسية فقط (جداول + موديلات + علاقات). لا يوجد أي controller، لا route، ولا منطق تطبيق الكوبون على الفاتورة. الحقل `discount_cents` موجود في `invoices` لكنه **دائماً صفر** في كل مسارات الإنشاء الحالية.

---

## 2. الجداول الموجودة

| الجدول | تاريخ الإنشاء | الأعمدة |
|--------|--------------|---------|
| `coupons` | 2025-05-03 | `id`, `code` (unique), `discount_type` (fixed/percent), `discount_value`, `expires_at`, `timestamps` |
| `coupon_subscription` | 2025-08-18 | `id`, `coupon_id` (FK→coupons cascade), `subscription_id` (FK→subscriptions cascade), `timestamps` |

### الجداول المفقودة (غير موجودة)

| الجدول المتوقع | الحالة | التأثير |
|----------------|--------|---------|
| `coupon_usages` | ❌ غير موجود | لا يمكن تتبع كم مرة استُخدم الكوبون |
| أعمدة `max_uses` / `used_count` في `coupons` | ❌ غير موجودة | لا يمكن تقييد الاستخدام |
| `coupon_id` FK في `invoices` | ❌ غير موجود | لا يمكن ربط الكوبون بالفاتورة التي خصمت منه |
| `coupon_id` FK في `orders` | ❌ غير موجود | لا توجد وصلة بين الطلب والكوبون المستخدم |
| `minimum_amount` في `coupons` | ❌ غير موجود | لا يمكن تقييد الكوبون بمبلغ أدنى |

### ملاحظة مهمة — `invoices.discount_cents`

العمود موجود ومُهيأ بشكل صحيح (`integer, default 0`). لكن في **جميع** مسارات إنشاء الفواتير، يُكتب كصفر بشكل ثابت:

```php
// InvoiceController::computeTotals() — السطر 388
$discount = 0; // ادمج كوبونات/خصومات هنا لاحقًا
return ['discount_cents' => $discount, ...];

// CheckoutController::process() — السطر 276
'discount_cents' => 0,  // (domain-only path)
```

---

## 3. العلاقات الموجودة

| من | العلاقة | إلى | الملف |
|----|---------|-----|-------|
| `Coupon` | `belongsToMany` | `Subscription` | `app/Models/Coupon.php` |
| `Subscription` | `belongsToMany` | `Coupon` | `app/Models/Tenancy/Subscription.php` |

### العلاقات المفقودة

| العلاقة المطلوبة | السبب |
|-----------------|-------|
| `Invoice` → `Coupon` | ربط الكوبون بالفاتورة المخصومة |
| `Order` → `Coupon` | ربط الطلب بالكوبون المطبّق |
| `Coupon` → usages / usage_count | تتبع الاستخدام |

---

## 4. ما الذي يعمل فعلاً؟

| المكوّن | الحالة |
|---------|--------|
| `coupons` table موجودة ومُنشأة | ✅ |
| `coupon_subscription` pivot table | ✅ |
| `Coupon` model مع fillable و relationship | ✅ |
| `Subscription::coupons()` relationship | ✅ |
| `invoices.discount_cents` column | ✅ (موجود، مقيّد بصفر) |
| `CouponPolicy` (فارغة تمامًا) | ✅ هيكل فقط |

---

## 5. ما الذي لا يعمل؟

| المكوّن | الحالة | التفاصيل |
|---------|--------|----------|
| CouponController | ❌ غير موجود | لا CRUD للمشرف |
| Routes للكوبونات | ❌ لا يوجد أي route | لا `/admin/coupons` ولا `/api/coupon/validate` |
| Admin UI لإدارة الكوبونات | ❌ غير موجودة | لا view في `dashboard/coupons/` |
| API endpoint للتحقق من كوبون | ❌ غير موجود | الواجهة تحتاجه لتطبيق الخصم قبل submit |
| تطبيق الكوبون على الفاتورة | ❌ معطّل | `discount_cents` = 0 دائمًا |
| تحقق من صلاحية الكوبون (expires_at) | ❌ لا يُستخدم | العمود موجود لكن لا أحد يقرأه |
| حد الاستخدام (max_uses) | ❌ العمود غير موجود | — |
| تتبع الاستخدام | ❌ جدول coupon_usages غير موجود | — |
| ربط الكوبون بالفاتورة/الطلب | ❌ لا FK | — |

---

## 6. أين يتوقف تدفق الكوبون؟

يتوقف **فورًا** عند نقطة الدخول — لا يوجد server-side validation على الإطلاق.

```
المستخدم يكتب كود الكوبون
         │
         ▼
[checkout.blade.php — JavaScript فقط]
   computeDiscount(code, base)
   → 3 أكواد hardcoded: PROMO10, WELCOME20, FREE
   → يحسب الخصم في الواجهة فقط
         │
         ▼
[updateTotals()] — يعرض المبلغ المخصوم للمستخدم بصريًا
         │
         ▼  ❌ الكوبون لا يُرسل للسيرفر ❌
         │
         ▼
[CheckoutController::process()]
   → يستقبل الطلب
   → يُنشئ Invoice بـ discount_cents = 0
   → الفاتورة تُحصَّل بالمبلغ الكامل
```

**المشاكل الثلاث:**

1. **الكوبون client-side فقط** — يغير العرض المرئي لكنه لا يُرسل للسيرفر
2. **الأكواد hardcoded في JavaScript** — لا صلة بجدول `coupons` في DB
3. **CheckoutController لا يقبل `coupon_code`** — لا `$request->input('coupon_code')` في أي مكان

---

## 7. أقل تعديل يجعل الكوبون يؤثر على الفاتورة

### الحد الأدنى للعمل (5 خطوات):

**الخطوة 1 — Migration جديدة:**
```php
// إضافة أعمدة لـ coupons table
$table->unsignedInteger('max_uses')->nullable();
$table->unsignedInteger('used_count')->default(0);
$table->boolean('is_active')->default(true);

// إضافة FK للفاتورة
// invoices table:
$table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
```

**الخطوة 2 — API endpoint للتحقق:**
```
POST /checkout/coupon/validate
{ "code": "SUMMER20", "subtotal_cents": 5000 }
→ { "valid": true, "discount_cents": 1000, "message": "..." }
```

**الخطوة 3 — تعديل checkout form:**
```html
<input type="hidden" name="coupon_code" id="couponCodeHidden">
<!-- عند الضغط على تطبيق: validate من API، ثم احفظ الكود في hidden field -->
```

**الخطوة 4 — تعديل CheckoutController::process():**
```php
// استقبال وتطبيق الكوبون
$couponCode = $request->input('coupon_code');
$coupon = $couponCode ? Coupon::where('code', $couponCode)->valid()->first() : null;
$discountCents = $coupon ? $coupon->computeDiscount($subtotalCents) : 0;

// كتابة discount_cents في الفاتورة
'discount_cents' => $discountCents,
'total_cents'    => $subtotalCents - $discountCents,
```

**الخطوة 5 — تتبع الاستخدام:**
```php
$coupon?->increment('used_count');
$coupon?->subscriptions()->attach($subscription->id); // pivot موجود
```

**الخطوة 6 — Admin CRUD:**
- CouponController (index/create/store/edit/update/destroy)
- Views: `dashboard/coupons/index.blade.php` + `create.blade.php` + `edit.blade.php`

---

## 8. المخاطر

| الخطر | الخطورة | التفاصيل |
|-------|---------|----------|
| **الخصم client-side قابل للتزوير** | 🔴 عالية | المستخدم يمكنه تعديل `window.__couponDiscountCents` في console وتغيير العرض المرئي. لكن الفاتورة الفعلية تُنشأ بالمبلغ الكامل من السيرفر (لذا المخاطرة المالية الحالية = صفر، لأن السيرفر يتجاهل الخصم) |
| **Race condition على max_uses** | 🟡 متوسطة | إذا لم يُستخدم database-level locking، قد يتجاوز الكوبون حد الاستخدام عند requests متزامنة |
| **لا audit trail للخصم** | 🟡 متوسطة | لا يمكن معرفة أي كوبون خفّض أي فاتورة (لا FK في invoices) |
| **الأكواد hardcoded في JS** | 🟠 مرتفعة | PROMO10, WELCOME20, FREE — مكشوفة للعامة بقراءة source code |
| **لا حماية من replay attacks** | 🟠 مرتفعة | لا `coupon_usages` table → نفس المستخدم يمكنه استخدام الكوبون مرات غير محدودة |

---

## 9. الـ Technical Debt الموجود

| الدين | الخطورة | المصدر |
|-------|---------|--------|
| أكواد كوبون hardcoded في JavaScript (PROMO10, WELCOME20, FREE) | 🔴 | `checkout.blade.php:2280` |
| `$discount = 0; // ادمج كوبونات هنا لاحقًا` | 🟡 | `InvoiceController.php:388` — TODO مُقيَّد بكود |
| `livewire/checkout-client.blade.php` يحتوي `wire:click="applyCoupon"` لكن لا Livewire component PHP موجود | 🔴 | `checkout-client.blade.php:451` — dead code كامل |
| `CouponPolicy` فارغة تمامًا | 🟢 | مقبول — تُستكمل مع CRUD |
| `coupon_subscription` pivot موجود لكن لا أحد يكتب إليه | 🟡 | البنية جاهزة، التطبيق مفقود |

---

## 10. خطة ADR-008 المقترحة

### Phase 1 — بنية البيانات (Database Schema Completion)
**Migration جديدة تضيف:**
- `coupons.max_uses` (nullable) + `coupons.used_count` (default 0) + `coupons.is_active` (default true) + `coupons.minimum_amount_cents` (nullable)
- `invoices.coupon_id` (nullable FK → coupons)

**Model updates:**
- `Coupon::computeDiscount(int $subtotalCents): int` — يحسب الخصم حسب النوع
- `Coupon` scope `valid()` — is_active=true, not expired, used_count < max_uses (إذا كان محدوداً)
- `Invoice` → `belongsTo(Coupon::class)`

### Phase 2 — Server-side Coupon Validation API
**Endpoint جديد:**
```
POST /checkout/coupon/validate
```
**يتحقق من:**
1. الكود موجود في DB
2. `is_active = true`
3. `expires_at` لم يمر
4. `used_count < max_uses` (إذا كان محدوداً)
5. `subtotal >= minimum_amount_cents` (إذا كان مضروباً)

**يرجع:** `{valid, discount_cents, message}`

### Phase 3 — Checkout Integration
**تعديلات CheckoutController:**
- استقبال `coupon_code` من POST
- التحقق من الكوبون server-side (لا ثقة بالـ frontend)
- كتابة `discount_cents` و `coupon_id` في الفاتورة
- `increment('used_count')` بعد إنشاء الفاتورة
- `coupon->subscriptions()->attach($subscription->id)` إذا كان الشراء اشتراكاً

**تعديلات Frontend:**
- استبدال `computeDiscount()` hardcoded بـ fetch إلى API
- إضافة `<input type="hidden" name="coupon_code">` في الـ form

### Phase 4 — Admin CRUD
**ملفات جديدة:**
- `app/Http/Controllers/Admin/CouponController.php` (index, create, store, edit, update, destroy)
- `resources/views/dashboard/coupons/index.blade.php`
- `resources/views/dashboard/coupons/create.blade.php`
- `resources/views/dashboard/coupons/edit.blade.php`
- Routes في `routes/dashboard.php`
- Nav link في sidebar
- Translations في `DashboardTranslationsSeeder`

### Phase 5 — Cleanup
- حذف الأكواد hardcoded من `checkout.blade.php`
- إصلاح أو حذف `livewire/checkout-client.blade.php` (dead code)
- تفعيل `CouponPolicy`

---

## ملخص تنفيذي — ما قبل البدء بالتنفيذ

```
نسبة الاكتمال الحالية: ~25%
  ✅ مكتمل: DB schema أساسي (coupons + pivot)
  ✅ مكتمل: Models + Relationships
  ✅ مكتمل: invoices.discount_cents column
  ❌ مفقود: Controller + Routes + Admin UI
  ❌ مفقود: Server-side validation API
  ❌ مفقود: Checkout integration (server-side)
  ❌ مفقود: usage tracking (max_uses, used_count)
  ❌ مفقود: coupon_id FK على invoices

أكبر blocker: الكوبون client-side فقط والأكواد hardcoded في JS
الملفات المتأثرة بالتنفيذ: ~12 ملف جديد + 5 ملفات معدّلة
التقدير الواقعي: 4 phases × ~2-3 ساعات = 8-12 ساعة عمل

ADR-008: جاهزة للبدء بعد مراجعة هذا الـ Audit ✅
```

---

## الملفات المتأثرة

### ملفات جديدة (Phase 1–4)
| الملف | Phase |
|-------|-------|
| `database/migrations/XXXX_add_columns_to_coupons_table.php` | 1 |
| `database/migrations/XXXX_add_coupon_id_to_invoices_table.php` | 1 |
| `app/Http/Controllers/Front/CouponValidationController.php` | 2 |
| `app/Http/Controllers/Admin/CouponController.php` | 4 |
| `resources/views/dashboard/coupons/index.blade.php` | 4 |
| `resources/views/dashboard/coupons/create.blade.php` | 4 |
| `resources/views/dashboard/coupons/edit.blade.php` | 4 |

### ملفات معدّلة (Phase 1–5)
| الملف | التعديل |
|-------|---------|
| `app/Models/Coupon.php` | إضافة `computeDiscount()` + scope `valid()` |
| `app/Models/Invoice.php` | إضافة `coupon()` relationship |
| `app/Http/Controllers/Front/CheckoutController.php` | استقبال + تطبيق الكوبون server-side |
| `app/Http/Controllers/Admin/Management/InvoiceController.php` | دمج كوبون في `computeTotals()` |
| `resources/views/front/pages/checkout.blade.php` | استبدال hardcoded JS + إضافة hidden input |
| `routes/dashboard.php` | إضافة coupon resource routes |
| `resources/views/dashboard/layouts/partials/nav.blade.php` | رابط الكوبونات في sidebar |
| `database/seeders/DashboardTranslationsSeeder.php` | ترجمات الكوبونات |
