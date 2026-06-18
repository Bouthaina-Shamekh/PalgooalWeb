# Auto Blade Generator — Architecture & Developer Guide

**Phase 6** of the Section Definition Developer Toolchain  
**Status:** Phase 1 (Preview) complete — Phase 2 (Write to Disk) ready to activate

---

## الهدف

تحويل تعريف قسم (SectionDefinition) + حقوله (SectionDefinitionField) إلى Blade scaffold
قابل للتحرير في ثوانٍ معدودة، بدلاً من كتابة الكود يدوياً.

```
Before: مطور يكتب @php block + HTML section يدوياً → 3-15 دقيقة
After:  ضغطة زر → scaffold كامل → تعديل → كتابة على disk → < 30 ثانية
```

---

## مصادر البيانات

```
ComponentLibrary          ─┐
                            ├─→  BladeGenerator::generate()  →  Blade Scaffold
SectionDefinitionField    ─┘
```

### 1. ComponentLibrary
يُحدد انتماء كل `field_key` لمجموعة منطقية:

| Component    | Field Keys                                      |
|--------------|-------------------------------------------------|
| `intro`      | eyebrow, title, subtitle                        |
| `description`| description                                     |
| `highlight`  | highlight_text                                  |
| `cta`        | button_label, button_url, button_target         |
| `image`      | image, image_alt, image_position                |
| `seo`        | meta_title, meta_description                    |

### 2. SectionDefinitionField
كل حقل يحمل:
- `field_key` — اسم الحقل في `$data`
- `field_type` — نوع الحقل (text / media / repeater / …)
- `field_scope` — shared أو translatable
- `schema.item_schema` — للـ repeater: تعريف الحقول الفرعية

---

## Supported Field Types

| Type         | Output Blade                                                    |
|--------------|-----------------------------------------------------------------|
| `text`       | `@if ($key) <tag class="key">{{ $key }}</tag> @endif`           |
| `textarea`   | `@if ($key) <div class="key">{{ $key }}</div> @endif`           |
| `richtext`   | `@if ($key) <div class="key">{!! $key !!}</div> @endif`         |
| `url`        | `@if ($url) <a href="{{ $url }}" ...>{{ $label }}</a> @endif`   |
| `media`      | `$key = MediaResolver::resolve(...)` + `<img src="{{ $key }}">` |
| `boolean`    | `@if ($key) {{-- key enabled --}} @endif`                       |
| `number`     | `@if ($key) <span class="key">{{ $key }}</span> @endif`         |
| `select`     | `@if ($key) <span class="key">{{ $key }}</span> @endif`         |
| `repeater`   | `@if (!empty($key)) @foreach ($key as $item) … @endforeach`     |

### Semantic HTML per field_key

بعض المفاتيح المعروفة تحصل على HTML دلالي:

| field_key      | HTML tag | CSS class        |
|----------------|----------|------------------|
| `eyebrow`      | `<span>` | `section-eyebrow` |
| `title`        | `<h2>`   | `section-title`   |
| `subtitle`     | `<p>`    | `section-subtitle`|
| `description`  | `<div>`  | `section-desc`    |
| `highlight_text`| `<mark>`| `section-highlight`|
| `meta_title`   | —        | تعليق (SEO فقط)  |
| `meta_description`| —     | تعليق (SEO فقط)  |

---

## Component Awareness — كيف تعمل؟

```
detectComponentGroups(fields)
    ↓
maps field_key → component via COMPONENT_FIELD_GROUPS
    ↓
groups fields by component (non-repeaters only)
    ↓
preserves canonical component order: intro → description → highlight → cta → image → seo
    ↓
repeaters & unmatched fields go to "ungrouped" (after component sections)
```

### مثال: Hero Section (intro + cta + image)

```blade
@php
    // Auto-generated scaffold: hero — 2026-06-19
    $eyebrow     = trim((string) ($data['eyebrow']     ?? '')); // text / trans
    $title       = trim((string) ($data['title']       ?? '')); // text / trans
    $subtitle    = trim((string) ($data['subtitle']    ?? '')); // text / trans
    $button_label= trim((string) ($data['button_label']?? '')); // text / trans
    $button_url  = trim((string) ($data['button_url']  ?? '')); // url  / trans
    $button_target=trim((string) ($data['button_target']??'')); // text / shared
    $image       = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null); // media / shared
@endphp

<section class="section-hero">
    <div class="container">

        {{-- Intro (eyebrow / title / subtitle) --}}
        @if ($eyebrow)
            <span class="section-eyebrow">{{ $eyebrow }}</span>
        @endif
        @if ($title)
            <h2 class="section-title">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="section-subtitle">{{ $subtitle }}</p>
        @endif

        {{-- CTA (button) --}}
        @if ($button_url)
            <a href="{{ $button_url }}"
               target="{{ $button_target ?: '_self' }}"
               class="btn btn-primary">
                {{ $button_label }}
            </a>
        @endif

        {{-- Image --}}
        @if ($image)
            <img src="{{ $image }}"
                 alt="{{ $data['image_alt'] ?? '' }}"
                 class="image">
        @endif

    </div>
</section>
```

---

## Repeater Generation

الـ repeater يقرأ `item_schema` من `SectionDefinitionField::repeaterItemSchema()`:

```php
// item_schema مثال لـ features repeater:
[
    ['key' => 'icon',        'type' => 'text'],
    ['key' => 'feature_title', 'type' => 'text'],
    ['key' => 'description', 'type' => 'textarea'],
]
```

**الكود المُولَّد:**

