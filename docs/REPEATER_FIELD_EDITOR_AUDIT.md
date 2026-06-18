# Repeater Field Editor — Phase 0 Audit

**Date:** 2026-06-18  
**Scope:** فحص شامل لوضع محرر حقول Repeater في Dynamic Section Editor  
**Deliverable:** توصية Go / No-Go مبنية على الكود الفعلي  
**نتيجة الفحص:** ⚠️ **STALE WARNING — الـ Editor موجود بالفعل**

---

## النتيجة الجوهرية (اقرأها أولاً)

**الـ Repeater Field Editor مُنجَز بالكامل** — Phase 5C اكتمل ولم يُوثَّق في تعليق `SectionDefinitionField.php`.

التحذير الظاهر في `section_definitions/edit` (QW3 السابق):
```
"محرر حقول Repeater غير متاح بعد. التوليد في Blade مدعوم، لكن التحرير من لوحة التحكم محدود."
```
هذا التحذير **غير صحيح**. المحرر موجود ومكتمل على صفحة تحرير الأقسام (`pages/sections/edit`). التحقق أدناه في التفاصيل.

---

## القسم ١ — المشكلة الأصلية

### ما كان مُعتقَداً

تعليق في `SectionDefinitionField.php` line 37:
```
Editor rendering and save/load pipeline are deferred to Phase 5B.
```

وكان هذا يعني أن حقل `repeater` مُعرَّف في الـ schema لكن بدون واجهة تحرير في لوحة التحكم.

### ما يوجد فعلاً

Phase 5C اكتملت بالكامل في ملفات متعددة دون تحديث التعليق في الـ Model:

| الطبقة | الملف | الحالة |
|-------|-------|-------|
| Model schema | `SectionDefinitionField::repeaterItemSchema()` | ✅ مكتمل |
| Normalizer | `DynamicSectionContentNormalizer::normalizeRepeaterField()` | ✅ مكتمل |
| Editor renderer | `DynamicSectionEditorRenderer::buildRepeaterPayload()` | ✅ مكتمل |
| Blade partial | `fields/repeater.blade.php` | ✅ مكتمل |
| Item partial | `fields/repeater-item.blade.php` | ✅ مكتمل (كامل + Icon Picker) |
| JavaScript | `initDynamicRepeaters()` في `workspace.blade.php` | ✅ مكتمل (~350 سطر) |

---

## القسم ٢ — بنية البيانات

### ٢.١ — Schema column (SectionDefinitionField)

```php
// $field->schema (cast: 'array')
[
    'item_schema' => [
        ['key' => 'title',  'type' => 'text',  'label' => 'العنوان', 'required' => true,  'translatable' => true],
        ['key' => 'image',  'type' => 'media', 'label' => 'الصورة',  'required' => false, 'translatable' => false],
        ['key' => 'active', 'type' => 'boolean','label' => 'مفعّل',   'required' => false, 'translatable' => false],
    ]
]
```

**V1 allowlist** لأنواع الـ sub-fields:
`text`, `textarea`, `url`, `media`, `boolean`, `select`

**محظور:** `repeater` (no nesting) و `richtext` و `number`

### ٢.٢ — Content JSON (section_translations.content)

```json
{
  "fieldKey": [
    { "title": "نص العنصر الأول", "image": 15, "active": true },
    { "title": "نص العنصر الثاني", "image": null, "active": false }
  ]
}
```

- قيم `media` sub-fields مخزنة كـ Media ID (integer) أو null
- النمط متوافق مع ADR-005 Wave 3 لأنها داخل JSON وليست عموداً مباشراً

### ٢.٣ — Scope: shared vs translatable

- **translatable repeater**: كل locale يحفظ array خاصة به
- **shared repeater**: يُحرَّر في default locale فقط، ويُنسخ لبقية الـ locales تلقائياً عبر `syncDynamicDefinitionSharedContent()` — لكن هذا غير مدعوم حالياً في shared repeaters (تعليق في `DynamicSectionEditorRenderer`)

---

## القسم ٣ — مسار الحفظ (Save Path)

### الرحلة الكاملة

```
POST /dashboard/pages/{page}/sections/{section}
    │
    ├─ SectionController::update()
    │   └─ normalizeDefinitionLinkedTranslations()
    │       └─ DynamicSectionContentNormalizer::normalize()
    │           └─ normalizeRepeaterField($raw, $itemSchema)
    │               └─ normalizeRepeaterItem($row, $itemSchema)
    │
    └─ section_translations.content = {fieldKey: [{...}, {...}]}
```

### ما يُنفَّذ في normalizeRepeaterField

```php
// تحول: translations[ar][content][items][0][title] → ['title' => 'القيمة']
// يُحذف أي sub-field غير موجود في item_schema
// يُعالَج media: قيمة موجبة → integer | null
// isEmptyItem(): يحذف الصفوف الفارغة تماماً
```

**الخلاصة:** مسار الحفظ مكتمل ومُختبَر.

---

## القسم ٤ — مسار القراءة / العرض (Read Path)

### Editor (لوحة التحكم)

