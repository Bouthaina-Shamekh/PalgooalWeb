# Repeater Snippet Generator — Improvement Report

**Date:** 2026-06-18  
**Scope:** تحسين توليد كود Blade لحقل repeater في `section-definitions/{id}/edit` → Blade tab → Field Reference Sidebar  
**Status:** ✅ Completed

---

## ١. أين كان Generator القديم

### الملف الوحيد المُعدَّل:
```
resources/views/dashboard/section_definitions/edit.blade.php
```

### الـ `->get([...])` query — line 45:
```php
// قبل:
->get(['field_key', 'field_type', 'field_scope', 'is_required', 'validation_rules', 'default_value']);

// بعد:
->get(['field_key', 'field_type', 'field_scope', 'is_required', 'validation_rules', 'default_value', 'schema']);
```

### `generateSnippet()` — inside `@verbatim` IIFE:
الدالة موجودة في الـ JavaScript IIFE داخل block `@verbatim`. تستقبل كائن الحقل `f` وتُرجع نص Blade.

### `generateFullScaffold()` — نفس IIFE:
تمر على جميع `fieldsData` وتبني scaffold كامل للـ section.

---

## ٢. ماذا تغير

### تغيير ١ — إضافة `schema` للـ Query

`$bladeFields` أصبح يجلب عمود `schema` (JSON column مع cast `array`). يُمرَّر لـ JS عبر:
```javascript
window.__sdEditorData = {
    fields: @json($bladeFields->toArray()),  // schema مُضمَّنة الآن
    ...
};
```

في JS: `f.schema` كائن يحتوي `item_schema` كـ array.

### تغيير ٢ — `generateSnippet()` للـ repeater

**قبل (generic):**
```javascript
} else if (type === 'repeater') {
    lines = [
        '@foreach (is_array($data[\'features\'] ?? null) ? $data[\'features\'] : [] as $featuresItem)',
        '    {{-- render $featuresItem --}}',
        '@endforeach'
    ];
}
```

**بعد (smart):**
```javascript
} else if (type === 'repeater') {
    var itemSchema = (f.schema && Array.isArray(f.schema.item_schema)) ? f.schema.item_schema : [];
    var itemVar = (k.length > 2 && k.slice(-1) === 's') ? '$' + k.slice(0, -1) : '$' + k + 'Item';
    if (itemSchema.length > 0) {
        // يبني snippet كامل بناءً على كل sub-field في item_schema
        ...
    } else {
        // fallback: سلوك قديم
    }
}
```

### تغيير ٣ — `generateFullScaffold()` للـ repeater

نفس منطق `generateSnippet()` لكن مُدمَج مع scaffold الـ section الكامل. لا تكرار: كلا الدالتين تستخدمان نفس `f.schema.item_schema`.

---

## ٣. كيف يُمرَّر item_schema للـ JS

```
PHP (Model cast) → $field->schema = ['item_schema' => [...]]
       ↓
$bladeFields->get([..., 'schema']) → toArray() → ['schema' => ['item_schema' => [...]]]
       ↓
@json($bladeFields->toArray()) → JSON في window.__sdEditorData.fields
       ↓
var fieldsData = data.fields;   // f.schema.item_schema متاح
       ↓
generateSnippet(f)  →  f.schema.item_schema
```

**لا `data-item-schema` attribute مطلوب** — البيانات موجودة بالفعل في `fieldsData` عبر `window.__sdEditorData`.

---

## ٤. أمثلة قبل/بعد

### مثال: حقل `features` مع item_schema: `[title(text), description(text), url(url), icon_class(text)]`

**قبل:**
```blade
@foreach (is_array($data['features'] ?? null) ? $data['features'] : [] as $featuresItem)
    {{-- render $featuresItem --}}
@endforeach
```

**بعد:**
```blade
@foreach (is_array($data['features'] ?? null) ? $data['features'] : [] as $feature)
    <div>
        {{ $feature['title'] ?? '' }}
        {{ $feature['description'] ?? '' }}
        @if(!empty($feature['url']))
            <a href="{{ $feature['url'] ?? '' }}">{{ $feature['url'] ?? '' }}</a>
        @endif
        @if(!empty($feature['icon_class']))
            <i class="{{ $feature['icon_class'] ?? '' }}"></i>
        @endif
    </div>
@endforeach
```

