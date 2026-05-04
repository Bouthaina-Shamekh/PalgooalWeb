# نظام الفواتير (Invoice System)

> **آخر تحديث:** 2026-05-04  
> **الحالة:** مكتمل ومُعالَج بالكامل

---

## 1. نظرة عامة

نظام الفواتير هو جزء من لوحة تحكم الإدارة (`/dashboard/invoices`) ويُتيح:

- إنشاء فواتير وتعديلها وحذفها
- إدارة بنود الفاتورة (اشتراكات، نطاقات، وأنواع مخصصة)
- إجراءات جماعية (تغيير الحالة، تكرار، إرسال تذكير، حذف)
- ربط الفاتورة بطلب (`Order`) وتفعيل الاشتراك تلقائيًا عند الدفع

---

## 2. هيكل الملفات

```
app/
├── Http/Controllers/Admin/Management/
│   ├── InvoiceController.php       ← متحكم الفواتير الرئيسي
│   └── OrderController.php         ← متحكم الطلبات (مُفوَّض لـ OrderActivationService)
│
├── Models/
│   ├── Invoice.php                 ← موديل الفاتورة (SoftDeletes)
│   └── InvoiceItem.php             ← موديل بند الفاتورة (SoftDeletes + guarded relations)
│
├── Policies/
│   └── InvoicePolicy.php           ← صلاحيات الفواتير
│
└── Services/
    └── Billing/
        ├── InvoiceSettlementService.php   ← تسوية الدفع وتفعيل الطلب
        └── OrderActivationService.php     ← منطق تفعيل الطلب (مُستخرَج من OrderController)

database/migrations/
└── 2026_05_04_180756_add_soft_deletes_to_invoices_tables.php

resources/views/dashboard/management/invoices/
├── index.blade.php     ← قائمة الفواتير مع فلترة وإجراءات جماعية
├── create.blade.php    ← نموذج إنشاء فاتورة
├── edit.blade.php      ← نموذج تعديل فاتورة
├── show.blade.php      ← عرض تفاصيل الفاتورة
└── _form.blade.php     ← النموذج المشترك (create + edit)

public/assets/dashboard/js/
└── invoices-index.js   ← منطق الإجراءات الجماعية والـ checkboxes
```

---

## 3. موديل الفاتورة `Invoice`

### الحقول الرئيسية

| الحقل | النوع | الوصف |
|-------|-------|-------|
| `id` | bigint | المعرف |
| `number` | string | رقم الفاتورة الفريد (مثال: `INV-AB12CD34`) |
| `client_id` | FK | معرف العميل |
| `order_id` | FK nullable | معرف الطلب المرتبط |
| `status` | enum | `draft` / `unpaid` / `paid` / `cancelled` |
| `subtotal_cents` | int | الإجمالي الفرعي بالسنت |
| `discount_cents` | int | الخصم بالسنت |
| `tax_cents` | int | الضريبة بالسنت |
| `total_cents` | int | الإجمالي الكلي بالسنت |
| `currency` | string | كود العملة (افتراضي: `USD`) |
| `due_date` | date nullable | تاريخ الاستحقاق |
| `paid_date` | date nullable | تاريخ الدفع |
| `deleted_at` | timestamp | Soft delete |

> **ملاحظة:** جميع المبالغ تُخزَّن كأعداد صحيحة (سنت) لتجنب مشاكل الفاصلة العائمة.  
> للعرض: `$total_cents / 100`

### العلاقات

```php
$invoice->client        // → App\Models\Client
$invoice->order         // → App\Models\Order
$invoice->items         // → Collection<InvoiceItem>
```

---

## 4. موديل بند الفاتورة `InvoiceItem`

### الحقول

| الحقل | النوع | الوصف |
|-------|-------|-------|
| `item_type` | string | `subscription` / `domain` / أنواع مخصصة من config |
| `reference_id` | string | معرف الكيان المرتبط (ID اشتراك أو نطاق) |
| `description` | string | وصف البند |
| `qty` | int | الكمية |
| `unit_price_cents` | int | سعر الوحدة بالسنت |
| `total_cents` | int | الإجمالي = qty × unit_price_cents |
| `deleted_at` | timestamp | Soft delete |

### العلاقات المحروسة (Type-Guarded Relations)

**مهم:** لا تستخدم أسماء العلاقات الخام مباشرةً في `->with()`.