```
SectionController::edit()
    └─ buildEditorState($section, $languages)
        └─ SectionEditorDataFactory::make()
            └─ DynamicSectionEditorRenderer::buildForSection()
                └─ buildRepeaterPayload()
                    └─ resolveRepeaterItems() → old() → saved → []
                        └─ returns: $field['items'] = [{...}, {...}]
```

### Frontend (واجهة العميل)

```
SectionDefinitionFrontendViewDataFactory
    └─ normalizeContent() → SectionQueryResolver::resolve()
        └─ $data['fieldKey'] = [{...}, {...}]
```

الـ Blade view يقرأ `$data['fieldKey']` كـ PHP array ويُكرّر عليه.

---

## القسم ٥ — واجهة المحرر الحالية

### ٥.١ — الـ Partial الرئيسي: `fields/repeater.blade.php`

**Header:** Label + Shared badge (إذا كان scope=shared) + زر "Add Item" أعلى اليمين

**حالة فارغة:** `[data-dynamic-repeater-empty]` — يظهر عند غياب items، يختفي تلقائياً عند الإضافة

**قائمة العناصر:** `[data-dynamic-repeater-items]` — كل عنصر من نوع `repeater-item`

**Template للـ JS:** `<template data-dynamic-repeater-template>` — يستخدمه `initDynamicRepeaters` عند Add

**Footer add button:** يظهر فقط بعد وجود عنصر واحد على الأقل

**Empty schema state:** رسالة مُوجَّهة للمطور (لا sub-fields بعد) — بدون زر Add

### ٥.٢ — الـ Item partial: `fields/repeater-item.blade.php`

**Header:** Toggle expand/collapse + Duplicate button + Remove button (أحمر)

**Body (grid-cols-2):** حقل لكل sub-field حسب نوعه:

| Sub-field type | Rendering |
|---------------|-----------|
| `text` | `<input type="text">` |
| `textarea` | `<textarea rows="3">` |
| `url` | `<input type="url" placeholder="https://">` |
| `boolean` | hidden 0 + checkbox value=1 |
| `select` | `<select>` من options المُحدَّدة في الـ field definition |
| `media` | hidden input + `btn-open-media-picker` + preview div |

**Icon Picker Card:** يُفعَّل تلقائياً إذا وُجد `icon_source` + `icon_class` في item_schema — يوفر:
- Preview box (Tabler icon أو صورة من Media)
- "Icon Library" button
- "Upload SVG" button
- "Clear" link

### ٥.٣ — JavaScript: `initDynamicRepeaters()` (workspace.blade.php ~line 2561)

**الوظائف المُنجَزة:**

```javascript
window.initDynamicRepeaters = function(scope) {
    // يجد كل [data-dynamic-repeater] في الـ scope
    // لكل repeater:
    //   bindItem(item)          — bind toggle + remove + duplicate + media + icon
    //   reindexItems()          — يُعيد ترقيم name attributes (__INDEX__ → 0,1,2...)
    //   addButtons → Add Item   — يستنسخ <template>, يُلحق بـ list, يُشغّل bindItem + reindexItems
    //   duplicateButton         — يُنسخ item, يُصفّي input IDs, يُلحق بعد الأصل, reindex
    //   removeButton            — يُزيل item من DOM, reindex
}
```

**يُستدعى في:** `workspace.blade.php` line 473 بعد أول render، وبعد كل AJAX update.

---

## القسم ٦ — الـ Shared Repeater: وضع خاص

**المشكلة الحالية:** `syncDynamicDefinitionSharedContent()` في `SectionController` يعمل مع الـ scalar fields فقط (يأخذ قيمة من first locale ويُوزّعها). للـ repeaters لا يوجد منطق Sync بسبب:
- الـ array-to-array replication لم يُنجَز بعد للـ shared repeaters
- تعليق في `DynamicSectionEditorRenderer`: "Replica inputs are not emitted: array-valued fields cannot be propagated via the scalar hidden-input replication pattern"

**الحل الحالي:** shared repeaters تُحرَّر من الـ default locale tab فقط. البيانات المحفوظة **لا تُنسَخ تلقائياً** للـ locales الأخرى.

**الأثر:** إذا كان الـ repeater field scope=`shared`، التغييرات من default locale لا تظهر في الـ frontend للـ locales الأخرى إلا إذا كان الـ frontend يقرأ من default locale مباشرة.

**التوصية:** استخدم `scope: translatable` للـ repeater fields في V1 لتجنب هذا السلوك.

---

## القسم ٧ — مقارنة Phase 5A / 5B / 5C / 5D

| Phase | المكوّن | الحالة | الملاحظة |
|-------|---------|--------|----------|
| **5A** | Schema foundation: `schema` column + `repeaterItemSchema()` | ✅ مكتمل | في model |
| **5B** | Editor UI (كان مُؤجَّلاً) | ✅ مكتمل | أُنجز كـ Phase 5C |
| **5C** | Generic repeater partial + JS | ✅ مكتمل | `fields/repeater.blade.php` + `initDynamicRepeaters()` |
| **5D** | Normalizer: `normalizeRepeaterField()` | ✅ مكتمل | في DynamicSectionContentNormalizer |

