# نظام Section Definitions — توثيق شامل للمطورين

> **الملف:** `docs/section-definitions.md`  
> **آخر تحديث:** يونيو 2026  
> **الغرض:** فهم كامل لبنية النظام الديناميكي لإدارة أقسام الصفحات

---

## ١. المشكلة التي يحلّها هذا النظام

في مشروع PalgooalWeb كل صفحة (`Page`) تتكون من أقسام (`Section`). في البداية كان كل قسم يُعرَّف بـ `section_type` ثابت (مثل `hero`، `features_grid`) ولكل نوع منطق render مشفّر في PHP.

**المشكلة:** إضافة قسم جديد يعني:
- كتابة Blade view جديد يدوياً
- تسجيله في `SectionRegistry` يدوياً
- إعادة deploy للكود

**الحل:** نظام `section_definitions` — مدير القسم يُعرَّف من لوحة الإدارة، ويكتب Blade view عبر Monaco editor، ويُحدَّد هيكل الحقول دون لمس الكود.

---

## ٢. المعمارية: طبقتان

```
┌─────────────────────────────────────────────────────────┐
│              طبقة التعريف (Definition Layer)            │
│  SectionDefinition   ─── SectionDefinitionField        │
│  "ما هو القسم؟"          "ما حقوله؟"                   │
└─────────────────────────────┬───────────────────────────┘
                              │ FK: section_definition_id
┌─────────────────────────────▼───────────────────────────┐
│              طبقة المحتوى (Content Layer)               │
│  Section   ─── SectionTranslation                       │
│  "بيانات قسم محدد في صفحة معينة"                       │
└─────────────────────────────────────────────────────────┘
```

### الطبقة الأولى — التعريف (Definition Layer)

تُجيب على السؤال: **"ما شكل هذا القسم؟"**

| الموديل | الجدول | الغرض |
|---------|--------|-------|
| `SectionDefinition` | `section_definitions` | Blueprint للقسم: اسمه، مفتاحه، Blade view كودُه |
| `SectionDefinitionField` | `section_definition_fields` | كل حقل في القسم (نصوص، صور، روابط...) |
| `SectionTemplate` | `section_templates` | ربط بين التعريف وـ template_key |

### الطبقة الثانية — المحتوى (Content Layer)

تُجيب على السؤال: **"ما بيانات هذا القسم في هذه الصفحة؟"**

| الموديل | الجدول | الغرض |
|---------|--------|-------|
| `Section` | `sections` | قسم واحد في صفحة معينة، يحمل FK للتعريف |
| `SectionTranslation` | `section_translations` | ترجمات الحقول لكل لغة |

---

## ٣. موديل SectionDefinition — الحقول الأساسية

```php
// app/Models/Sections/SectionDefinition.php

// الهوية
$def->section_key;      // 'hero_main' — معرّف فريد (lowercase + underscores)
$def->label;            // 'Hero المرحبة' — الاسم في لوحة الإدارة
$def->category;         // 'hero' — تحدد مسار الملف: front/sections/hero/

// الـ Blade Editor
$def->blade_source;     // محتوى ملف .blade.php كنص في الـ DB
$def->blade_written_at; // وقت آخر كتابة للـ disk

// وضع المحرر
$def->editor_mode;      // 'dynamic' أو 'custom_preset'
// 'dynamic'      → يظهر محرر الحقول الديناميكي في صفحة تعديل القسم
// 'custom_preset' → يستخدم render مخصص قديم

// الربط بالـ Template
$def->primaryTemplate();  // SectionTemplate → template_key

// الحالة
$def->is_active;         // إذا كان false → لا يُستخدم في الـ render
$def->is_visible;        // مرئي في مكتبة الأقسام؟
$def->sort_order;        // ترتيب في الـ UI
```

---

## ٤. موديل SectionDefinitionField — أنواع الحقول

### أنواع الحقول المتاحة:

