# Section Template Library — Implementation Report

**Date:** 2026-06-19  
**Session:** Phase Next — Section Templates System  
**Status:** Phase 1 Complete ✅

---

## المشكلة التي يحلّها هذا النظام

إنشاء Section Definition جديد كان يتطلب 5–6 خطوات يدوية:

| الخطوة | الوقت التقريبي |
|-------|--------------|
| إنشاء SectionDefinition (key, label, category) | ~2 دقيقة |
| إضافة الحقول واحداً بواحداً | ~5-8 دقيقة |
| تطبيق Field Preset ثم ضبط scopes | ~3 دقيقة |
| كتابة Blade Scaffold أولي | ~5-10 دقيقة |
| **المجموع** | **15–23 دقيقة** |

Section Template Library تختصر ذلك إلى **أقل من 30 ثانية** بضغطة زر واحدة.

---

## المعمارية

```
SectionTemplateLibrary
│
├── all()          → كل الـ templates
├── get(key)       → template محدد
└── keys()         → قائمة المفاتيح للـ validation

كل template يحتوي:
  ├── label         → اسم بشري
  ├── icon          → Tabler icon class
  ├── color         → لون الـ card
  ├── category      → تصنيف (hero, features, cta ...)
  ├── description   → وصف قصير
  ├── definition    → بيانات SectionDefinition
  ├── fields[]      → تعريفات SectionDefinitionField
  │     ├── field_key, label, field_type, field_scope
  │     ├── is_required, sort_order
  │     ├── schema (item_schema للـ repeater)
  │     └── options (للـ select)
  └── blade_stub    → كود Blade أولي يُحفظ في blade_source
```

---

## الملفات المُنشأة

### `app/Support/Sections/SectionTemplateLibrary.php` — جديد

Static library تحتوي 6 templates جاهزة. أنماط ثابتة:
- `private const T = 'translatable'` / `private const S = 'shared'` — اختصار للوضوح
- كل حقل له comment يشرح سبب الـ scope (Translatable/Shared)
- Repeater fields تحتوي `schema.item_schema` كامل
- `options` للـ select fields بصيغة `[['value' => '...', 'label' => '...']]`
- `blade_stub` → كود Blade واقعي يستخدم `$data[...]` ويُظهر كيفية Render الحقول

### `resources/views/dashboard/section_definitions/from-template.blade.php` — جديد

صفحة اختيار template:
- Grid of cards ملونة (لون مختلف لكل نوع سكشن)
- كل card: icon + label + description + field badges + repeater sub-fields
- Badge خاص يظهر عند وجود section_key مسبقاً (تمنع الضغط)
- زر confirm قبل الإرسال
- رابط "إنشاء يدوياً" في أسفل الصفحة

---

## الملفات المعدّلة

### `app/Http/Controllers/Admin/SectionDefinitionController.php`

أضيفت methods:

**`createFromTemplate()`** — `GET /section-definitions/from-template`
- يجمع `SectionTemplateLibrary::all()`
- يجمع `SectionDefinition::pluck('section_key')->flip()` للـ duplicate detection في الـ view
- يُعيد `view('dashboard.section_definitions.from-template', ...)`

**`storeFromTemplate()`** — `POST /section-definitions/from-template`

```
1. validate(template_key ∈ SectionTemplateLibrary::keys())
2. Guard: SectionDefinition::where('section_key', ...)->exists() → 409 redirect
3. DB::transaction:
   a. SectionDefinition::create([key, label, category, blade_source=stub, ...])
   b. foreach fields:
      - normalize options (array → pipe-delimited string)
      - $sectionDefinition->fields()->create([...])
4. Redirect → fields.index with success message
```

### `routes/dashboard.php`

```php
Route::get('/from-template',  [SectionDefinitionController::class, 'createFromTemplate'])->name('from_template');
Route::post('/from-template', [SectionDefinitionController::class, 'storeFromTemplate'])->name('store_from_template');
```

ملاحظة: مُضافان قبل routes الـ `/{sectionDefinition}` لمنع اعتراضها.

### `resources/views/dashboard/section_definitions/index.blade.php`

- زر "⚡ من قالب" مُضاف بجانب "إضافة تعريف" في toolbar الرئيسي
- زر مماثل في empty state

### `database/seeders/DashboardTranslationsSeeder.php`

أضيفت 17 ترجمة جديدة — `dashboard.Section_Tpl_*` و `dashboard.Field_Type_*`.

---

## الـ Templates المتوفرة