```blade
{{-- features / repeater --}}
@if (!empty($features))
    <div class="features-list">
        @foreach ($features as $feature)
            <div class="features-item">
                <i class="{{ $feature['icon'] ?? '' }}"></i>
                <span>{{ $feature['feature_title'] ?? '' }}</span>
                <span>{{ $feature['description'] ?? '' }}</span>
            </div>
        @endforeach
    </div>
@endif
```

### قواعد تسمية الـ Item Variable

| field_key   | item variable |
|-------------|---------------|
| `features`  | `$feature`    |
| `services`  | `$service`    |
| `faqs`      | `$faq`        |
| `items`     | `$item`       |
| `data`      | `$dataItem`   |

القاعدة: إذا انتهى الـ key بـ `s` → احذفها (`features` → `$feature`).  
وإلا → أضف `Item` (`data` → `$dataItem`).

---

## @php Block — Runtime Contract

```
$data  — flat merged array: shared values + translatable values for current locale
         set by SectionDefinitionFrontendViewDataFactory
```

**أنواع القيم:**
- `text / url / select` → `trim((string) ($data['key'] ?? ''))`
- `textarea / richtext` → `(string) ($data['key'] ?? '')` (بدون trim — RTE يتحكم في whitespace)
- `boolean` → `!empty($data['key'])`
- `media` → `SectionFrontendMediaResolver::resolve($data['key'] ?? null)`
- `repeater` → `is_array($data['key'] ?? null) ? $data['key'] : []`

> ⚠️ لا تستخدم `$sharedData` أو `$translatableData` أو `$fields` — هذه لا تُعرَّف في runtime.

---

## Architecture Files

| File | Role |
|------|------|
| `app/Support/Sections/BladeGenerator.php` | كلاس التوليد الرئيسي |
| `app/Http/Controllers/Admin/SectionDefinitionController.php` | method: `bladeScaffold()` |
| `routes/dashboard.php` | `GET /{id}/blade-scaffold` |
| `resources/views/dashboard/section_definitions/edit.blade.php` | Modal UI + JS |
| `app/Support/Sections/ComponentLibrary.php` | Component definitions |
| `app/Support/Sections/SectionTemplateLibrary.php` | Template + blade_stub |

---

## UI Flow (Phase 1 — Preview Only)

```
المطور في صفحة edit سكشن
    ↓
ضغط "⚡ Scaffold من الحقول"
    ↓
JS: fetch GET /admin/section-definitions/{id}/blade-scaffold
    ↓
BladeGenerator::generate($definition) + BladeGenerator::stats($definition)
    ↓
Modal يعرض:
  ┌─────────────────────────────────────────┐
  │ ⚡ Auto Blade Generator                  │
  │ Scaffold محسوب من الحقول + Components    │
  ├─────────────────────────────────────────┤
  │ [stats: X حقل · Y repeater · Z comp]   │
  │ [chips: intro · cta · image]            │
  ├─────────────────────────────────────────┤
  │ @php                                    │
  │     // Auto-generated scaffold: hero    │
  │     $eyebrow = trim(...)               │
  │     ...                                 │
  │ </section>                              │
  ├─────────────────────────────────────────┤
  │ [إدراج في المحرر]  [نسخ الكود]  [إغلاق]│
  └─────────────────────────────────────────┘
```

---

## Roadmap

### Phase 1 — Preview Only ✅ (منجزة)
- Server-side `BladeGenerator.php`
- REST endpoint `GET /blade-scaffold`
- Preview Modal مع Stats + Code + Insert + Copy

### Phase 2 — Generate Blade File (مستقبلاً)
إضافة زر "كتابة الملف مباشرة" يرسل الـ scaffold مباشرة إلى `writeBladeFile()`.
```php
// في bladeScaffold() بعد التوليد:
// if ($request->boolean('write_to_disk')) {
//     $sectionDefinition->blade_source = $scaffold;
//     $sectionDefinition->saveQuietly();
//     app(SectionTemplateFileWriter::class)->write($sectionDefinition);
// }
```

### Phase 3 — Generate Snippets
توليد snippets لكل حقل بشكل منفصل (مفيد للإضافة اليدوية).

### Phase 4 — Generate Complete Section Package
توليد: Definition + Fields + Scaffold + Migration + Seeder من template واحد.

---

## Adding a New Field Type

```php
// في BladeGenerator::renderFieldHtml() — أضف case واحدة فقط:
case 'my_new_type' => $this->renderMyNewType($field, $indent),

// ثم اكتب الـ renderer:
private function renderMyNewType(SectionDefinitionField $field, string $indent): string
{
    $key = $field->field_key;
    return implode("\n", [
        "{$indent}@if (\${$key})",
        "{$indent}    {{-- render {$key} here --}}",
        "{$indent}@endif",
    ]);
}
```

---

## Validation Checklist

- ✅ جميع Section Templates متوافقة مع Component Library (v2 components-based)
- ✅ `resolveTemplateFields()` يدعم v1 (inline fields) و v2 (components) بشكل متوافق
- ✅ Repeater `item_schema` يُقرأ عبر `repeaterItemSchema()` المُعتمدة
- ✅ Field Types الجديدة يمكن إضافتها بتعديل ملف واحد فقط
- ✅ لا يُكسر FieldPresets أو SectionTemplates الحالية

---

## Success Metrics

| Metric | Before | After |
|--------|--------|-------|
| وقت كتابة @php block | 2-5 دقائق | 0 ثانية |
| وقت كتابة HTML section | 5-15 دقيقة | 0 ثانية |
| جاهزية للتعديل | من الصفر | scaffold كامل |
| **الإجمالي** | **7-20 دقيقة** | **< 5 ثوانٍ** |