| النوع (`field_type`) | الوصف | مثال على الاستخدام |
|---------------------|-------|-------------------|
| `text` | نص قصير | العنوان، الوصف المختصر |
| `textarea` | نص طويل | فقرة نصية |
| `richtext` | محرر نصوص غني (CKEditor) | محتوى تفصيلي بتنسيق HTML |
| `url` | رابط URL | رابط زر CTA |
| `media` | اختيار من مكتبة الوسائط | صورة خلفية، أيقونة |
| `number` | رقم | عدد المميزات، نسبة مئوية |
| `boolean` | صح/خطأ | إظهار/إخفاء عنصر |
| `select` | قائمة منسدلة بخيارات محددة | لون الخلفية، المحاذاة |
| `repeater` | مصفوفة من عناصر (كل عنصر له حقوله) | قائمة خدمات، بطاقات مميزات |

### نطاق الحقل (`field_scope`):

```php
// 'translatable' → قيمة مختلفة لكل لغة (مخزنة في section_translations)
// 'shared'       → قيمة واحدة لجميع اللغات (مخزنة في sections.settings)

$field->field_scope; // 'translatable' | 'shared'
```

**مثال:**
- عنوان القسم → `translatable` (عربي/إنجليزي مختلفان)
- لون الخلفية → `shared` (نفس اللون في كل اللغات)

---

## ٥. رحلة Render — من DB إلى HTML

```
request للصفحة
      │
      ▼
SectionRenderer::render($section)
      │
      ├── renderDefinitionDriven($section)  ← يحاول أولاً
      │         │
      │         ├── SectionDefinitionRuntimeResolver
      │         │       └── resolveRenderableDefinition($section)
      │         │               → يتحقق: is section_definition_id موجود؟
      │         │               → يتحقق: is_active = true?
      │         │               → يتحقق: هل للتعريف primaryTemplate?
      │         │
      │         ├── SectionDefinitionFrontendViewDataFactory::build()
      │         │       → يجمع بيانات الحقول المترجمة
      │         │       → يُعيد ['view' => '...', 'viewData' => [...]]
      │         │
      │         └── view($view, $viewData)->render()  ← HTML النهائي
      │
      └── renderRegisteredSection($section)  ← fallback للـ legacy
                → تحقق من LEGACY_FRONTEND_SECTION_TYPES
                → render من code-side SectionRegistry
```

### كيف يُحدَّد مسار الـ Blade View؟

يوجد مسارين:

**المسار ١ — Registry المسجّل:**
```php
// config/sections.php
'template_registry' => [
    'templates' => [
        'portfolio_slider' => [
            'view' => 'front.sections.portfolio.portfolio_slider',
        ],
    ],
],
```

**المسار ٢ — Convention-Based (الأكثر استخداماً):**
```
template_key: 'hero_main'
category:     'hero'
          ↓
Blade view: front.sections.hero.hero_main
          ↓
File path:  resources/views/front/sections/hero/hero_main.blade.php
```

---

## ٦. كتابة Blade View من لوحة الإدارة

هذه إحدى أهم مميزات النظام. المشرف يكتب كود الـ Blade مباشرة من Monaco editor.

### تسلسل العمليات:

```
Monaco Editor (في المتصفح)
      │
      │  blade_source (النص الكامل للـ Blade)
      │  ← يُشفَّر base64 قبل الإرسال (لتجاوز ModSecurity WAF)
      ▼
POST /admin/section-definitions/{id}/write-blade
      │
      ▼
SectionDefinitionController@writeBlade()
      │  ← يفكّ الـ base64
      ▼
SectionTemplateFileWriter::write($definition)
      │
      ├── resolvedPath() → resources/views/front/sections/{cat}/{key}.blade.php
      ├── path traversal check (أمان)
      ├── mkdir() إذا المجلد غير موجود
      ├── file_put_contents() ← يكتب الملف
      └── $definition->blade_written_at = now()
```