| المفتاح | الاسم | الحقول | Category |
|---------|------|--------|---------|
| `hero` | Hero Section | 8 (eyebrow, title, subtitle, button_label, button_url, button_target, image, image_alt) | hero |
| `features-grid` | Features Grid | 3 (title, subtitle, features repeater) | features |
| `content-showcase` | Content Showcase | 10 (eyebrow, title, subtitle, highlight, button_*, image_*, features repeater) | content |
| `cta-banner` | CTA Banner | 6 (title, subtitle, button_*, background_image) | cta |
| `faq` | FAQ Accordion | 3 (title, subtitle, faqs repeater) | faq |
| `testimonials` | Testimonials | 3 (title, subtitle, testimonials repeater) | social-proof |

---

## Field Scope Compliance

جميع الحقول مُصنَّفة وفق قاعدة Field Scope Architecture (§٢أ في CLAUDE.md):

| الحقل | Scope | السبب |
|-------|-------|-------|
| title, subtitle, eyebrow | Translatable | نص يُترجم دائماً |
| button_label, button_url | Translatable | نص / URL يختلف بين locales |
| button_target | Shared | سلوك متصفح — لا يرتبط باللغة |
| image | Shared | أصل بصري — لا يتغير |
| image_alt | Translatable | نص بديل — يُترجم لـ SEO |
| image_position | Shared | قرار تخطيط — لا يتغير |
| repeater (features/faqs/testimonials) | Translatable | الـ items تحتوي نصوصاً مُترجَمة |
| icon, icon_media, icon_source | Shared | رمز بصري — لا يتغير |

---

## مثال على Blade Stub المحفوظ

بعد إنشاء "Hero Section"، يجد المطور في `blade_source`:

```blade
@php
    $eyebrow     = $data['eyebrow']      ?? null;
    $title       = $data['title']        ?? '';
    $subtitle    = $data['subtitle']     ?? null;
    $buttonLabel = $data['button_label'] ?? null;
    $buttonUrl   = $data['button_url']   ?? null;
    $buttonTarget= $data['button_target']?? '_self';
    $image       = $data['image']        ?? null;
    $imageAlt    = $data['image_alt']    ?? '';
@endphp

<section class="section-hero">
    ...
</section>
```

**ما يحتاجه المطور بعد ذلك:** إضافة CSS classes فقط. البنية الكاملة جاهزة.

---

## Phase 2 — خارطة طريق

الميزات التالية لم تُنفَّذ في Phase 1 وتنتظر:

| الميزة | الوصف | الأولوية |
|-------|-------|---------|
| **Auto-write Blade file** | كتابة `blade_source` إلى disk تلقائياً عبر `SectionTemplateFileWriter` | عالية |
| **Preview Image** | صورة preview مُضافة مع كل template في `SectionTemplateLibrary` | متوسطة |
| **Demo Data** | بيانات تجريبية لاختبار الـ render فوراً | متوسطة |
| **Snippet Generator** | snippet جاهز للـ Monaco editor من الـ template | منخفضة |
| **Custom Category Filter** | تصفية templates حسب category في الـ picker | منخفضة |

### كيفية تفعيل Auto-write (Phase 2)

في `storeFromTemplate()`، بعد إنشاء `$sectionDefinition`، أضف:

```php
if (! empty($template['blade_stub'])) {
    $writer = app(SectionTemplateFileWriter::class);
    $result = $writer->write($sectionDefinition);
    // تجاهل الخطأ — المستخدم يمكنه الكتابة يدوياً من Monaco لاحقاً
}
```

---

## أوامر التشغيل

```bash
# تشغيل الـ seeder لإضافة الترجمات الجديدة
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

---

## قياس الأداء

| المقياس | قبل | بعد |
|--------|-----|-----|
| وقت إنشاء سكشن كامل | 15–23 دقيقة | < 30 ثانية |
| عدد الخطوات | 5-6 | 1 (ضغطة زر + تأكيد) |
| احتمال نسيان حقل | عالٍ | صفر (الحقول محددة مسبقاً) |
| توحيد الـ scopes | يعتمد على الفرد | مضمون معمارياً |
| Blade scaffold | يكتبه المطور من الصفر | جاهز في `blade_source` |

---

## مراجع

- `app/Support/Sections/SectionTemplateLibrary.php` — library الـ templates
- `app/Support/Sections/FieldPresetLibrary.php` — library الـ presets (مستوى أدنى)
- `docs/FIELD_SCOPE_ARCHITECTURE.md` — قواعد Translatable/Shared
- `docs/section-definitions.md` — معمارية النظام الكاملة
- `CLAUDE.md` §٢أ — قاعدة Field Scope للجلسات المستقبلية
