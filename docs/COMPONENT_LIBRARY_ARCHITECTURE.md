# Component Library Architecture
**Date:** 2026-06-19  
**Status:** Phase 1 Complete ✅

---

## المشكلة التي يحلّها هذا النظام

قبل Component Library، كل Section Template كانت تُعيد تعريف نفس الحقول:

```php
// قبل — كل template يكرر eyebrow/title/subtitle/button_* من الصفر
'hero' => [
    'fields' => [
        ['field_key' => 'eyebrow',   ...],  // مكرر في 6 templates
        ['field_key' => 'title',     ...],  // مكرر في 6 templates
        ['field_key' => 'subtitle',  ...],  // مكرر في 6 templates
        ['field_key' => 'button_label', ...], // مكرر في 3 templates
        // ...
    ],
],

'features-grid' => [
    'fields' => [
        ['field_key' => 'eyebrow',   ...],  // نفس الحقل مكرر مرة أخرى
        ['field_key' => 'title',     ...],
        // ...
    ],
],
```

**المشاكل:**
1. تغيير تعريف `title` (مثلاً: إضافة validation rule) يتطلب تعديل 6 ملفات
2. تصنيف Scope غير موحّد عبر templates
3. إضافة template جديد يتطلب معرفة كاملة بكل حقل

---

## طبقات المعمارية الثلاث

```
┌─────────────────────────────────────────────────────────────────┐
│                    FIELD PRESETS                                │
│  app/Support/Sections/FieldPresetLibrary.php                    │
│                                                                 │
│  مجموعة حقول تُطبَّق على SectionDefinition موجود               │
│  مثال: "SEO Fields", "Social Media Fields"                      │
│  الاستخدام: Admin → Manage Fields → Apply Preset               │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼ يُستخدم في
┌─────────────────────────────────────────────────────────────────┐
│                    COMPONENTS                                   │
│  app/Support/Sections/ComponentLibrary.php                      │
│                                                                 │
│  مجموعة حقول قانونية (canonical) قابلة لإعادة الاستخدام         │
│  مثال: intro (eyebrow+title+subtitle), cta (button_*)           │
│  الاستخدام: تُدرج في Section Templates                          │
│                                                                 │
│  8 components: intro, description, cta, image,                  │
│                features, highlight, faq, testimonials, seo      │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼ يُستخدم في
┌─────────────────────────────────────────────────────────────────┐
│                    SECTION TEMPLATES                            │
│  app/Support/Sections/SectionTemplateLibrary.php                │
│                                                                 │
│  blueprint كامل = components[] + extra_fields[] + blade_stub    │
│  مثال: hero = [intro, cta, image] + blade Scaffold             │
│  الاستخدام: Admin → ⚡ من قالب                                  │
│                                                                 │
│  6 templates: hero, features-grid, content-showcase,            │
│               cta-banner, faq, testimonials                     │
└─────────────────────────────────────────────────────────────────┘
```

---

## المقارنة بين الطبقات الثلاث

| المعيار | Field Presets | Components | Section Templates |
|---------|--------------|-----------|------------------|
| **الغرض** | إضافة حقول لتعريف موجود | حقول قابلة لإعادة الاستخدام | blueprint كامل |
| **الاستخدام** | Admin UI → Apply Preset | داخل Section Templates | Admin UI → إنشاء من قالب |
| **المدخلات** | SectionDefinition (موجود) | مفاتيح component + extra_fields | اختيار من قائمة |
| **المخرجات** | حقول مُضافة للتعريف | حقول مُدمجة + مُزالة التكرار | Definition + Fields + blade_stub |
| **يُنشئ Definition؟** | ❌ لا | ❌ لا | ✅ نعم |
| **يُنشئ Blade file؟** | ❌ لا | ❌ لا | ⏳ Phase 2 |
| **مستوى التجريد** | أقل (single preset) | متوسط (reusable group) | أعلى (full section) |

---

## ComponentLibrary — التصميم

### `resolveFields()` — منطق الدمج

```php
ComponentLibrary::resolveFields(
    componentKeys: ['intro', 'cta', 'image'],
    extraFields: [
        ['field_key' => 'background_image', ...],
    ]
)
```

**النتيجة (مُرتَّبة حسب sort_order):**

| sort_order | field_key | من |
|---|---|---|
| 1 | eyebrow | intro |
| 2 | title | intro |
| 3 | subtitle | intro |
| 4 | button_label | cta |
| 5 | button_url | cta |
| 6 | button_target | cta |
| 7 | image | image |
| 8 | image_alt | image |
| 9 | image_position | image |
| 10 | background_image | extra_fields |

