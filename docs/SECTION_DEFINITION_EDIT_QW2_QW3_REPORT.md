# Section Definition Edit — QW2 + QW3 Implementation Report

**Date:** 2026-06-18  
**Scope:** Field Reference Sidebar in `section-definitions/{id}/edit` → Blade tab  
**Status:** ✅ Completed  

---

## 1. الملف المعدَّل

| الملف | نوع التعديل |
|-------|-------------|
| `resources/views/dashboard/section_definitions/edit.blade.php` | تعديل بلوكين فقط |

لا ملفات أخرى تُعدَّل. لا Controller. لا Model. لا Migration. لا Seeder (استُخدم fallback مباشرة في `t()`).

---

## 2. ماذا أُضيف للـ Field Reference Sidebar

### التعديل الأول — توسيع `$bladeFields` query (سطر 45)

```php
// قبل:
->get(['field_key', 'field_type', 'field_scope']);

// بعد:
->get(['field_key', 'field_type', 'field_scope', 'is_required', 'validation_rules', 'default_value']);
```

أُضيفت 3 أعمدة للـ select:
- `is_required` — boolean مخصص للتحقق السريع
- `validation_rules` — JSON array مثل `["required", "max:200"]`
- `default_value` — JSON array (قيمة افتراضية للحقل)

لا يُضاف query إضافي — نفس الـ query الحالي فقط مع أعمدة أكثر.

### التعديل الثاني — تحسين كل صف في القائمة

**الصف الرئيسي (Main Row)** — محافظ على التخطيط تماماً:
- نقطة المؤشر (indicator dot) ← بدون تغيير
- Badge النطاق (ت/م) ← أُضيف `title` tooltip للوضوح
- `field_key` ← بدون تغيير
- `field_type` ← بدون تغيير
- **جديد**: Required badge (أحمر خفيف) — يظهر فقط إذا كان الحقل مطلوباً
- زر الإدراج (+) ← بدون تغيير

**Required Badge** — الشرط مزدوج الأمان:
```php
// يتحقق من كلاهما:
$fIsRequired = in_array('required', $fValRules)  // من validation_rules array
           ||  (bool)($f->is_required ?? false);  // من عمود is_required المخصص
```

**QW2 — Sub-row للتفاصيل** — يظهر فقط إذا كان هناك بيانات:
```
تحقق:  required | max:200 | string
الافتراضي: ""
```
- النص بحجم 10px font-mono
- Truncate بعد 45 حرفاً للـ default_value
- مُعالج للحالتين: `validation_rules` كـ array أو كـ string

**QW3 — Repeater Warning** — يظهر فقط لحقول بنوع `repeater`:
```
⚠️  محرر حقول Repeater غير متاح بعد. التوليد في Blade مدعوم، لكن التحرير من لوحة التحكم محدود.
```
- لون amber خفيف (`bg-amber-50`, `border-amber-200`, `text-amber-700`)
- يستخدم `$f->isRepeater()` — الـ method الموجودة في الموديل
- يستخدم `t('dashboard.Repeater_Editor_Not_Available', '...')` مع fallback مباشر

---

## 3. هل تغير scaffold logic؟

**لا.** `generateSnippet()` و `generateFullScaffold()` في Monaco IIFE (من line 452 فأكثر) لم يُلمسا. تمت الإضافة فقط في بلوك PHP `@foreach` في الـ sidebar. الـ scaffold generator لا يقرأ من `$bladeFields` PHP variable — يقرأ من `window.sdFieldsData` JavaScript array الذي يُبنى من الـ `data-*` attributes على أزرار الإدراج. تلك الـ attributes لم تتغير.

---

## 4. هل تغير runtime contract؟

**لا.** الـ runtime contract هو أن Blade views تستقبل `$data['key']`. لم يتم تعديل:
- `SectionRenderer`
- `SectionDefinitionRuntimeResolver`
- `SectionDefinitionFrontendViewDataFactory`
- أي Blade view في `front/`

---

## 5. هل Monaco تأثر؟

**لا.** التعديلات كلها في Blade/PHP قبل الـ `<style>` block على line 411. Monaco initialization IIFE يبدأ من line 452. AMD isolation أيضاً لم يُلمس. الـ `field-insert-btn` data attributes (المصدر الوحيد الذي يقرأه Monaco JS) لم تتغير.

---

## 6. تفاصيل التحقق من الصحة

| العنصر | النتيجة |
|--------|---------|
| Query `$bladeFields` — أعمدة جديدة | ✅ أُضيفت: `is_required`, `validation_rules`, `default_value` |
| Required badge — شرط مزدوج | ✅ يتحقق من `is_required` column و `validation_rules` array |
| `validation_rules` — معالجة آمنة | ✅ تُعالج كـ array أو string على حد سواء |
| `default_value` — truncate | ✅ مقتطعة عند 45 حرفاً + `…` |
| Scope badge — tooltip جديد | ✅ `title` attribute أُضيف بـ `t()` |
| Repeater warning — الشرط | ✅ `$f->isRepeater()` (method موجودة في Model) |
| Blade tab — Monaco insert buttons | ✅ لا تغيير على `data-key/type/scope` attributes |
| Scaffold generator | ✅ لم يُلمس |
| Controller / Models / Renderer | ✅ لم يُلمس |
| `$fields` / `$sharedData` / `$translatableData` | ✅ لم تُستخدم — محظورة بحق |

---

## 7. هل يمكن متابعة QW1/QW4 لاحقاً؟

**نعم، بدون أي تعارض.**

- **QW1** (استبدال `editor_mode` select ببadge) — يُعدّل `form.blade.php` فقط. لا علاقة بهذا التعديل.
- **QW4** (قائمة حقول خفيفة في Info tab) — يُعدّل `form.blade.php` أو الـ sidebar في Info tab. لا علاقة بالـ Blade tab sidebar.

الملف `edit.blade.php` منظم بوضوح في بلوكات منفصلة — Info pane (`sd-pane-info`) وBlade pane (`sd-pane-blade`). التعديلات هنا كلها داخل `sd-pane-blade` فقط.

---

## 8. مفاتيح الترجمة المُستخدَمة (fallback مباشر)

| المفتاح | الـ fallback المستخدم |
|---------|----------------------|
| `dashboard.Translatable` | `'قابل للترجمة'` |
| `dashboard.Shared` | `'مشترك'` |
| `dashboard.Required` | `'مطلوب'` |
| `dashboard.Validation` | `'تحقق'` |
| `dashboard.Default_Value` | `'الافتراضي'` |
| `dashboard.Repeater_Editor_Not_Available` | `'محرر حقول Repeater غير متاح بعد. التوليد في Blade مدعوم، لكن التحرير من لوحة التحكم محدود.'` |

جميعها مُضمَّنة كـ fallback في `t()` — لا يحتاج المشرف تشغيل Seeder لتظهر. إضافتها للـ `DashboardTranslationsSeeder` اختيارية لاحقاً.
