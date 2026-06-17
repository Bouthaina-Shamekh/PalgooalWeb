# Section Definition Edit — QW4 Implementation Report

**Date:** 2026-06-18  
**Scope:** إضافة قائمة حقول read-only في Info tab  
**Status:** ✅ Completed  

---

## 1. الملف المعدَّل

| الملف | نوع التعديل |
|-------|-------------|
| `resources/views/dashboard/section_definitions/form.blade.php` | إضافة block جديد قبل `</div>` الأخير للـ grid |

لا Controller. لا Model. لا Migration. لا `edit.blade.php`. لا Monaco.

---

## 2. أين أُضيف القسم

أُضيف في نهاية `<div class="grid grid-cols-12 gap-x-6 gap-y-4">` في `form.blade.php`، بعد block "الحالة والرؤية" مباشرة وقبل `</div>` الذي يغلق الـ grid.

الموضع بالأسطر: بعد line 222 (نهاية الحالة والرؤية)، قبل line 271 (`</div>` الـ grid).

---

## 3. ماذا يعرض

القسم يظهر **فقط** إذا:
- `$sectionDefinition->exists` — تعديل وليس إنشاء
- `$bladeFields ?? collect()` — توجد حقول محملة

**المحتوى:**

```
┌─────────────────────────────────────────────────┐
│  ⊞ الحقول (5)              إدارة الحقول ←      │
│                                                  │
│  [ت title text] [م image media] [ت subtitle text مطلوب] │
│  [م bg_color text] [ت items repeater ⚠️]        │
└─────────────────────────────────────────────────┘
```

لكل حقل chip يعرض:
- **scope badge**: `ت` (أزرق = translatable) أو `م` (رمادي = shared) مع tooltip
- **`field_key`** بـ font-mono
- **`field_type`** بلون رمادي خفيف
- **Required badge** (أحمر خفيف) إذا كان مطلوباً — نفس شرط QW2 المزدوج

رابط "إدارة الحقول ←" في الأعلى يوجه لـ `section_definitions.fields.index`.

---

## 4. هل أُضيف query جديد؟

**لا.** القسم يستخدم `$bladeFields` الذي يُعرَّف مسبقاً في `edit.blade.php`:

```php
// في edit.blade.php (line 43-46) — موجود قبل @include form
$bladeFields = $sectionDefinition->fields()
    ->orderBy('sort_order')->orderBy('id')
    ->get(['field_key', 'field_type', 'field_scope', 'is_required', 'validation_rules', 'default_value']);
```

المتغير `$bladeFields` متاح في scope الـ `@include` تلقائياً في Laravel Blade. صفر queries إضافية.

`$fieldsCount` أيضاً متاح من نفس scope وُيستخدم للعداد مع fallback آمن:
```php
$fieldsCount ?? ($bladeFields ?? collect())->count()
```

**في صفحة create** (حيث `$bladeFields` غير معرَّف): `@if($sectionDefinition->exists ...)` يكون `false` فيُخفى القسم كاملاً — آمن تماماً.

---

## 5. هل تغير runtime contract؟

**لا.** لم يتغير شيء في:
- `SectionRenderer`
- `SectionDefinitionRuntimeResolver`
- `SectionDefinitionFrontendViewDataFactory`
- أي Blade view في `front/`
- المتغيرات المحظورة (`$fields`، `$sharedData`، `$translatableData`) — لم تُستخدم

---

## 6. هل يمكن متابعة QW5 لاحقاً؟

**نعم.** QW5 (Danger Zone بـ Bootstrap modal) يُضاف في الـ sidebar داخل `edit.blade.php` — ملف مختلف تماماً. لا تعارض مع هذا التعديل.

---

## ملاحظة: سلامة على صفحة create

`form.blade.php` تُستخدم في create أيضاً (`create.blade.php`). الشرط الحارس:

```blade
@if($sectionDefinition->exists && ($bladeFields ?? collect())->isNotEmpty())
```

- `$sectionDefinition->exists` = `false` في create → القسم كاملاً مخفي
- `$bladeFields ?? collect()` → fallback آمن لو المتغير غير معرَّف

لا خطر من UndefinedVariable أو قراءة خاطئة.