**قواعد الدمج:**
- **First occurrence wins:** إذا ظهر `title` في `intro` و`extra_fields` معاً → يُؤخذ من `intro` (الأسبقية)
- **لا تكرار:** حقل بنفس `field_key` لا يظهر مرتين
- **sort_order تسلسلي:** بدءاً من 1، بدون فجوات

### Components المتوفرة

| المفتاح | الحقول | الوصف |
|---------|-------|-------|
| `intro` | eyebrow, title, subtitle | عنوان القسم والعنوان الفرعي |
| `description` | description | نص طويل |
| `cta` | button_label, button_url, button_target | زر الدعوة للإجراء |
| `image` | image, image_alt, image_position | صورة مع نص بديل وموضع |
| `features` | features (repeater) | قائمة المميزات مع أيقونات |
| `highlight` | highlight_text | نص بارز / badge |
| `faq` | faqs (repeater) | أسئلة وأجوبة |
| `testimonials` | testimonials (repeater) | آراء العملاء |
| `seo` | meta_title, meta_description | بيانات SEO |

---

## SectionTemplateLibrary — التصميم (v2)

### قبل v2 (inline fields)
```php
'hero' => [
    'fields' => [
        ['field_key' => 'eyebrow', ...],
        ['field_key' => 'title',   ...],
        // كل حقل مُعرَّف يدوياً
    ],
]
```

### بعد v2 (component-based)
```php
'hero' => [
    'components'   => ['intro', 'cta', 'image'],  // ← قائمة مفاتيح
    'extra_fields' => [],                           // ← إضافات خاصة
    'blade_stub'   => '...',
    'definition'   => [...],
]
```

### الـ Templates المتوفرة

| المفتاح | Components | Extra Fields | إجمالي الحقول |
|---------|-----------|-------------|--------------|
| `hero` | intro, cta, image | — | 9 |
| `features-grid` | intro, features | — | 4 |
| `content-showcase` | intro, features, highlight, cta, image | — | 11 |
| `cta-banner` | intro, cta | background_image | 7 |
| `faq` | intro, faq | — | 4 |
| `testimonials` | intro, testimonials | — | 4 |

---

## Field Scope Compliance

جميع الحقول مُصنَّفة وفق قاعدة Multi-Tenant Field Scope Architecture (CLAUDE.md §٢أ):

### السؤال الفاصل:
> "في قالب يخدم موقعاً بالعربية والإنجليزية والفرنسية، هل يمكن أن تختلف قيمة هذا الحقل بين اللغات؟"

| الحقل | Scope | السبب |
|-------|-------|-------|
| eyebrow, title, subtitle | Translatable | نص يُترجم دائماً |
| description | Translatable | نص طويل يُترجم |
| button_label | Translatable | نص الزر يختلف بين locales |
| button_url | Translatable | `/ar/contact` ≠ `/en/contact`؛ WhatsApp بأرقام مختلفة |
| button_target | **Shared** | سلوك متصفح (`_self`/`_blank`) — لا يرتبط باللغة |
| image | **Shared** | أصل بصري — نفس الصورة لجميع اللغات |
| image_alt | Translatable | نص بديل لـ SEO — يُترجم |
| image_position | **Shared** | قرار تخطيط (left/right/center) |
| highlight_text | Translatable | badge text يُترجم |
| background_image | **Shared** | أصل بصري |
| meta_title, meta_description | Translatable | SEO copy يختلف بين locales |
| icon, icon_source, icon_media | **Shared** | رمز بصري، لا يتغير |
| name, position, quote (sub-fields) | Translatable | نصوص قابلة للترجمة |
| company, avatar (sub-fields) | **Shared** | اسم المؤسسة / صورة لا تتغير |

---

## كيفية إضافة Component جديد

**خطوة واحدة فقط:** أضف entry في `ComponentLibrary::ALL_COMPONENTS`:

```php
'pricing' => [
    'name'        => 'Pricing',
    'icon'        => 'ti-currency-dollar',
    'color'       => 'green',
    'description' => 'Pricing block with amount, period, and currency.',
    'fields' => [
        ['field_key' => 'price',    'label' => 'Price',    'field_type' => 'text', 'field_scope' => 'translatable', 'is_required' => false],
        ['field_key' => 'period',   'label' => 'Period',   'field_type' => 'text', 'field_scope' => 'translatable', 'is_required' => false],
        ['field_key' => 'currency', 'label' => 'Currency', 'field_type' => 'text', 'field_scope' => 'shared',        'is_required' => false],
    ],
],
```

ثم يمكن إدراجه في أي template:
```php
'pricing-hero' => [
    'components' => ['intro', 'pricing', 'cta'],
    ...
],
```

لا ملف آخر يحتاج تغيير.

---

## كيفية إضافة Template جديد