```php
// ✅ صحيح — للـ eager loading
$invoice->load(['items.subscriptionRelation.plan', 'items.domainRelation']);

// ✅ صحيح — للوصول للبيانات (يستخدم الـ accessor المحروس)
$item->subscription   // returns Subscription|null  (null إذا item_type !== 'subscription')
$item->domain         // returns Domain|null         (null إذا item_type !== 'domain')

// ❌ خطأ — العلاقات الخام بدون حراسة النوع
$item->subscriptionRelation   // قد يُرجع بيانات خاطئة على مجموعات مختلطة الأنواع
```

**السبب:** كلا العلاقتين تستخدمان `reference_id` كـ FK، لذا قد يتطابق `reference_id = 42` مع اشتراك أو نطاق بنفس الـ ID. الـ accessors تمنع هذا بفحص `item_type` أولًا.

---

## 5. الصلاحيات `InvoicePolicy`

النظام يعتمد على `ModelPolicy` الذي يُترجم اسم الدالة تلقائيًا إلى slug الدور:

| دالة Policy | slug الدور المطلوب |
|-------------|-------------------|
| `viewAny` | `invoices.viewAny` |
| `view` | `invoices.view` |
| `create` | `invoices.create` |
| `update` | `invoices.update` |
| `delete` | `invoices.delete` |
| `bulk` | `invoices.bulk` (مُعرَّف صراحةً) |

جميع إجراءات المتحكم محمية بـ `$this->authorize()`.

---

## 6. `InvoiceController` — تدفق العمليات

### إنشاء فاتورة (`store`)

```
Request → validate → validateReferenceIds() → DB::transaction {
    createInvoiceRecord()        ← ينشئ Invoice مع retry على تضارب رقم الفاتورة
    items()->create() × N        ← إنشاء البنود
    maybeActivateRelatedOrder()  ← تفعيل الطلب إن كانت الفاتورة مدفوعة
} → redirect
```

### توليد رقم الفاتورة (مقاوم للـ race conditions)

```php
// لا يُستخدم do-while مع EXISTS check (TOCTOU race condition)
// بدلًا من ذلك:

protected function createInvoiceRecord(array $attributes, int $maxAttempts = 5): Invoice
{
    for ($i = 0; $i < $maxAttempts; $i++) {
        try {
            return Invoice::create([...$attributes, 'number' => 'INV-' . Str::upper(Str::random(8))]);
        } catch (QueryException $e) {
            if ($i < $maxAttempts - 1 && str_contains($e->getMessage(), '23000')) continue;
            throw $e;
        }
    }
}
```

**8 أحرف عشوائية** = ~208 مليار تركيبة → احتمالية تضارب منخفضة جدًا.

### الإجراءات الجماعية (`bulk`)

| الإجراء | الوصف |
|---------|-------|
| `paid` | تغيير حالة الفواتير المحددة إلى مدفوعة |
| `unpaid` | تغيير الحالة إلى غير مدفوعة |
| `cancelled` | تغيير الحالة إلى ملغاة |
| `duplicate` | نسخ الفواتير مع رقم جديد وحالة `draft` |
| `reminder` | إرسال بريد تذكير للعملاء |
| `delete` | حذف ناعم (soft delete) |

**ملاحظة مهمة على `reminder`:** جمع بيانات البريد يحدث داخل الـ transaction، لكن الإرسال الفعلي يحدث **بعد** نجاح الـ commit لتجنب إرسال بريد عند rollback.

---

## 7. `OrderActivationService`

### الغرض

استُخرج من `OrderController::processActivation()` لإزالة نمط استدعاء متحكم من داخل متحكم آخر.

### المستخدمون

```php
// InvoiceController::maybeActivateRelatedOrder()
$this->activationService->activate($order);

// InvoiceSettlementService::markPaid()
$this->activationService->activate($order, $paymentMethod);

// OrderController::processActivation() — wrapper مُهمَل للتوافق
return $this->activationService->activate($order, $paymentMethod);
```

### تدفق التفعيل

```
activate($order, $paymentMethod)
│
├── قلب فواتير draft → unpaid
├── استخراج بيانات الدومين من بنود الطلب
├── (إن كان domain register/renew) → RegistrarProvisioningService::provisionOrderDomain()
│
├── إن كانت هناك subscription IDs في بنود الفاتورة:
│   └── تحديث الاشتراكات الموجودة (status=active, dates) + TenantProvisioningService::provision()
│
└── Fallback (template-based):
    ├── البحث عن اشتراك موجود لنفس العميل والخطة
    ├── إن وُجد → تحديثه
    └── إن لم يُوجد → إنشاء اشتراك جديد + SyncSubscriptionToProvider::dispatch()
```

---

## 8. `InvoiceSettlementService`

يُستخدم لتسوية دفع فاتورة من مسارات الدفع الخارجية (gateways).

```php
$service->markPaid($invoice, $paymentMethod);
```