**ملاحظة:** التعليق في `SectionDefinitionField.php` ("deferred to Phase 5B") قديم ولا يعكس الواقع.

---

## القسم ٨ — المخاطر المتبقية

### خطر منخفض — التحذير الخاطئ في sidebar
**الموضع:** `section_definitions/edit.blade.php` line 417-425 (QW3)  
**المشكلة:** يُظهر للمطور رسالة تقول المحرر غير متاح، وهذا مُضلِّل  
**الإصلاح:** تحديث النص ليوضح أن المحرر متاح من صفحة تحرير الأقسام

### خطر منخفض — shared repeater sync
**المشكلة:** shared repeaters لا تُنسَخ قيمها للـ locales الأخرى  
**الأثر:** محدود — repeaters في الغالب تكون translatable  
**الإصلاح:** توثيق للمطور في hint text في الـ field editor

### خطر منخفض — `__()` بدلاً من `t()`
**الموضع:** `repeater.blade.php` و `repeater-item.blade.php` — كلهم يستخدمون `__()` للنصوص  
**الأثر:** النصوص ثابتة بالإنجليزية للمستخدمين العرب  
**الإصلاح:** استبدال بـ `t()` مع fallback إنجليزي

### خطر لا شيء — Runtime contract
الـ repeater data مُخزَّنة في `section_translations.content` كـ JSON array — لا يؤثر على أي ADR أو pipeline آخر.

---

## القسم ٩ — الإجابة على الأسئلة الخمسة

### هل نحتاج Migration؟
**لا.** `schema` column موجود في `section_definition_fields`. البيانات تُخزَّن في `section_translations.content` الموجود. لا تغيير في DB مطلوب.

### أين يُخزَّن الـ repeater؟
`section_translations.content` (JSON column) — نفس العمود الذي تُخزَّن فيه كل حقول الـ section الأخرى. المفتاح هو `field_key` والقيمة array من items.

### ما شكل الـ JSON النهائي؟
```json
{
  "hero_items": [
    {"title": "العنصر الأول", "icon": "ti ti-star", "is_active": true},
    {"title": "العنصر الثاني", "icon": "ti ti-heart", "is_active": false}
  ],
  "other_field": "قيمة عادية"
}
```

### ما هو الحد الأدنى الآمن للتنفيذ؟
لا تنفيذ مطلوب — المحرر موجود. المطلوب:
1. تحديث النص في `edit.blade.php` QW3 block
2. استبدال `__()` بـ `t()` في `repeater.blade.php` + `repeater-item.blade.php`
3. تحديث تعليق Phase 5B في `SectionDefinitionField.php`

### هل نبدأ فوراً؟
**نعم** — لكن المهمة هي **تصحيح توثيق** وليس بناء محرر جديد.

---

## Go / No-Go

### ✅ GO — بدون تحفظات

**السبب:** الـ Repeater Field Editor مكتمل بالكامل (Phase 5C). الكود الموجود يشمل:
- Backend payload builder (`buildRepeaterPayload`)
- Old() → saved → [] resolution chain
- Add / Remove / Duplicate items
- Toggle collapse/expand
- جميع V1 sub-field types (text, textarea, url, media, boolean, select)
- Icon Picker Card (Tabler + Media)
- Reindex names بعد كل mutation

**ما يحتاج اهتماماً (بالترتيب):**

| الأولوية | المهمة | الملف | نوع التغيير |
|---------|-------|-------|------------|
| 1 | تحديث QW3 warning message | `edit.blade.php` lines 417-425 | نص فقط |
| 2 | `__()` → `t()` في repeater partials | `repeater.blade.php` + `repeater-item.blade.php` | 12-15 استبدال |
| 3 | تحديث تعليق Phase 5B | `SectionDefinitionField.php` line 37 | تعليق فقط |
| 4 | توثيق shared repeater limitation | hint text في field editor | نص فقط |

**لا تغييرات مطلوبة في:**
- `DynamicSectionEditorRenderer` ❌
- `DynamicSectionContentNormalizer` ❌
- `workspace.blade.php` JavaScript ❌
- `SectionController` ❌
- أي migration ❌
- Runtime contract ❌

---

## ملاحظة: كيف تستخدم Repeater Editor الآن

الـ Repeater editor يعمل تلقائياً على أي section مرتبطة بـ definition يحتوي على حقل من نوع `repeater` **وعنده item_schema محدد**.

الخطوات من A إلى Z:
1. في `section_definitions/{id}/fields/create` — أنشئ حقلاً من نوع `repeater`
2. في الـ Repeater Item Schema editor — أضف sub-fields (key + type + label + required)
3. انتقل لـ `pages/{page}/sections/{section}/edit` حيث الـ section مرتبطة بهذا الـ definition
4. الـ repeater widget يظهر تلقائياً مع Add / Remove / Duplicate
5. اضغط Save — البيانات تُحفظ عبر normalizer
6. في الـ Blade view: `$data['fieldKey']` هو array من items جاهز للـ `@foreach`