```php
'stats-section' => [
    'label'       => 'Stats Section',
    'icon'        => 'ti-chart-bar',
    'color'       => 'blue',
    'category'    => 'stats',
    'description' => 'Key statistics with numbers and labels.',

    'components' => ['intro'],
    'extra_fields' => [
        [
            'field_key'   => 'stats',
            'label'       => 'Statistics',
            'field_type'  => 'repeater',
            'field_scope' => 'translatable',
            'is_required' => false,
            'schema'      => [
                'item_schema' => [
                    ['key' => 'number', 'label' => 'Number', 'type' => 'text', 'required' => true,  'translatable' => false],
                    ['key' => 'label',  'label' => 'Label',  'type' => 'text', 'required' => true,  'translatable' => true],
                    ['key' => 'suffix', 'label' => 'Suffix', 'type' => 'text', 'required' => false, 'translatable' => false],
                ],
            ],
        ],
    ],

    'definition' => [
        'label'       => 'Stats Section',
        'section_key' => 'stats_section',
        'category'    => 'stats',
        // ...
    ],

    'blade_stub' => <<<'BLADE'
@php
    $title = $data['title'] ?? '';
    $stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
@endphp
<section class="section-stats">
    <h2>{{ $title }}</h2>
    <div class="stats-grid">
        @foreach($stats as $stat)
            <div class="stat-item">
                <strong>{{ $stat['number'] ?? '' }}{{ $stat['suffix'] ?? '' }}</strong>
                <span>{{ $stat['label'] ?? '' }}</span>
            </div>
        @endforeach
    </div>
</section>
BLADE,
],
```

---

## التوافق مع النظام القديم (Backward Compatibility)

**`SectionTemplateLibrary::resolveTemplateFields()`** يتحقق من الترتيب:

```php
public static function resolveTemplateFields(string $templateKey): array
{
    $template = self::get($templateKey);

    if (! is_array($template)) {
        return [];
    }

    // v2: component-based (الجديد)
    if (! empty($template['components'])) {
        return ComponentLibrary::resolveFields(
            $template['components'],
            $template['extra_fields'] ?? [],
        );
    }

    // v1: inline fields (backward-compat للـ templates القديمة)
    return $template['fields'] ?? [];
}
```

**`storeFromTemplate()`** يستخدم هذا المنطق مباشرةً — لا تغيير في الـ controller logic.

---

## Controller Flow (v2)

```
POST /admin/section-definitions/from-template
    │
    ├── validate(template_key ∈ SectionTemplateLibrary::keys())
    ├── Guard: section_key موجود → back with error
    │
    └── DB::transaction:
        ├── SectionDefinition::create([...])
        │
        ├── $fields = SectionTemplateLibrary::resolveTemplateFields($key)
        │   └── → ComponentLibrary::resolveFields(['intro','cta','image'], [])
        │         ├── intro fields: [eyebrow, title, subtitle]     sort: 1,2,3
        │         ├── cta fields:   [button_label, button_url, button_target]  sort: 4,5,6
        │         └── image fields: [image, image_alt, image_position] sort: 7,8,9
        │
        └── foreach $fields → $sectionDefinition->fields()->create([...])
            └── normalize options: [['value'=>'...','label'=>'...']] → pipe string
```

---

## الفرق الجوهري بين الطبقات

```
FieldPresetLibrary           ComponentLibrary              SectionTemplateLibrary
───────────────────          ─────────────────             ──────────────────────
preset = حقول جاهزة         component = حقول قانونية     template = blueprint كامل
تُضاف لتعريف موجود          تُدمج في templates           ينشئ تعريفاً + حقولاً
Admin UI: Apply Preset       داخلي (لا واجهة مباشرة)     Admin UI: ⚡ من قالب
لا تُنشئ Definition         لا تُنشئ Definition          ✅ تُنشئ Definition
لا تُنشئ Blade file         لا تُنشئ Blade file          ✅ blade_source (Phase 2: disk)
يختاره المشرف               مُستخدم بشكل برمجي فقط       يختاره المشرف
```

---

## مراجع

- `app/Support/Sections/ComponentLibrary.php` — تعريفات الـ Components
- `app/Support/Sections/SectionTemplateLibrary.php` — تعريفات الـ Templates (v2)
- `app/Support/Sections/FieldPresetLibrary.php` — Field Presets (مستوى أدنى)
- `app/Http/Controllers/Admin/SectionDefinitionController.php` — `storeFromTemplate()`
- `resources/views/dashboard/section_definitions/from-template.blade.php` — واجهة الاختيار
- `docs/FIELD_SCOPE_ARCHITECTURE.md` — قواعد Translatable/Shared
- `docs/section-definitions.md` — معمارية النظام الكاملة
- `CLAUDE.md` §٢أ — قاعدة Field Scope