### مثال: حقل `gallery_items` مع item_schema: `[image(media), caption(text), is_active(boolean)]`

```blade
@foreach (is_array($data['gallery_items'] ?? null) ? $data['gallery_items'] : [] as $gallery_item)
    <div>
        @if(!empty($gallery_item['image']))
            <img src="{{ $gallery_item['image'] ?? '' }}" alt="">
        @endif
        {{ $gallery_item['caption'] ?? '' }}
        @if(!empty($gallery_item['is_active']))
            {{-- is_active enabled --}}
        @endif
    </div>
@endforeach
```

### مثال: حقل `steps` بدون item_schema (fallback)

```blade
@foreach (is_array($data['steps'] ?? null) ? $data['steps'] : [] as $step)
    {{-- render $step --}}
@endforeach
```
*(لاحظ: `steps` ينتهي بـ `s` → `$step` وليس `$stepsItem`)*

---

## ٥. قواعد اختيار itemVar

| المفتاح | النتيجة |
|---------|---------|
| `features` | `$feature` |
| `services` | `$service` |
| `items` | `$item` |
| `steps` | `$step` |
| `data` | `$data` (2 حرف فقط → لا اختصار) |
| `x` | `$xItem` (حرف واحد → لا اختصار) |

**القاعدة:** `k.length > 2 && k.slice(-1) === 's'` → احذف `s`، وإلا أضف `Item`.

---

## ٦. هل تغير runtime contract؟

**لا.** الكود الناتج يستخدم فقط:
- `$data['fieldKey']` — المتغير الصحيح في definition-driven sections
- `$feature['subKey']` — عمليات array على item data

**لا يُولَّد أي استخدام لـ:**
- `$fields` ❌
- `$sharedData` ❌
- `$translatableData` ❌

---

## ٧. هل تغير Repeater Editor؟

**لا.** التغييرات محصورة في:
- `resources/views/dashboard/section_definitions/edit.blade.php` — الـ Blade tab فقط

**لا تغيير في:**
- `fields/repeater.blade.php` ❌
- `fields/repeater-item.blade.php` ❌
- `DynamicSectionEditorRenderer` ❌
- `DynamicSectionContentNormalizer` ❌
- `workspace.blade.php` ❌
- `initDynamicRepeaters()` ❌
- `pages/sections/edit` ❌

---

## ٨. هل scaffold الكامل يستفيد من التحسين؟

**نعم.** `generateFullScaffold()` يستخدم نفس منطق `item_schema` مع توليد مستقل لا يستدعي `generateSnippet()` مباشرة (لأن scaffold يحتاج مسافات بادئة مختلفة). كلا الدالتين تشتركان في نفس قواعد التوليد حسب النوع.

---

## ٩. هل يوجد fallback عند غياب item_schema؟

**نعم.** في كلا الدالتين:
```javascript
var itemSchema = (f.schema && Array.isArray(f.schema.item_schema)) ? f.schema.item_schema : [];
if (itemSchema.length > 0) {
    // Smart generation
} else {
    // Fallback: sلوك قديم (generic @foreach)
}
```

Fallback يُفعَّل عند:
- `schema` column = null (حقل قديم بدون schema)
- `item_schema` = [] (لم تُضَف sub-fields بعد)
- `item_schema` = undefined/missing

---

## الملخص

| العنصر | الحالة |
|-------|-------|
| الملف المُعدَّل | `edit.blade.php` فقط |
| Schema column مُضمَّنة في fieldsData | ✅ |
| Smart repeater snippet لـ generateSnippet | ✅ |
| Smart repeater scaffold لـ generateFullScaffold | ✅ |
| اسم متغير أنظف (features→$feature) | ✅ |
| قواعد sub-field types (text/url/media/boolean/icon) | ✅ |
| Fallback عند غياب item_schema | ✅ |
| Runtime contract سليم ($data فقط) | ✅ |
| Monaco/scaffold يعمل كما كان | ✅ |
| Repeater Editor لم يُلمس | ✅ |
