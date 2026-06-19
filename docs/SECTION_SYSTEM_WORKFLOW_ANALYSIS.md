# Section System — Workflow Analysis & Architecture Guide

**التاريخ:** 2026-06-19 (آخر تحديث: 2026-06-19 — Phase 2 Implemented)  
**الهدف:** توثيق معمارية نظام الـ Sections بالكامل — من إنشاء التعريف إلى العرض على الـ Frontend — لمطور جديد يدخل المشروع لأول مرة.  
**الحالة:** Phase 2 (Generate & Write Blade File) مُنفَّذة ومُتحقق منها ✅

---

## جدول المحتويات

1. [نظرة عامة على الطبقات](#1-نظرة-عامة-على-الطبقات)
2. [Section Definition — ما هو وكيف يُحفظ](#2-section-definition)
3. [Field Presets — حقول جاهزة بضغطة واحدة](#3-field-presets)
4. [Component Library — مجموعات الحقول القابلة لإعادة الاستخدام](#4-component-library)
5. [Section Templates — القوالب الكاملة](#5-section-templates)
6. [Field Scope Architecture — Shared vs Translatable](#6-field-scope-architecture)
7. [Auto Blade Generator — توليد الـ Scaffold تلقائياً](#7-auto-blade-generator)
8. [Section Rendering Runtime — من التعريف إلى الـ HTML](#8-section-rendering-runtime)
9. [مثال عملي كامل: Content Showcase](#9-مثال-عملي-كامل)
10. [Current Limitations — نقاط القوة والضعف](#10-current-limitations)
11. [اقتراحات المرحلة التالية](#11-اقتراحات-المرحلة-التالية)

---

## 1. نظرة عامة على الطبقات

النظام مبني على **خمس طبقات متراصة** من الأدنى للأعلى:

```
┌─────────────────────────────────────────────────────────────────┐
│  Layer 5: Rendering Runtime                                      │
│  SectionRenderer → SectionDefinitionFrontendViewDataFactory      │
│  → SectionDefinitionRuntimeResolver → SectionTemplateRegistry    │
│  → Blade View (front/sections/{category}/{key}.blade.php)        │
├─────────────────────────────────────────────────────────────────┤
│  Layer 4: Auto Blade Generator (Phase 6 → Phase 2 Extension)     │
│  BladeGenerator → scaffold → Monaco Editor → SectionTemplateFileWriter │
│  OR: BladeGenerator → generate & write direct (Phase 2) ✅       │
├─────────────────────────────────────────────────────────────────┤
│  Layer 3: Section Templates (from-template flow)                 │
│  SectionTemplateLibrary → storeFromTemplate() → DB records       │
├─────────────────────────────────────────────────────────────────┤
│  Layer 2: Component Library                                      │
│  ComponentLibrary::resolveFields() → merged field list           │
├─────────────────────────────────────────────────────────────────┤
│  Layer 1: Field Presets                                          │
│  FieldPresetLibrary → applyPreset() → DB records (fields)        │
├─────────────────────────────────────────────────────────────────┤
│  Foundation: SectionDefinition + SectionDefinitionField (DB)     │
└─────────────────────────────────────────────────────────────────┘
```

كل طبقة أعلى تبني على الطبقة التي تحتها. يمكن للمطور الدخول من أي طبقة:
- **مطور مبتدئ** → يبدأ من Layer 3 (from-template) ويحصل على كل شيء تلقائياً.
- **مطور متوسط** → يضيف حقولاً يدوياً أو يطبّق Presets (Layers 1-2).
- **مطور خبير** → يكتب BladeGenerator للتوليد أو يُعدّل Runtime Resolver (Layers 4-5).

---

## 2. Section Definition

### ما هو؟

`SectionDefinition` هو **بلوبرينت** لقسم واحد في الموقع. لا يحتوي على بيانات المحتوى الفعلية (النصوص والصور)، بل يصف **شكل تلك البيانات**: ما هي الحقول؟ وما هي أنواعها؟ وكيف تُعرض؟

**الفرق الجوهري:**

| المفهوم | ما يحويه | جدول DB |
|---------|---------|---------|
| `SectionDefinition` | بلوبرينت (البنية) | `section_definitions` |
| `SectionDefinitionField` | حقل واحد في البلوبرينت | `section_definition_fields` |
| `Section` | instance قسم في صفحة | `sections` |
| `SectionTranslation` | المحتوى الفعلي (نصوص، مدیا) | `section_translations` |

### الحقول الأساسية في الموديل

```php
// app/Models/Sections/SectionDefinition.php
protected $fillable = [
    'section_key',      // slug فريد: 'hero', 'content_showcase', 'faq'
    'label',            // اسم بشري: 'Hero Section', 'Content Showcase'
    'description',      // وصف مختصر للأداة الإدارية
    'category',         // تصنيف: 'hero', 'features', 'cta', 'social-proof'
    'editor_mode',      // 'dynamic' = يستخدم الـ dynamic editor في صفحة تعديل الصفحة
    'blade_source',     // الكود الفعلي للـ Blade (مُخزَّن في DB للتعديل من Monaco)
    'blade_written_at', // متى كُتب آخر ملف على disk
    'is_active',        // هل هذا التعريف مفعَّل؟
    'is_visible',       // هل يظهر في مكتبة الـ Sections؟
    'sort_order',       // ترتيب العرض في القوائم
];
```

### ما هو `section_key`؟

هو المعرّف الفريد لكل تعريف. يُستخدم في:
1. ربط الـ Section instance بتعريفه: `sections.section_definition_id`
2. بناء مسار الـ Blade بالـ convention: `front/sections/{category}/{section_key}.blade.php`
3. ربط البيانات من الـ `SectionQueryResolver`

**قواعد التسمية:** `snake_case` فقط، حروف صغيرة وأرقام وشرطة سفلية، لا مسافات.

```
hero             → front/sections/hero/hero.blade.php
content_showcase → front/sections/content/content_showcase.blade.php  
faq              → front/sections/faq/faq.blade.php
```

### ما هو `category`؟

هو تصنيف يُحدد **المجلد** الذي يُوضع فيه ملف الـ Blade:
- `hero` → `front/sections/hero/`
- `features` → `front/sections/features/`
- `content` → `front/sections/content/`
- `cta` → `front/sections/cta/`
- `faq` → `front/sections/faq/`
- `social-proof` → `front/sections/social-proof/`

إذا كان `category` فارغاً أو غير صالح → يستخدم `uncategorized/`.

### العلاقات الأساسية

```php
// الحقول المرتبطة بهذا التعريف (مُرتَّبة حسب sort_order)
$definition->fields()          → Collection<SectionDefinitionField>

// أي Section instances تستخدم هذا التعريف
$definition->sections()        → Collection<Section>

// القوالب (Templates) المرتبطة (pivot)
$definition->templates()       → Collection<Template>

// القالب الأول النشط (المستخدم للـ Render)
$definition->primaryTemplate() → Template|null
$definition->primaryTemplateKey() → string|null ('hero_featured', 'content_showcase', ...)
```

---

## 3. Field Presets

### ما هي الـ Presets؟

هي **مجموعات حقول جاهزة** يمكن تطبيقها على أي `SectionDefinition` موجود بضغطة زر واحدة. لا تُنشئ تعريفاً جديداً — تُضيف حقولاً فقط.

**الملف:** `app/Support/Sections/FieldPresetLibrary.php`

### الـ Presets الحالية (8 presets)

| Preset Key | الحقول | اللون |
|-----------|--------|-------|
| `section_intro` | eyebrow, title, subtitle | indigo |
| `description_block` | description | slate |
| `cta_button` | button_label, button_url, button_target | emerald |
| `features_list` | features (repeater مع title, icon_source, icon, icon_media) | violet |
| `image_block` | image, image_alt, image_position | cyan |
| `highlight_block` | highlight_text | amber |
| `seo_block` | meta_title, meta_description | blue |
| `complete_content` | كل الحقول أعلاه مُدمجة (9 حقول) | rose |

### كيف تعمل؟

```
المطور في صفحة Fields Index
    ↓
يضغط على Preset (مثلاً "Section Intro")
    ↓
POST /section-definitions/{id}/fields/apply-preset
    ↓
SectionDefinitionController::applyPreset()
    ↓
FieldPresetLibrary::get('section_intro')
    ↓
يجلب حقول الـ preset
    ↓
يُلغي التكرار: يتجاهل أي field_key موجود مسبقاً في DB
    ↓
يُنشئ الحقول الجديدة فقط في DB
    ↓
redirect مع flash 'ok'
```

### منع تكرار الحقول

```php
// في applyPreset():
$existingKeys = $sectionDefinition->fields()->pluck('field_key')->toArray();
foreach ($preset['fields'] as $fieldDef) {
    if (in_array($fieldDef['field_key'], $existingKeys)) {
        continue; // تجاهل الحقل الموجود مسبقاً
    }
    SectionDefinitionField::create([...]);
}
```

**نقطة مهمة:** الـ Presets هي **أداة للمطور** لإضافة حقول سريعة. تختلف عن الـ Components التي تُستخدم في بناء الـ Templates الكاملة.

---

## 4. Component Library

### ما هو الـ Component؟

هو **مجموعة حقول معنوية قابلة لإعادة الاستخدام** تمثل وحدة وظيفية واحدة. الفرق بين Preset وComponent:

| | Field Preset | Component |
|-|-------------|-----------|
| **يُستخدم في** | تطبيق يدوي على Section موجود | بناء Section Templates تلقائياً |
| **وظيفته** | أداة إضافة سريعة | لبنة بناء (building block) |
| **يدعم التكرار** | لا (يتجاهل الحقول الموجودة) | نعم (first-occurrence-wins) |
| **مُعرَّف في** | FieldPresetLibrary | ComponentLibrary |

**الملف:** `app/Support/Sections/ComponentLibrary.php`

### الـ Components الحالية (8 components)

| Component Key | الحقول | عدد الحقول |
|--------------|--------|------------|
| `intro` | eyebrow, title, subtitle | 3 |
| `description` | description | 1 |
| `cta` | button_label, button_url, button_target | 3 |
| `image` | image, image_alt, image_position | 3 |
| `features` | features (repeater: title, description, icon_source, icon, icon_media) | 1 |
| `highlight` | highlight_text | 1 |
| `faq` | faqs (repeater: question, answer) | 1 |
| `testimonials` | testimonials (repeater: name, position, company, quote, avatar) | 1 |
| `seo` | meta_title, meta_description | 2 |

### كيف يتم دمج الحقول؟

`ComponentLibrary::resolveFields()` يُنفّذ ثلاث خطوات:

```php
public static function resolveFields(array $componentKeys, array $extraFields = []): array
{
    $seen   = [];  // لتتبع الحقول المُضافة (O(1) lookup)
    $result = [];

    // ١. دمج حقول الـ components بالترتيب
    foreach ($componentKeys as $componentKey) {
        foreach ($component['fields'] as $fieldDef) {
            $fieldKey = $fieldDef['field_key'];
            if (isset($seen[$fieldKey])) continue; // تجاهل التكرار
            $seen[$fieldKey] = true;
            $result[] = $fieldDef;
        }
    }

    // ٢. إضافة الحقول الخاصة بالـ Template (extra_fields)
    foreach ($extraFields as $fieldDef) {
        if (isset($seen[$fieldDef['field_key']])) continue;
        $result[] = $fieldDef;
    }

    // ٣. تعيين sort_order تسلسلي (يبدأ من 1)
    foreach ($result as $index => &$fieldDef) {
        $fieldDef['sort_order'] = $index + 1;
    }

    return $result;
}
```

**مبدأ "first-occurrence-wins":** إذا ظهر نفس الـ `field_key` في أكثر من Component، يفوز التعريف الذي جاء أولاً حسب ترتيب `$componentKeys`. مثال: `['intro', 'description']` → إذا أضاف أحدهم `title`، لن يُضاف مرة ثانية.

---

## 5. Section Templates

### ما هو الـ Template؟

هو **بلوبرينت كامل** لقسم ويب جاهز للاستخدام، يتضمن:
- قائمة الـ Components المستخدمة
- حقول إضافية خاصة (extra_fields)
- `definition` — بيانات الـ SectionDefinition المقترحة
- `blade_stub` — كود Blade أولي جاهز للتعديل

**الملف:** `app/Support/Sections/SectionTemplateLibrary.php`

### الـ Templates الحالية (6 templates)

| Template Key | label | Components | الحقول الإجمالية |
|-------------|-------|-----------|----------------|
| `hero` | Hero Section | intro + cta + image | 9 |
| `features-grid` | Features Grid | intro + features | 4 |
| `content-showcase` | Content Showcase | intro + features + highlight + cta + image | 12 |
| `cta-banner` | CTA Banner | intro + cta + background_image (extra) | 7 |
| `faq` | FAQ Accordion | intro + faq | 4 |
| `testimonials` | Testimonials | intro + testimonials | 4 |

### هيكل Template كامل (مثال: `cta-banner`)

```php
'cta-banner' => [
    'label'       => 'CTA Banner',
    'icon'        => 'ti-speakerphone',
    'color'       => 'rose',
    'category'    => 'cta',
    'description' => 'Full-width CTA banner with title, subtitle, and button.',

    // الـ Components المستخدمة (من ComponentLibrary)
    'components'   => ['intro', 'cta'],
    
    // حقول إضافية خاصة بهذا الـ Template فقط
    'extra_fields' => [
        ['field_key' => 'background_image', 'field_type' => 'media', 'field_scope' => 'shared', ...],
    ],

    // بيانات الـ SectionDefinition المُقترحة
    'definition' => [
        'label'       => 'CTA Banner',
        'section_key' => 'cta_banner',
        'category'    => 'cta',
        'is_active'   => true,
        'sort_order'  => 0,
    ],

    // كود Blade أولي يُحفظ في blade_source
    'blade_stub' => <<<'BLADE'
        @php ... @endphp
        <section class="section-cta-banner">...</section>
    BLADE,
],
```

### كيف يتم إنشاء Section Definition من Template؟

```
المطور في صفحة "Create From Template"
    ↓
يختار Template (مثلاً "CTA Banner")
    ↓
POST /section-definitions/from-template
    ↓
SectionDefinitionController::storeFromTemplate()
    ↓
SectionTemplateLibrary::resolveTemplateFields('cta-banner')
    ├── تحقق: هل يوجد 'components'؟ نعم → v2 path
    └── ComponentLibrary::resolveFields(['intro', 'cta'], [background_image])
        → يُرجع 7 حقول مدموجة ومُرتَّبة
    ↓
DB Transaction:
    ├── SectionDefinition::create($template['definition'])       → سجل واحد
    ├── $definition->blade_source = $template['blade_stub']      → حفظ stub
    └── SectionDefinitionField::create(...) × 7                  → 7 سجلات
    ↓
redirect → edit page للتعريف الجديد
```

### v1 vs v2 Backward Compatibility

```php
public static function resolveTemplateFields(string $templateKey): array
{
    $template = self::get($templateKey);

    // v2: component-based (الحالي)
    if (! empty($template['components'])) {
        return ComponentLibrary::resolveFields(
            $template['components'],
            $template['extra_fields'] ?? [],
        );
    }

    // v1: inline fields (للتوافق مع templates قديمة)
    return $template['fields'] ?? [];
}
```

**التوجيه:** كل Template جديد يجب أن يستخدم v2 `components[]` فقط.

---

## 6. Field Scope Architecture

### المفهوم الأساسي

منصة PalgooalWeb تخدم آلاف القوالب والمشتركين بلغات متعددة. لذلك، كل حقل يحتاج قراراً: **هل قيمته تختلف بين اللغات أم لا؟**

```
السؤال الفاصل:
"في قالب يخدم موقعاً بالعربية والإنجليزية والفرنسية، 
هل يمكن أن تختلف قيمة هذا الحقل بين اللغات؟"

نعم → TRANSLATABLE
لا  → SHARED
```

### Shared — قيمة واحدة لجميع اللغات

حقل `SHARED` يُخزَّن مرة واحدة ويُقرأ لجميع اللغات.

**أمثلة:**
```
image          → الصورة نفسها لا تتغير بين ar/en/fr
image_position → قرار تصميمي (يسار/يمين) لا علاقة له باللغة
button_target  → _self أو _blank — سلوك متصفح، ليس نصاً
icon           → CSS class (ti-star) — رمز بصري عالمي
background_color → لون — قرار تصميمي
```

### Translatable — قيمة مختلفة لكل لغة

حقل `TRANSLATABLE` يُخزَّن مع كل locale منفصلاً.

**أمثلة:**
```
title          → 'عنوان القسم' (ar) / 'Section Title' (en)
subtitle       → نص ثانوي مختلف بكل لغة
button_label   → 'اشتر الآن' (ar) / 'Buy Now' (en)
button_url     → '/ar/contact' (ar) / '/en/contact' (en) [TRANSLATABLE!]
image_alt      → نص alt للـ SEO — مختلف بكل لغة
meta_title     → عنوان محركات البحث — مختلف بكل لغة
```

### لماذا `button_url` هو TRANSLATABLE وليس SHARED؟

هذا قرار معماري مهم يوثقه `docs/FIELD_SCOPE_ARCHITECTURE.md`:

```
ar → /ar/contact          (locale prefix)
en → /en/contact
ar → wa.me/966xxxxxxxxx   (رقم WhatsApp سعودي)
en → wa.me/1xxxxxxxxxx    (رقم WhatsApp أمريكي)
ar → /ar/pricing          (landing page مترجم)
en → /en/pricing
```

معاملة `button_url` كـ `SHARED` ستكسر كل template متعدد اللغات يستخدم locale-prefixed routes.

### لماذا `image` هو SHARED وليس TRANSLATABLE؟

```
الصورة ذاتها (الملف، الأبعاد، المحتوى البصري) لا تتغير بين اللغات.
فقط image_alt (وصف الصورة النصي) هو الذي يتغير لأهداف SEO والـ accessibility.
```

### جدول مرجعي سريع

| field_key | النوع | الـ Scope | السبب |
|-----------|-------|---------|-------|
| `eyebrow` | text | TRANSLATABLE | نص label — يُترجم |
| `title` | text | TRANSLATABLE | عنوان — يُترجم |
| `subtitle` | textarea | TRANSLATABLE | نص — يُترجم |
| `description` | textarea | TRANSLATABLE | نص طويل — يُترجم |
| `highlight_text` | text | TRANSLATABLE | badge text — يُترجم |
| `button_label` | text | TRANSLATABLE | نص زر — يُترجم |
| `button_url` | url | TRANSLATABLE | رابط — locale-specific |
| `button_target` | select | **SHARED** | سلوك متصفح |
| `image` | media | **SHARED** | أصل بصري ثابت |
| `image_alt` | text | TRANSLATABLE | SEO/a11y — يُترجم |
| `image_position` | select | **SHARED** | قرار تصميمي |
| `meta_title` | text | TRANSLATABLE | SEO — يُترجم |
| `meta_description` | textarea | TRANSLATABLE | SEO — يُترجم |
| `features` (repeater) | repeater | TRANSLATABLE | يحتوي نصوصاً مترجمة |
| `icon` | text | **SHARED** | CSS class — لغوي-agnostic |
| `icon_media` | media | **SHARED** | أصل بصري |

---

## 7. Auto Blade Generator

### المشكلة التي يحلها

بعد إنشاء `SectionDefinition` وحقوله، يحتاج المطور إلى كتابة ملف Blade للـ Frontend. هذا يستغرق 7-20 دقيقة يدوياً. BladeGenerator يحوّل هذا إلى أقل من 5 ثوانٍ.

**الملف:** `app/Support/Sections/BladeGenerator.php`

### مصادر البيانات

```
SectionDefinitionField (من DB)  → يُحدد الحقول وأنواعها وscopes
ComponentLibrary::all()         → يُحدد انتماء كل field_key لـ component (Single Source of Truth)
```

### كيف يحصل BladeGenerator على الحقول؟

```php
public function generate(SectionDefinition $definition): string
{
    $fields = $definition->fields()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();  // ← Query مباشرة على DB

    if ($fields->isEmpty()) {
        return $this->emptyStub($definition); // scaffold فارغ
    }

    return $this->buildPhpBlock($definition, $fields)
        . "\n\n"
        . $this->buildHtmlBlock($definition, $fields);
}
```

### كيف يتعرف على الـ Components؟

بعد **Component Sync Refactor** (موثَّق في `docs/COMPONENT_SYNC_REFACTOR_REPORT.md`)، أصبح `BladeGenerator` يبني الخريطة **ديناميكياً** من `ComponentLibrary::all()` بدلاً من ثابت داخلي:

```php
// detectComponentGroups() — يبني خريطة عكسية field_key → component في runtime:
private function detectComponentGroups(Collection $fields): array
{
    // يستخدم ComponentLibrary::all() مباشرةً
    $map = [];
    foreach (ComponentLibrary::all() as $componentKey => $component) {
        foreach ($component['fields'] as $fieldDef) {
            $map[$fieldDef['field_key']] = $componentKey;
        }
    }
    // ثم يُجمّع حقول الـ SectionDefinition حسب component
    // ...
}
```

**ComponentLibrary هو Single Source of Truth** — إضافة Component جديد في `ComponentLibrary` تنعكس تلقائياً على كل الـ scaffold المُولَّد.

الـ repeaters وأي `field_key` غير معروف → يذهبون للـ "ungrouped" bucket.

### كيف يولد @php block؟

لكل حقل، يُولَّد سطر واحد بحسب النوع:

```php
match ($type) {
    'media'    => "$key = SectionFrontendMediaResolver::resolve($data['key'] ?? null);",
    'boolean'  => "$key = !empty($data['key']);",
    'repeater' => "$key = is_array($data['key'] ?? null) ? $data['key'] : [];",
    'richtext',
    'textarea' => "$key = (string) ($data['key'] ?? '');",
    default    => "$key = trim((string) ($data['key'] ?? ''));",
}
```

### كيف يولد HTML Scaffold؟

```
buildHtmlBlock():
    ↓
<section class="section-{key}">
    <div class="container">
    ↓
    لكل component في canonical order (intro → description → highlight → cta → image → seo):
        {{-- Component Label --}}
        renderFieldHtml() لكل حقل في الـ component
    ↓
    لكل حقل ungrouped (repeaters + others):
        {{-- field_key / type --}}
        renderFieldHtml()
    ↓
    </div>
</section>
```

### كيف يتعامل مع الـ Media؟

```php
// في @php block:
$image = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null);

// في HTML:
@if ($image)
    <img src="{{ $image }}"
         alt="{{ $data['image_alt'] ?? '' }}"
         class="image">
@endif
```

`SectionFrontendMediaResolver::resolve()` يأخذ Media ID من `$data` ويُرجع Public URL من Storage.

### كيف يتعامل مع URLs (button_url)؟

حقل `button_url` له معالجة خاصة — يتوقع وجود `button_label` و`button_target`:

```php
@if ($button_url)
    <a href="{{ $button_url }}"
       target="{{ $button_target ?: '_self' }}"
       class="btn btn-primary">
        {{ $button_label }}
    </a>
@endif
```

### كيف يولد Repeaters؟

```php
private function renderRepeater(SectionDefinitionField $field, string $indent): string
{
    // ١. قراءة item_schema من DB
    $subFields = $field->repeaterItemSchema(); // normalized + validated

    // ٢. اشتقاق اسم المتغير الفردي
    // features → $feature, faqs → $faq, items → $item
    $itemVar = str_ends_with($key, 's') ? '$' . substr($key, 0, -1) : '$' . $key . 'Item';

    // ٣. توليد @foreach مع حقول فرعية
    @if (!empty($features))
        <div class="features-list">
            @foreach ($features as $feature)
                <div class="features-item">
                    {{-- حقل icon: <i class="..."> --}}
                    {{-- حقل title: <span>...</span> --}}
                    {{-- حقل icon_media: <img ...> --}}
                </div>
            @endforeach
        </div>
    @endif
}
```

### قواعد تسمية Item Variable

| field_key | item variable | القاعدة |
|-----------|--------------|---------|
| `features` | `$feature` | إزالة الـ s الأخيرة |
| `faqs` | `$faq` | إزالة الـ s الأخيرة |
| `services` | `$service` | إزالة الـ s الأخيرة |
| `testimonials` | `$testimonial` | إزالة الـ s الأخيرة |
| `data` | `$dataItem` | key قصير → إضافة Item |
| `info` | `$infoItem` | لا ينتهي بـ s → إضافة Item |

### UI Flow (Phase 1 — Preview Only)

```
المطور يفتح صفحة edit لـ SectionDefinition (تبويب Blade)
    ↓
يضغط "⚡ Scaffold من الحقول"
    ↓
JS: openScaffoldPreview()
    → fetch GET /admin/section-definitions/{id}/blade-scaffold
    → Headers: Accept: application/json
    ↓
BladeGenerator::generate($definition) + stats($definition)
    ↓
Response JSON: { scaffold: "...", stats: { fields, repeaters, components, component_names } }
    ↓
Modal يظهر:
  ┌── Header: ⚡ + Title + Close ────────────────────────┐
  ├── Stats Bar: X حقل · Y repeater · Z component        │
  ├── Component Chips: [intro] [cta] [image]              │
  ├── <pre> Code Block: كود الـ scaffold                  │
  └── Footer: [إدراج في المحرر] [نسخ الكود] [إغلاق]    │
    ↓
"إدراج في المحرر" → يضع الكود في Monaco editor (blade_source)
"نسخ الكود"       → clipboard
    ↓
المطور يُعدّل الـ Scaffold في Monaco
    ↓
"كتابة الملف" → write-blade endpoint → SectionTemplateFileWriter::write()
    → يكتب blade_source على disk: resources/views/front/sections/{cat}/{key}.blade.php
```

---

## 8. Section Rendering Runtime

### المسار الكامل من DB إلى HTML

```
request وصول صفحة frontend
    ↓
PageController/SiteController يجلب Section instances للصفحة
    ↓
لكل Section:
    SectionRenderer::render($section, $locale)
    ↓
    ┌─────────────────────────────────────────────────────┐
    │ هل يمكن الـ Definition-Driven Render؟              │
    │                                                     │
    │ SectionDefinitionRuntimeResolver::runtimeTablesAvailable()
    │ ← يتحقق من وجود جداول: section_definitions,        │
    │   section_templates, section_definition_template,   │
    │   sections.section_definition_id                    │
    └─────────────────────────────────────────────────────┘
    ↓ نعم
    SectionDefinitionFrontendViewDataFactory::build($section, $locale)
    ↓
    ┌─────────────────────────────────────────────────────┐
    │ 1. resolveRenderableDefinition($section)            │
    │    ← يجلب SectionDefinition المرتبط:               │
    │    - $section->section_definition_id يجب أن يكون set│
    │    - definition.is_active = true                    │
    │    - definition له primaryTemplate() (template_key)│
    │    مع eager loading: templates + fields (is_active) │
    └─────────────────────────────────────────────────────┘
    ↓
    ┌─────────────────────────────────────────────────────┐
    │ 2. normalizeContent($content, $definition, $locale) │
    │    ← $content من SectionTranslation (JSON)          │
    │    ← يملأ قيم default_value للحقول الفارغة         │
    │    ← يُطبّق SectionQueryResolver للبيانات الديناميكية│
    └─────────────────────────────────────────────────────┘
    ↓
    ┌─────────────────────────────────────────────────────┐
    │ 3. SectionTemplateRegistry::resolve($templateKey)   │
    │                                                     │
    │ Resolution Priority:                                │
    │   a. Registry Override (config/sections.php templates)│
    │      → view path صريح في config                    │
    │   b. Convention-Based Resolution:                   │
    │      front.sections.{category}.{template_key}       │
    │      ← View::exists() للتحقق                       │
    │   c. Legacy Fallback (إذا كان LEGACY_FRONTEND_SECTION_TYPES)│
    │   d. Missing Template View                          │
    └─────────────────────────────────────────────────────┘
    ↓ found
    view($resolvedView, $viewData)->render()
    ↓
    HTML string
```

### SectionTemplateRegistry

هو الـ registry الذي يربط `template_key` بـ Blade view. يعمل على مستويين:

**المستوى الأول — Registry صريح (config/sections.php):**
```php
// config/sections.php
'template_registry' => [
    'templates' => [
        'portfolio_slider' => [
            'label' => 'Portfolio Slider',
            'view'  => 'front.sections.portfolio.portfolio_slider',
            'category' => 'portfolio',
        ],
    ],
],
```

**المستوى الثاني — Convention-Based:**
```
template_key: 'content_showcase'  + category: 'showcase'
    ↓
conventionView() = 'front.sections.showcase.content_showcase'
    ↓
View::exists('front.sections.showcase.content_showcase')
    ↓ true
resolved!
```

**المعادلة:** `front.sections.{category}.{template_key}` = `resources/views/front/sections/{category}/{template_key}.blade.php`

### الـ View Data المُمررة للـ Blade

عند نجاح الـ resolution، يحصل كل Blade view على:

```php
[
    'data'                    => array,  // ← الأهم: كل قيم الحقول (shared + translatable merged)
    'content'                 => array,  // ← نفس $data (alias للتوافق)
    'section'                 => Section,
    'title'                   => string|null,
    'translation'             => SectionTranslation|null,
    'variant'                 => string|null,
    'currentLocale'           => string,
    'sectionDefinition'       => SectionDefinition,
    'sectionDefinitionFields' => Collection<SectionDefinitionField>,
    'sectionTemplate'         => Template,
    'sectionTemplateKey'      => string,
    'sectionTemplateMeta'     => array,
]
```

**في الـ Blade view:** استخدم `$data['field_key']` للوصول لقيمة أي حقل.

### Legacy Fallback Path

إذا لم يوجد `section_definition_id` أو التعريف غير مرتبط بـ template:

```php
// SectionRenderer::renderRegisteredSection()
$config = SectionRegistry::get($section->type);  // legacy registry
return view($config['view'], ['data' => $content, 'section' => $section])->render();
```

أنواع legacy موجودة في `LEGACY_FRONTEND_SECTION_TYPES`:
```
hero, hero_campaign, programming_showcase, mobile_app_showcase,
how_we_build, design_showcase, features_grid, services_grid, ...
```

### Front Views الموجودة على Disk

```
resources/views/front/sections/
├── _missing-template.blade.php    ← Fallback عند عدم إيجاد view
├── hero.blade.php                 ← Legacy
├── faq/
│   └── faq_section.blade.php
├── hero/
│   ├── hero_campaign.blade.php
│   ├── hero_featured.blade.php
│   └── test_dynamic_card.blade.php
├── portfolio/
│   └── portfolio_slider.blade.php  ← مُسجّل في config/sections.php
├── promo/
│   ├── promo_image_features.blade.php
│   ├── website_protection.blade.php
│   └── wordpress_ai_promo.blade.php
├── reviews/
│   └── reviews_slider.blade.php
├── services/
│   ├── process_steps.blade.php
│   ├── service_gallery_showcase.blade.php
│   ├── service_masonry_gallery.blade.php
│   ├── service_showcase.blade.php
│   └── tech_stack_logos.blade.php
├── showcase/
│   └── content_showcase.blade.php  ← مثال Definition-Driven
└── templates/
    └── templates_showcase.blade.php
```

---

## 9. مثال عملي كامل: Content Showcase

سنتتبع دورة حياة `content-showcase` template من البداية للنهاية.

### المرحلة ١: اختيار Template

المطور يذهب إلى `/admin/section-definitions/from-template` ويختار **Content Showcase**.

**ما يعرفه النظام عن هذا الـ Template:**
```php
'content-showcase' => [
    'components'   => ['intro', 'features', 'highlight', 'cta', 'image'],
    'extra_fields' => [],  // لا حقول إضافية — الـ components كافية
    'definition'   => [
        'section_key' => 'content_showcase',
        'category'    => 'showcase',         // ← content or showcase؟ الملف في showcase/
        ...
    ],
]
```

### المرحلة ٢: حل الحقول من Components

`SectionTemplateLibrary::resolveTemplateFields('content-showcase')` يستدعي:

```
ComponentLibrary::resolveFields(['intro', 'features', 'highlight', 'cta', 'image'])
```

النتيجة — 12 حقلاً بالترتيب:

| # | field_key | type | scope |
|---|-----------|------|-------|
| 1 | eyebrow | text | TRANSLATABLE |
| 2 | title | text | TRANSLATABLE |
| 3 | subtitle | textarea | TRANSLATABLE |
| 4 | features | repeater | TRANSLATABLE |
| 5 | highlight_text | text | TRANSLATABLE |
| 6 | button_label | text | TRANSLATABLE |
| 7 | button_url | url | TRANSLATABLE |
| 8 | button_target | select | SHARED |
| 9 | image | media | SHARED |
| 10 | image_alt | text | TRANSLATABLE |
| 11 | image_position | select | SHARED |

(الـ `features` repeater يتضمن: title, description, icon_source, icon, icon_media)

### المرحلة ٣: إنشاء DB Records

```php
// SectionDefinitionController::storeFromTemplate()
DB::transaction(function () use ($definition, $fields) {
    $sectionDef = SectionDefinition::create([
        'section_key' => 'content_showcase',
        'label'       => 'Content Showcase',
        'category'    => 'showcase',         // ← مهم! يُحدد مسار الـ Blade
        'blade_source'=> $template['blade_stub'],
        'is_active'   => true,
        ...
    ]);

    // إنشاء 11 سجل في section_definition_fields
    foreach ($fields as $fieldDef) {
        SectionDefinitionField::create([
            'section_definition_id' => $sectionDef->id,
            'field_key'   => $fieldDef['field_key'],
            'field_type'  => $fieldDef['field_type'],
            'field_scope' => $fieldDef['field_scope'],
            'sort_order'  => $fieldDef['sort_order'],
            ...
        ]);
    }
});
```

### المرحلة ٤: توليد Blade Scaffold

المطور يضغط "⚡ Scaffold من الحقول" في تبويب Blade.

**الـ @php block المُولَّد:**
```php
@php
    // Auto-generated scaffold: content_showcase — 2026-06-19
    // $data contains all field values (shared + translatable merged).

    $eyebrow        = trim((string) ($data['eyebrow']        ?? '')); // text / trans
    $title          = trim((string) ($data['title']          ?? '')); // text / trans
    $subtitle       = (string) ($data['subtitle']            ?? ''); // textarea / trans
    $features       = is_array($data['features']  ?? null) ? $data['features']  : []; // repeater
    $highlight_text = trim((string) ($data['highlight_text'] ?? '')); // text / trans
    $button_label   = trim((string) ($data['button_label']   ?? '')); // text / trans
    $button_url     = trim((string) ($data['button_url']     ?? '')); // url / trans
    $button_target  = trim((string) ($data['button_target']  ?? '')); // select / shared
    $image          = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null); // media / shared
    $image_alt      = trim((string) ($data['image_alt']      ?? '')); // text / trans
    $image_position = trim((string) ($data['image_position'] ?? '')); // select / shared
@endphp
```

**الـ HTML المُولَّد (مُجمَّع بـ components):**
```html
<section class="section-content_showcase">
    <div class="container">

        {{-- Intro (eyebrow / title / subtitle) --}}
        @if ($eyebrow)
            <span class="section-eyebrow">{{ $eyebrow }}</span>
        @endif
        @if ($title)
            <h2 class="section-title">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <div class="section-subtitle">{{ $subtitle }}</div>
        @endif

        {{-- Highlight --}}
        @if ($highlight_text)
            <mark class="section-highlight">{{ $highlight_text }}</mark>
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

        {{-- features / repeater --}}
        @if (!empty($features))
            <div class="features-list">
                @foreach ($features as $feature)
                    <div class="features-item">
                        @if (!empty($feature['icon_source']))
                            <span>{{ $feature['icon_source'] ?? '' }}</span>
                        @endif
                        <span>{{ $feature['title'] ?? '' }}</span>
                        <span>{{ $feature['description'] ?? '' }}</span>
                        @if (!empty($feature['icon']))
                            <i class="{{ $feature['icon'] ?? '' }}"></i>
                        @endif
                        @if (!empty($feature['icon_media']))
                            <img src="{{ $feature['icon_media'] ?? '' }}" alt="">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>
```

### المرحلة ٥: التعديل والكتابة على Disk

المطور يُعدّل الـ scaffold في Monaco ليصبح مثل `showcase/content_showcase.blade.php` الفعلي (مع Tailwind classes، SectionFrontendMediaResolver، إلخ) ثم يضغط "كتابة الملف":

```
POST /admin/section-definitions/{id}/write-blade
    ↓
SectionTemplateFileWriter::write($definition)
    ↓
resolvedPath() = resources/views/front/sections/showcase/content_showcase.blade.php
    ↓
أمان: path traversal check → base path صحيح؟
    ↓
mkdir() إذا لزم
    ↓
file_put_contents($path, $definition->blade_source)
    ↓
$definition->blade_written_at = now() → saveQuietly()
```

### المرحلة ٦: ربط Section instance بالتعريف

في قاعدة البيانات، `sections.section_definition_id` يُشير للـ SectionDefinition الجديد. ويُضاف `template_key` عبر pivot `section_definition_template`.

### المرحلة ٧: Rendering على الـ Frontend

```
GET /ar/about  ← زيارة المستخدم
    ↓
PageController يجلب sections الصفحة
    ↓
SectionRenderer::render($section_content_showcase, 'ar')
    ↓
runtimeTablesAvailable() → true (جداول موجودة)
    ↓
resolveRenderableDefinition($section) → $definition (id: X)
    ↓
$definition->primaryTemplateKey() → 'content_showcase'
    ↓
$translation = SectionTranslation::find(locale: 'ar')
$content = $translation->content  // JSON: { eyebrow: 'خدماتنا', title: 'ما يجعلنا مختلفين', ... }
    ↓
normalizeContent($content, $definition, 'ar')
    // يدمج shared values + arabic translatable values
    // result: { eyebrow: 'خدماتنا', title: 'ما يجعلنا مختلفين', image: 42, features: [...], ... }
    ↓
SectionTemplateRegistry::resolve('content_showcase', 'showcase')
    → conventionView = 'front.sections.showcase.content_showcase'
    → View::exists() → true ✓
    ↓
view('front.sections.showcase.content_showcase', ['data' => $normalizedContent, ...])->render()
    ↓
HTML جاهز
```

---

## 10. Current Limitations

### نقاط القوة ✅

**١. معمارية طبقية متماسكة**  
كل طبقة مستقلة وقابلة للاستبدال. إضافة Component جديد = ملف واحد. إضافة Template = entry واحد.

**٢. Field Scope Enforcement**  
المنصة تُطبّق قرارات Scope على مستوى المعمارية (مكتوبة في ComponentLibrary وFieldPresetLibrary)، وليس على مستوى التسمية فقط.

**٣. Zero-DB Reading في Rendering**  
`$data` وصل للـ Blade view بعد تطبيع واحد. لا استعلامات إضافية داخل view.

**٤. Backward Compatibility**  
v1 inline fields لا تزال تعمل. Legacy sections تعمل بدون أي تغيير.

**٥. Convention-Based Resolution**  
إضافة Blade view في المكان الصحيح كافية — لا حاجة لتسجيل يدوي.

**٦. Security في الكتابة**  
`SectionTemplateFileWriter` يرفض Path Traversal ويُقيّد الكتابة داخل `resources/views/front/sections/` فقط.

---

### نقاط الضعف ⚠️

**١. BladeGenerator لا يعرف الـ UI Design**  
يُولّد scaffold وظيفي بـ HTML بسيط، لكن لا يعرف:
- هل نستخدم Tailwind أم Bootstrap؟
- الـ RTL/LTR layout؟
- الـ Responsive classes؟

المطور يحتاج دائماً تعديل يدوي بعد الـ scaffold.

**٢. blade_source في DB = تحدي DevOps**  
الكود مُخزَّن في جدول DB **وأيضاً** على disk. يمكن أن يختلفا:
- هل المصدر الحقيقي هو DB؟ أم disk؟
- إذا استُعيد DB من backup → قد يختلف عن disk.
- `blade_written_at` هو المؤشر الوحيد، وهو غير موثوق 100%.

**٣. ~~component_names في BladeGenerator غير مُتزامنة مع ComponentLibrary~~** ✅ تم حلها  
`BladeGenerator` الآن يقرأ من `ComponentLibrary::all()` مباشرةً — لا تكرار، لا حاجة لتحديث ملفَين. (راجع `docs/COMPONENT_SYNC_REFACTOR_REPORT.md`)

**٤. غياب تتبع "من أنشأ هذا الـ Definition؟"**  
`section_definitions` لا يحتوي على `created_by` أو audit trail. إذا حذف مطور تعريفاً، لا يوجد سجل.

**٥. لا يوجد validation لـ blade_source**  
الـ Monaco editor يُرسل أي نص كـ `blade_source`. لا فحص لصيغة PHP أو Blade قبل الكتابة على disk.

**٦. الـ Section Rendering الحالي لا يدعم Caching**  
كل طلب يُشغّل Query لجلب SectionDefinition + fields + template. مع 10+ sections في صفحة = 10+ DB queries.

**٧. رابط Section Instance بالـ Definition غير مفعَّل تلقائياً**  
بعد إنشاء `SectionDefinition`، لا يتم ربط الـ `Section` instances الموجودة تلقائياً بالتعريف الجديد. هذا يدوي.

---

### الاختناقات المستقبلية مع 100+ Template

**أ. SectionTemplateLibrary.php ستصبح ملفاً ضخماً**  
حالياً 480 سطر لـ 6 templates. مع 100 template = ~8000 سطر في ملف واحد. يصعب التنقل والمراجعة.

**ب. لا يوجد Template Search/Filter**  
صفحة `from-template` تعرض كل الـ Templates في grid. مع 100+، ستحتاج بحث وتصفية حسب category.

**ج. category inconsistency**  
Template قد يعلن `category: 'showcase'`، لكن definition قد يُنشئ بـ `category: 'content'`. لا validation يمنع هذا التناقض.

**د. لا versioning للـ Templates**  
إذا تغيرت `component fields` في ComponentLibrary، كل الـ Templates القديمة ستُنشئ حقولاً مختلفة دون إشعار.

---

### المشاكل المحتملة مع Marketplace للقوالب

**١. لا نظام permissions على Template level**  
أي مطور لديه access للـ Admin يمكنه إنشاء أي Section Definition من أي Template.

**٢. غياب Template Schema Versioning**  
Template من Marketplace قد يفترض وجود Component غير موجود في النظام → `resolveTemplateFields()` يُرجع حقولاً ناقصة بصمت.

**٣. لا Namespace isolation**  
`section_key` يجب أن يكون فريداً globally. في Marketplace، قد يتعارض `section_key: 'hero'` من مزودَين مختلفَين.

**٤. لا preview حقيقي**  
`from-template.blade.php` يعرض field badges فقط. في Marketplace، المستخدم يحتاج screenshot أو live preview.

**٥. Blade files = Server Code**  
في Marketplace، كود Blade قابل للتنفيذ على السيرفر. لا sandbox أو sandboxing يمنع Blade ضار.

---

## 11. اقتراحات المرحلة التالية

مرتبة حسب **التأثير × سهولة التنفيذ × العائد المستقبلي**:

---

### ١. ~~Phase 2 — Auto Write Blade File~~ ✅ مُنفَّذة

**التنفيذ:** زر "توليد وكتابة مباشرة" (أخضر) في Preview Modal يجمع `BladeGenerator::generate()` + `SectionTemplateFileWriter::write()` في خطوة واحدة عبر endpoint جديد:

```
POST /admin/section-definitions/{id}/generate-and-write-blade
→ generateAndWriteBladeFile() في SectionDefinitionController
→ يُولّد scaffold، يحفظ blade_source في DB، يكتب الملف على disk
→ يُعيد JSON مع المسار + convention view + stats + scaffold
```

**Overwrite Guard:** ملفات خارجية (`blade_written_at = null`) محمية بـ 409 + تأكيد.  
**العائد المُحقق:** وقت "من تعريف إلى Blade على disk" = أقل من 5 ثوانٍ.

**التوثيق:** `docs/GENERATE_BLADE_FILE_PHASE_2_REPORT.md`

---

### ٢. Section Rendering Cache Layer (تأثير: عالي، سهولة: متوسطة)

**المشكلة:** كل render request = DB query لـ `SectionDefinition` + `fields` + `templates`. مع 10+ sections في صفحة = overhead حقيقي.

**المقترح:** Cache الـ `$renderPayload` بـ key `section_render_{id}_{locale}_{template_key}`:

```php
// في SectionDefinitionFrontendViewDataFactory::build():
$cacheKey = "section_def_render.{$section->id}.{$locale}.{$templateKey}";
return Cache::remember($cacheKey, 3600, fn() => $this->buildPayload(...));
// invalidate عند: update definition, update fields, update translation
```

**العائد:** يُحسّن أداء صفحات Frontend بشكل ملموس بدون تغيير في المعمارية.

---

### ٣. SectionTemplateLibrary → Database-backed Templates (تأثير: عالي، سهولة: منخفضة)

**المشكلة:** `SectionTemplateLibrary.php` سيصبح ضخماً جداً مع نمو عدد الـ Templates.

**المقترح:** إنشاء جدول `section_template_blueprints` يخزّن Templates في DB بدلاً من PHP file:

```sql
CREATE TABLE section_template_blueprints (
    id, key, label, icon, color, category,
    components JSON,  -- ['intro', 'cta', 'image']
    extra_fields JSON,
    blade_stub TEXT,
    is_builtin TINYINT,  -- 1 = built-in, 0 = user-created/marketplace
    is_active TINYINT,
    created_by INT,
    ...
);
```

**العائد:** يُحضّر للـ Marketplace، يُتيح للعملاء إنشاء templates خاصة بهم.

---

### ٤. BladeGenerator Design Awareness (تأثير: متوسط، سهولة: متوسطة)

**المشكلة:** الـ scaffold المُولَّد يستخدم CSS classes عامة (`section-title`, `section-desc`) غير مرتبطة بـ design system المشروع (Tailwind).

**المقترح:** إضافة `render_style` لـ SectionDefinition أو لـ BladeGenerator:
- `style: 'tailwind'` → يُولّد بـ Tailwind classes
- `style: 'plain'` → يُولّد HTML فارغ (الحالي)
- `style: 'palgoals'` → يُولّد بـ Palgoals design tokens

```php
// في COMPONENT_LABELS + TAG_BY_KEY نُضيف CLASS_MAP per style:
private const TAILWIND_CLASSES = [
    'eyebrow' => 'text-red-brand font-bold uppercase',
    'title'   => 'text-purple-brand font-extrabold text-2xl md:text-4xl',
    'subtitle'=> 'text-purple-brand/80 text-base md:text-lg',
];
```

**العائد:** يُقلص وقت التعديل بعد الـ scaffold من 10-15 دقيقة إلى دقيقتين.

---

### ٥. ~~ComponentLibrary ↔ BladeGenerator Sync~~ ✅ مُنفَّذة

`BladeGenerator` الآن يبني خريطة `field_key → component` ديناميكياً من `ComponentLibrary::all()` في runtime — لا ثابت `COMPONENT_FIELD_GROUPS` داخلي. إضافة Component في `ComponentLibrary` تنعكس تلقائياً على الـ scaffold.

**التوثيق:** `docs/COMPONENT_SYNC_REFACTOR_REPORT.md`

---

### ٦. Phase 3 — File Status Indicator في UI (تأثير: متوسط، سهولة: عالية)

**المشكلة:** المستخدم لا يعلم حالة ملف Blade (missing / exists / external) قبل الضغط على "توليد وكتابة مباشرة".

**المقترح:** إضافة Status Badge في تبويب Blade يُظهر:
- 🔴 `missing` — الملف غير موجود على disk
- 🟢 `exists` — موجود وكتبه النظام (`blade_written_at != null`)
- 🟡 `external` — موجود لكن لم يكتبه النظام

**التنفيذ:** `SectionTemplateFileWriter::fileStatus($definition)` موجود مسبقاً — يكفي استدعاؤه في edit.blade.php.  
**العائد:** تجربة مستخدم أفضل وتجنب مفاجأة الـ 409.

---

## ملخص تنفيذي

### أين نحن الآن؟

النظام الحالي يُغطي **دورة الحياة الكاملة** للمطور:

```
إنشاء بلوبرينت → إضافة حقول → توليد Blade → كتابة ملف → ربط بصفحة → عرض Frontend
     ✅                 ✅              ✅          ✅ (Phase 2)    Manual        ✅
```

الخطوة الوحيدة اليدوية المتبقية هي ربط الـ `Section instance` بالـ `SectionDefinition` في DB.

### الطريق للأمام

**الأولوية القصيرة المدى:**
1. ~~Phase 2 (Auto Write Blade)~~ ✅ **مُنفَّذة**
2. ~~ComponentLibrary ↔ BladeGenerator Sync~~ ✅ **مُنفَّذة**
3. Phase 3 (File Status Indicator) ← تُحسّن تجربة المستخدم مع Generate & Write

**الأولوية المتوسطة المدى:**
4. Section Rendering Cache ← ضروري عند scale
5. BladeGenerator Design Awareness ← يحسن جودة الـ scaffold

**الأولوية طويلة المدى:**
6. Database-backed Templates ← Marketplace readiness

---

*آخر تحديث: 2026-06-19 (Phase 2 Generate & Write — Implemented ✅ + Validated) | مُراجَع مع: SectionRenderer, SectionDefinitionFrontendViewDataFactory, SectionDefinitionRuntimeResolver, SectionTemplateRegistry, ComponentLibrary, SectionTemplateLibrary, FieldPresetLibrary, BladeGenerator, SectionTemplateFileWriter, SectionDefinitionController::generateAndWriteBladeFile(), SectionDefinition Model, SectionDefinitionField Model*