### ملاحظة مهمة — إصلاح Apache Redirect:

على السيرفر الإنتاجي document root هو `public_html/` وليس `public_html/public/`، مما يعني أن أي POST يُعاد توجيهه (301) إلى مسار آخر فيتحول إلى GET ويُعيد 405. الحل في JS:

```javascript
// نتأكد من وجود /public/ في الـ URL قبل الإرسال
var url = writeForm.action;
if (!/\/public\//.test(url)) {
    url = url.replace(/(https?:\/\/[^\/]+)\//, '$1/public/');
}
```

---

## ٧. وضع الحرر الديناميكي (Dynamic Editor Mode)

عندما `editor_mode = 'dynamic'`، بدلاً من إظهار textarea فارغ في صفحة تعديل القسم، يظهر محرر ديناميكي يحتوي على:

- حقل لكل `SectionDefinitionField` في التعريف
- دعم المتعدد اللغات (تبويبات للغات المختلفة)
- حقول `shared` تظهر مرة واحدة
- حقول `translatable` تتكرر لكل لغة

```
DynamicSectionEditorRenderer::buildForSection($section, $languages)
      │
      ├── runtimeResolver->resolveDynamicDefinition($section)
      │       → يتحقق: editor_mode === 'dynamic'
      │       → يتحقق: hasPrimaryTemplate
      │
      └── يبني payload يتضمن:
              - definition (id, key, label, fields)
              - fieldGroups (مُجمَّعة بـ group)
              - defaultLocale
              - localeCodes
              - existingValues (القيم الحالية من الـ DB)
```

---

## ٨. ربط القسم بتعريف (Section ↔ SectionDefinition)

عند إضافة قسم لصفحة، يختار المشرف `section_type`. إذا أراد ربطه بتعريف ديناميكي:

```php
// في نموذج القسم
$section->section_definition_id = $definition->id;
$section->save();
```

بعدها `SectionDefinitionRuntimeResolver::resolveLinkedDefinition()` يستطيع إيجاد التعريف:

```php
public function resolveLinkedDefinition(Section $section, ?string $editorMode = null): ?SectionDefinition
{
    if (! $this->runtimeTablesAvailable()) {
        return null;
    }

    if (! $section->section_definition_id) {
        return null;  // لا يوجد ربط → legacy render
    }

    $query = SectionDefinition::where('id', $section->section_definition_id)
        ->where('is_active', true);

    if ($editorMode !== null) {
        $query->where('editor_mode', $editorMode);
    }

    return $query->first();
}
```

---

## ٩. تدفق إنشاء تعريف جديد — خطوة بخطوة

**لإنشاء قسم "خدماتنا" الجديد:**

### الخطوة ١: إنشاء التعريف
```
/admin/section-definitions/create
- section_key:  services_grid
- label:        خدماتنا
- category:     services
- editor_mode:  dynamic
- is_active:    ✓
```

### الخطوة ٢: تعريف الحقول
```
/admin/section-definitions/{id}/fields/create

الحقل ١:
- key:    title
- label:  عنوان القسم
- type:   text
- scope:  translatable

الحقل ٢:
- key:    subtitle
- label:  وصف مختصر
- type:   textarea
- scope:  translatable

الحقل ٣:
- key:    items
- label:  الخدمات
- type:   repeater
- scope:  translatable
- repeaterItemSchema:
    - key:  icon     | type: text
    - key:  title    | type: text
    - key:  desc     | type: textarea
```

### الخطوة ٣: كتابة الـ Blade View
```
/admin/section-definitions/{id}/edit (تبويب الـ Blade Editor)
```