**ما يفعله:**
1. يقفل الفاتورة بـ `lockForUpdate()` لمنع التسوية المزدوجة
2. يتحقق من عدم الدفع المسبق (`status === 'paid'`)
3. يُحدِّث الحالة والتاريخ
4. يُفعِّل الطلب المرتبط عبر `OrderActivationService`
5. إن لم يكن هناك طلب، يُزامن نطاق الفاتورة المستقلة

---

## 9. أنواع بنود الفاتورة (Item Types)

الأنواع تأتي من `config/invoices.php`:

```php
// config/invoices.php
return [
    'item_types' => [
        'subscription' => 'اشتراك استضافة',
        'domain'       => 'نطاق',
        // يمكن إضافة أنواع مخصصة هنا
    ],
];
```

---

## 10. الـ Soft Deletes

كلا الجدولين يدعمان الحذف الناعم:

```php
// حذف ناعم (لا يُزال من DB)
$invoice->delete();

// استرجاع
$invoice->restore();

// حذف دائم
$invoice->forceDelete();

// الاستعلام عن المحذوفات
Invoice::withTrashed()->find($id);
Invoice::onlyTrashed()->get();
```

---

## 11. نقاط الانتباه للمطورين

### ⚠️ لا تُضف `->where('item_type', ...)` على علاقات BelongsTo

هذا القيد يستهدف جدول الـ related model (مثل `subscriptions`) وليس جدول `invoice_items`. استخدم دائمًا الـ accessor:

```php
// ✅
$item->subscription  // يفحص item_type تلقائيًا

// ❌
$item->subscriptionRelation->where('item_type', 'subscription')
```

### ⚠️ المبالغ دائمًا بالسنت

```php
// ✅ صحيح
$invoice->total_cents = 15000;  // = 150.00 USD

// ❌ خطأ — لا تُخزِّن كسور عشرية
$invoice->total = 150.00;
```

### ⚠️ لا ترسل بريد داخل DB::transaction

```php
// ❌ خطأ — إذا rollback الـ transaction، البريد أُرسل بالفعل
DB::transaction(function() {
    // ...
    Mail::send(...);
});

// ✅ صحيح — اجمع البيانات داخل الـ transaction، أرسل بعده
$pendingEmails = [];
DB::transaction(function() use (&$pendingEmails) {
    $pendingEmails[] = [...];
});
foreach ($pendingEmails as $mail) {
    Mail::send(...);
}
```

### ⚠️ Eager loading العلاقات المحروسة

```php
// ✅ صحيح — استخدم أسماء العلاقات الخام للـ eager loading
$invoice->load(['items.subscriptionRelation.plan', 'items.domainRelation']);

// ثم الوصول عبر الـ accessors (تعمل بعد تحميل subscriptionRelation)
foreach ($invoice->items as $item) {
    $sub = $item->subscription;  // محروس، لن يُرجع null إن item_type !== 'subscription'
}
```

---

## 12. ADR — قرارات تصميمية

### ADR-INV-001: استخدام السنت لتخزين المبالغ

**القرار:** جميع المبالغ النقدية تُخزَّن كأعداد صحيحة (سنت).  
**السبب:** تجنب مشاكل دقة الفاصلة العائمة في العمليات الحسابية المالية.

### ADR-INV-002: Retry على unique constraint بدلًا من pre-check

**القرار:** `createInvoiceRecord()` يُعيد المحاولة عند `QueryException 23000` بدلًا من التحقق المسبق بـ `exists()`.  
**السبب:** `do { ... } while (exists())` يعاني من TOCTOU race condition في البيئات المتزامنة.

### ADR-INV-003: استخراج OrderActivationService

**القرار:** منطق تفعيل الطلب أُخرج من `OrderController` إلى `OrderActivationService`.  
**السبب:** استدعاء `app(OrderController::class)->processActivation()` من `InvoiceSettlementService` هو antipattern (business logic في controller + controller-from-service).

### ADR-INV-004: Type-guarded accessors لعلاقات InvoiceItem

**القرار:** `getSubscriptionAttribute()` و `getDomainAttribute()` تفحصان `item_type` قبل إرجاع الـ related model.  
**السبب:** كلا العلاقتين تستخدمان `reference_id` كـ FK مشترك؛ بدون الحراسة، قد يتطابق `reference_id=42` مع اشتراك ونطاق مختلفين.

### ADR-INV-005: إرسال البريد بعد commit الـ transaction

**القرار:** بريد تذكير الفاتورة يُرسَل بعد `DB::transaction()` وليس داخله.  
**السبب:** إرسال بريد داخل transaction يجعله غير قابل للتراجع إذا فشل الـ commit.