```blade
{{-- resources/views/front/sections/services/services_grid.blade.php --}}
{{-- هذا الملف يُكتب من Monaco Editor --}}
<section class="services-section py-16">
    <div class="container">
        <h2>{{ $fields['title'] ?? '' }}</h2>
        <p>{{ $fields['subtitle'] ?? '' }}</p>

        <div class="grid grid-cols-3 gap-6">
            @foreach ($fields['items'] ?? [] as $item)
                <div class="service-card">
                    <i class="{{ $item['icon'] ?? '' }}"></i>
                    <h3>{{ $item['title'] ?? '' }}</h3>
                    <p>{{ $item['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
```

ثم كليك "كتابة الملف" → يُكتب إلى `resources/views/front/sections/services/services_grid.blade.php`

### الخطوة ٤: ربط بـ Template Key
```
في نموذج تعديل التعريف:
- template_key: services_grid  (يُحدَّد عبر SectionTemplate)
```

### الخطوة ٥: إضافة القسم لصفحة
```
/admin/pages/{page}/sections
- اختر section_type أو ربطه بـ section_definition_id
- المحرر الديناميكي يظهر تلقائياً بحقوله
- المشرف يملأ البيانات بعربي وإنجليزي
```

---

## ١٠. بنية الملفات المتعلقة بالنظام

```
app/
├── Models/
│   ├── Section.php                              ← موديل القسم (content layer)
│   ├── SectionTranslation.php                   ← ترجمات القسم
│   └── Sections/
│       ├── SectionDefinition.php                ← موديل التعريف
│       ├── SectionDefinitionField.php           ← موديل الحقول
│       └── SectionTemplate.php                  ← ربط template_key
│
├── Support/Sections/
│   ├── SectionRenderer.php                      ← نقطة الدخول للـ render
│   ├── SectionTemplateRegistry.php              ← خريطة template_key → Blade view
│   ├── SectionTemplateFileWriter.php            ← كتابة الـ .blade.php للـ disk
│   ├── SectionDefinitionRuntimeResolver.php     ← قرارات runtime (هل نستخدم dynamic؟)
│   ├── SectionDefinitionFrontendViewDataFactory.php ← تجهيز بيانات الـ frontend render
│   ├── DynamicSectionEditorRenderer.php         ← بناء payload المحرر الديناميكي
│   └── DeveloperSectionManagementArchitecture.php ← وثيقة معمارية للمطورين
│
├── Http/Controllers/Admin/
│   ├── SectionDefinitionController.php          ← CRUD + writeBlade
│   └── SectionDefinitionFieldController.php     ← CRUD + reorder
│
config/
└── sections.php                                 ← icon_library + template_registry

resources/views/
├── dashboard/section_definitions/
│   ├── index.blade.php                          ← قائمة التعريفات
│   ├── create.blade.php                         ← إنشاء تعريف
│   ├── edit.blade.php                           ← تعديل + Monaco Blade Editor
│   ├── form.blade.php                           ← form مشترك (create/edit)
│   └── fields/
│       ├── index.blade.php                      ← قائمة حقول تعريف
│       ├── create.blade.php                     ← إضافة حقل
│       ├── edit.blade.php                       ← تعديل حقل
│       ├── form.blade.php                       ← form مشترك للحقول
│       └── partials/
│           └── repeater-item-schema-editor.blade.php ← محرر sub-fields للـ repeater
│
└── front/sections/                              ← ملفات Blade المكتوبة من المحرر
    ├── hero/
    │   └── hero_main.blade.php
    ├── services/
    │   └── services_grid.blade.php
    └── ...

database/
└── migrations/
    ├── ..._create_section_definitions_table.php
    ├── ..._create_section_definition_fields_table.php
    ├── ..._create_section_templates_table.php
    └── ..._add_section_definition_id_to_sections.php
```

---

## ١١. أنماط مهمة يجب معرفتها

### نمط قيم الحقول في الـ Blade View

```blade
{{-- الحقول المترجمة متوفرة في $fields --}}
{{ $fields['title'] ?? '' }}
{{ $fields['subtitle'] ?? '' }}

{{-- للـ repeater --}}
@foreach ($fields['items'] ?? [] as $item)
    {{ $item['title'] ?? '' }}
    {{ $item['icon'] ?? '' }}
@endforeach

{{-- للـ boolean --}}
@if (!empty($fields['show_cta']))
    <a href="{{ $fields['cta_url'] ?? '#' }}">{{ $fields['cta_label'] ?? '' }}</a>
@endif

{{-- للـ media (يُعيد path الصورة) --}}
<img src="{{ $fields['background_image'] ?? '' }}" alt="">
```

### نمط التحقق من الـ Runtime (حماية من 500 errors)

قبل استخدام جداول `section_definitions`:

```php
if (! app(SectionDefinitionRuntimeResolver::class)->runtimeTablesAvailable()) {
    // النظام لم يُطبَّق في هذه البيئة → fallback
    return null;
}
```

### نمط إصلاح `$languages` في كل Controller

أي صفحة تعديل/إنشاء لأي موديل يستخدم تبويبات اللغة يحتاج:

```php
use App\Models\Language;

$languages = Language::where('is_active', true)->orderBy('id')->get();
return view('dashboard.something.create', compact('languages'));
```

غياب `$languages` يُسبب 500 error صامت.

---

## ١٢. الأسئلة الشائعة (FAQ)

**س: لماذا base64 عند كتابة الـ Blade من المحرر؟**
ج: ModSecurity WAF على السيرفر يمنع إرسال كود PHP في body الطلب. نشفّر الكامل بـ base64 ثم نفكّه في PHP.

**س: ما الفرق بين `section_key` و `template_key`؟**
ج: `section_key` هو معرّف التعريف نفسه (مثل `hero_main`). `template_key` هو المفتاح المستخدم في `SectionTemplateRegistry` لتحديد مسار الـ Blade view. في الغالب يكونان متطابقَين، لكن الفصل يسمح بأن يمتلك تعريف واحد عدة templates.

**س: متى يُستخدم `legacy render` بدلاً من `definition-driven`؟**
ج: عندما `section.section_definition_id` فارغ، أو عندما التعريف المرتبط `is_active = false`، أو عندما لا يوجد `template_key` محدد. حينها يرجع `SectionRenderer` إلى `LEGACY_FRONTEND_SECTION_TYPES`.

**س: هل يمكن تعديل ملف .blade.php يدوياً من الـ server؟**
ج: نعم. إذا وُجد الملف على الـ disk ولكن `blade_source` في DB فارغ، يُسمّى `external`. سيعمل الـ render بشكل طبيعي لكن لوحة الإدارة ستعرض تحذيراً أن الملف خارج سيطرة المحرر.

**س: ما الذي يحدث إذا أُنشئ تعريف بدون كتابة ملف Blade؟**
ج: `fileStatus` يُعيد `missing`. الـ render يفشل بـ View not found error. لذلك كتابة الملف خطوة إلزامية قبل استخدام التعريف في صفحة حقيقية.

---

## ١٣. جدول مراجع سريع

| الإجراء | المسار / الكلاس |
|---------|---------------|
| إنشاء تعريف جديد | `/admin/section-definitions/create` |
| تعديل وكتابة Blade | `/admin/section-definitions/{id}/edit` |
| إدارة حقول تعريف | `/admin/section-definitions/{id}/fields` |
| نقطة الدخول للـ render | `App\Support\Sections\SectionRenderer::render()` |
| خريطة template → view | `App\Support\Sections\SectionTemplateRegistry` |
| كتابة ملف Blade | `App\Support\Sections\SectionTemplateFileWriter` |
| قرارات runtime | `App\Support\Sections\SectionDefinitionRuntimeResolver` |
| بيانات المحرر الديناميكي | `App\Support\Sections\DynamicSectionEditorRenderer` |
| config المسجّل | `config/sections.php` → `template_registry.templates` |

---

*للاستفسارات التقنية الإضافية، راجع `app/Support/Sections/DeveloperSectionManagementArchitecture.php`*
