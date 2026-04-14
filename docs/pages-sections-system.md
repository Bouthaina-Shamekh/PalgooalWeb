# نظام Pages & Sections — توثيق مرجعي شامل
## PalGoals — Laravel 12 · PHP 8.2 · Livewire 3

> **الغرض من هذا الملف**: مرجع تقني دائم لأي مطور أو جلسة AI تعمل على هذا الجزء من المشروع.  
> **آخر تحديث**: 2026-04-15

---

## 1. نظرة عامة (Overview)

نظام الصفحات والأقسام هو قلب مشروع PalGoals. يتيح إنشاء صفحات تسويقية وصفحات خاصة بكل tenant، وتعديل محتواها عبر واجهة بصرية (Visual Builder) أو واجهة أقسام منظمة (Sections Builder).

### المفاهيم الأساسية

| المفهوم | الوصف |
|---------|-------|
| **Page** | صفحة تحتوي على ترجمات (عنوان، slug، محتوى) وأقسام |
| **Section** | كتلة محتوى داخل صفحة (hero, features, pricing...) |
| **builder_mode** | يحدد أي builder يستخدمه المستخدم: `visual` أو `sections` |
| **context** | `marketing` للموقع الرئيسي، `tenant` لمواقع العملاء |
| **tenant_id** | ربط اختياري للصفحة أو القسم بـ tenant محدد |
| **SectionDefinition** | blueprint يصف بنية قسم معين (المتغيرات، الحقول) |

---

## 2. قاعدة البيانات (Database Schema)

### جدول `pages`

| العمود | النوع | الوصف |
|--------|-------|-------|
| `id` | bigint PK | |
| `context` | string | `marketing` أو `tenant` |
| `subscription_id` | bigint nullable FK | رابط للـ subscription (للـ tenant pages) |
| `tenant_id` | bigint nullable | ملكية tenant اختيارية |
| `builder_mode` | string(20) nullable | `visual` أو `sections` |
| `is_active` | boolean | |
| `is_home` | boolean | صفحة رئيسية؟ |
| `published_at` | datetime nullable | |
| `created_at / updated_at` | timestamps | |

**Indexes**: `tenant_id`

---

### جدول `page_translations`

| العمود | النوع | الوصف |
|--------|-------|-------|
| `id` | bigint PK | |
| `page_id` | bigint FK → pages | |
| `locale` | string | كود اللغة (ar, en...) |
| `slug` | string nullable | |
| `title` | string | |
| `content` | longtext | محتوى CKEditor |
| `meta_title` | string nullable | |
| `meta_description` | text nullable | |
| `meta_keywords` | json nullable | cast to array |
| `og_image` | string nullable | |

**Unique**: `(page_id, locale)` — `(slug, locale)`  
**Index**: `(locale, slug)` للبحث السريع

---

### جدول `sections`

| العمود | النوع | الوصف |
|--------|-------|-------|
| `id` | bigint PK | |
| `page_id` | bigint FK → pages | |
| `section_definition_id` | bigint nullable FK → section_definitions | رابط للـ blueprint (nullable للتوافق مع القديم) |
| `tenant_id` | bigint nullable | |
| `type` | string | نوع القسم (hero, features, pricing...) |
| `variant` | string nullable | نسخة بصرية بديلة |
| `style` | json | cast to array — إعدادات التصميم |
| `order` | integer | ترتيب القسم في الصفحة |
| `is_active` | boolean | |

**Indexes**: `tenant_id` — `(section_definition_id, type)`

---

### جدول `section_translations`

| العمود | النوع | الوصف |
|--------|-------|-------|
| `id` | bigint PK | |
| `section_id` | bigint FK → sections | |
| `tenant_id` | bigint nullable | |
| `locale` | string | |
| `title` | string nullable | |
| `content` | json | **cast to array** — كل محتوى القسم هنا |

**Unique**: `(section_id, locale)`

> ⚠️ **تنبيه مهم**: عمود `content` يقبل أي JSON بدون schema validation. تأكد دائماً أن البيانات المحفوظة تطابق بنية النوع المتوقعة.

---

### جدول `section_definitions`

Blueprint يصف قسماً قابلاً لإعادة الاستخدام. **لا يحتوي على محتوى فعلي**.

| العمود | النوع | الوصف |
|--------|-------|-------|
| `section_key` | string unique | معرّف ثابت للمطور |
| `label` | string | اسم يظهر في الـ admin |
| `description` | text nullable | وصف داخلي |
| `category` | string nullable | تجميع في الـ admin |
| `editor_mode` | string | `dynamic` أو `custom_preset` |
| `custom_editor_key` | string nullable | مفتاح الـ preset عند `editor_mode = custom_preset` |
| `settings` | json nullable | metadata للـ editor/runtime فقط — لا render logic |
| `schema` | json nullable | metadata للأدوات المستقبلية |
| `is_active` | boolean | |
| `is_visible` | boolean | يتحكم في ظهور القسم في قوائم الاختيار |
| `sort_order` | int | |

---

### جدول `section_definition_fields`

حقول كل `SectionDefinition`. locale-agnostic (لا تخزن كود لغة).

| العمود | النوع | الوصف |
|--------|-------|-------|
| `section_definition_id` | bigint FK | |
| `field_key` | string | معرّف ثابت داخل الـ definition |
| `label` | string | اسم يظهر في الـ admin |
| `help_text` | text nullable | |
| `field_type` | string | `text`, `textarea`, `url`, `media`, `number`, `boolean`, `select`, `richtext` |
| `field_scope` | string | `shared` أو `translatable` |
| `default_value` | json nullable | |
| `options` | json nullable | للـ select fields |
| `settings` | json nullable | |
| `schema` | json nullable | |
| `is_required` | boolean | |
| `is_active` | boolean | |
| `sort_order` | int | |

**Unique**: `(section_definition_id, field_key)`

---

### تطور قاعدة البيانات عبر الـ Migrations

```
pages:             create (2025-06) → +context/subscription_id (2025-11)
                   → +builder_mode (2026-03) → +tenant_id (2026-03)

sections:          create +key (2025-06) → rename key→type, +variant/is_active (2025-11)
                   → +style (2025-12) → +tenant_id (2026-03)
                   → +section_definition_id nullable FK (2026-04)

section_definitions + section_definition_fields: created 2026-04-11
```

---

## 3. Models

### `App\Models\Page`

```php
// Fillable
context, subscription_id, tenant_id, builder_mode,
is_active, is_home, published_at

// Casts
is_active → bool
is_home   → bool
published_at → datetime

// Relations
translations()      → HasMany PageTranslation
sections()          → HasMany Section (ordered by 'order')
builderStructure()  → HasOne PageBuilderStructure
builderStructures() → HasMany PageBuilderStructure
subscription()      → BelongsTo Subscription
tenant()            → BelongsTo (via subscription_id FK)

// Helper
translation(?locale) → eager-load-aware, fallback to first translation

// Scopes
scopeWhereSlug($slug, $locale)
scopeMarketing()          // context = 'marketing'
scopeTenant($tenant?)
scopeActive()

// Accessors (shortcuts to current translation)
$page->slug
$page->title
```

---

### `App\Models\Section`

```php
// Fillable
page_id, section_definition_id, tenant_id,
type, variant, style, order, is_active

// Casts
section_definition_id → int
is_active → bool
style     → array

// Relations
translations()      → HasMany SectionTranslation
page()              → BelongsTo Page
sectionDefinition() → BelongsTo SectionDefinition
tenant()            → BelongsTo
image()             → (media relation)

// Helper
translation(?locale) → same pattern as Page

// Scopes
scopeTenant($tenant?) → returns query unchanged if null
```

---

### `App\Models\Sections\SectionDefinition`

```php
// Constants
EDITOR_MODE_DYNAMIC        = 'dynamic'
EDITOR_MODE_CUSTOM_PRESET  = 'custom_preset'

// Relations
fields()    → HasMany SectionDefinitionField (ordered by sort_order)
sections()  → HasMany Section
templates() → BelongsToMany (via section_definition_template pivot)

// Methods
primaryTemplate()    → first template in pivot
primaryTemplateKey() → key of primary template
```

---

### `App\Models\Sections\SectionDefinitionField`

```php
// Field Type Constants
TEXT, TEXTAREA, RICHTEXT, URL, MEDIA, NUMBER, BOOLEAN, SELECT

// Scope Constants
FIELD_SCOPE_SHARED       = 'shared'
FIELD_SCOPE_TRANSLATABLE = 'translatable'

// Methods
isTranslatable() → field_scope === 'translatable'
```

---

## 4. Controllers

### `Admin\PageController`

**المسؤولية**: CRUD كامل لصفحات التسويق (`context = marketing`).

| Method | Route | الوصف |
|--------|-------|-------|
| `index()` | GET /admin/pages | قائمة مع withCount(sections, builderStructures)، paginate(20) |
| `store()` | POST /admin/pages | DB::transaction، validates slug uniqueness per locale |
| `update()` | PUT /admin/pages/{page} | updateOrCreate للترجمات |
| `destroy()` | DELETE /admin/pages/{page} | |
| `toggleActive()` | POST .../toggle-active | |
| `setHome()` | POST .../set-home | يلغي is_home لكل الصفحات ثم يعيّن الواحدة |
| `updateBuilderMode()` | POST .../builder-mode | يغيّر `builder_mode` |

---

### `Admin\SectionController` ← **Base Controller**

**المسؤولية**: workspace كامل للأقسام. يُستخدم مباشرة للـ admin ويُرث للـ client.

#### Methods الأساسية

| Method | الوصف |
|--------|-------|
| `index()` | الـ workspace الرئيسي |
| `preview()` | iframe preview عبر SectionWorkspacePreviewViewDataFactory |
| `create()` | form اختيار النوع |
| `store()` | حفظ section جديد |
| `quickStore()` | **AJAX** — ينشئ section بمحتوى افتراضي، يُعيد JSON مع `section_card_html` و`redirect_url` و`editor_url` |
| `edit()` | صفحة كاملة للتعديل |
| `editor()` | **AJAX** — يُعيد `sidebar-editor` partial فقط |
| `update()` | يُعيد JSON إذا كان الطلب AJAX |
| `toggleActive()` | |
| `rename()` | |
| `move()` | تبديل order مع القسم أعلاه/أسفله |
| `reorder()` | **AJAX JSON** — يقبل مصفوفة IDs ويعيد ترتيب 1-based |
| `duplicate()` | نسخ مع `is_active = false` |
| `destroy()` | |

#### Methods القابلة للـ Override (للـ inheritance)

```php
workspaceRoutePrefix()          // اسم الـ route group
workspaceMode()                 // 'admin' أو 'client'
workspaceModeLabel()
workspaceBaseRouteParameters()  // parameters مشتركة في كل routes
workspaceViewData()             // بيانات إضافية للـ views
workspaceShellBackUrl()
workspaceFrontUrl()
workspaceVisualBuilderUrl()
workspaceBuilderModeUrl()
```

#### Methods الداخلية المهمة

```php
availableSectionTypes()      // ~20 نوع
normalizeContentByType()     // switch/case لتنظيف البيانات حسب النوع
createDefaultSection()       // يُنشئ section مع محتوى افتراضي
defaultContentForType()      // المحتوى الافتراضي لكل نوع
sectionLibraryTypes()        // يدمج code registry مع DB definitions
```

> ⚠️ `normalizeContentByType()` دالة ضخمة تنمو مع كل نوع جديد — يُنصح مستقبلاً بنقل هذا المنطق لـ per-type handler classes.

---

### `Client\SubscriptionHomepageEditorController` ← يرث SectionController

**Overrides**:
- `workspaceRoutePrefix()` → `'client.subscriptions.homepage-editor.'`
- `workspaceMode()` → `'client'`
- `workspaceVisualBuilderUrl()` → `''` (لا visual builder للعملاء)
- `workspaceBuilderModeUrl()` → `''`
- يضيف `workspacePageSwitcherData()` للتبديل بين صفحات متعددة

---

### `Client\SubscriptionPageEditorController` ← يرث SectionController

يضيف إدارة صفحات الـ tenant:

| Method | الوصف |
|--------|-------|
| `pages()` | قائمة صفحات الـ tenant |
| `storePage()` | إنشاء صفحة tenant مع slug فريد |
| `updatePageSettings()` | |
| `setHomePage()` | |
| `destroyPage()` | |

---

### `Admin\PageBuilderController`

يتعامل مع الـ Visual Builder (GrapesJS):

| Method | الوصف |
|--------|-------|
| `edit()` | صفحة الـ builder |
| `loadData()` | تحميل بيانات GrapesJS |
| `saveData()` | حفظ بيانات GrapesJS |
| `publish()` | نشر |

---

## 5. Support Classes

### `SectionRegistry`

سجل static بسيط يربط section type باسم view أو config.

```php
SectionRegistry::register($type, $config)
SectionRegistry::get($type)
SectionRegistry::all()
```

---

### `SectionRenderer`

المحرك المركزي للـ rendering. يجرب استراتيجيتين:

```
render($section, $locale, $tenant?)
  ↓
  1. renderDefinitionDriven()
     → SectionDefinitionFrontendViewDataFactory
     → يُعيد HTML إذا وُجد definition مرتبط
  ↓ fallback
  2. renderRegisteredSection()
     → SectionRegistry::get($type)
     → يُعيد <!-- Section type 'x' not registered --> إذا لم يجد
```

---

### `SectionEditorDataFactory`

يبني حالة الـ editor الكاملة قبل إرسالها للـ view.

```
build($section, $locale)
  ↓
  - normalizeType
  - resolveTypeCapabilities (SectionEditorTypeCapabilities)
  - choose editor mode:
      customPresetEditor → إذا كان custom_preset
      dynamicEditor      → إذا كان dynamic definition
      neither            → legacy typeFlags
  - resolve editorSchema from schemaRegistry
  - build repeater state
  - build localeViewData
```

---

### `SectionEditorTypeCapabilities`

يُعرّف قدرات كل section type في الـ legacy editor.

```php
// TYPE_CONFIG: ~20 نوع، كل نوع عنده:
typeFlags: [
    isProgrammingShowcase,
    isHeroCampaign,
    isServicesList,
    isPricingSection,
    // ...
]
fieldFlags: [
    showEyebrowField,
    showPrimaryButtonFields,
    showSecondaryButtonFields,
    showFeaturesTextField,
    showMediaField,
    // ...
]

// Methods
SectionEditorTypeCapabilities::for($type)      // مع merging defaults
SectionEditorTypeCapabilities::supports($type) // هل يتعامل معه الـ legacy editor؟
```

---

### `SectionWorkspacePreviewViewDataFactory`

يبني بيانات الـ preview iframe:

```php
build() → [
    seo,
    stylesheetUrl,
    scriptUrl,
    showFrontChrome,       // false للـ tenant
    isTenantPagePreview,
    highlightSectionId,
    previewBlocks,
    sectionTypes,
    tenantHeaderRenderData,
    tenantFooterRenderData,
]
```

---

### `DeveloperSectionManagementArchitecture`

ملف توثيق معماري (ليس كود تشغيلي). يوثق:
- الحدود بين layer التعريف و layer المحتوى
- ماذا يخزن كل layer / ماذا يجب ألا يخزن
- Support classes المخطط لها

---

## 6. الـ Views والـ UI

### البنية العامة للملفات

```
resources/views/dashboard/pages/
├── index.blade.php          ← قائمة الصفحات
├── create.blade.php         ← wrapper → يتضمن partials/form
├── edit.blade.php           ← wrapper → يتضمن partials/form
├── partials/
│   └── form.blade.php       ← الـ form الرئيسي
└── sections/
    ├── layouts/
    │   └── workspace.blade.php  ← layout مستقل (لا يرث dashboard)
    ├── index.blade.php          ← الـ workspace الرئيسي
    ├── create.blade.php         ← form اختيار النوع
    ├── edit.blade.php           ← صفحة التعديل الكاملة
    ├── preview.blade.php        ← iframe preview
    └── partials/
        ├── sidebar-editor.blade.php       ← drawer panel للتعديل
        ├── editor-form.blade.php          ← orchestrator الـ form
        ├── outline-item.blade.php         ← بطاقة قسم في القائمة
        ├── icon-library-modal.blade.php   ← modal اختيار الأيقونة
        ├── dynamic-editor/
        │   ├── renderer.blade.php         ← يُكرر على الحقول
        │   └── fields/
        │       ├── text.blade.php
        │       ├── textarea.blade.php
        │       ├── url.blade.php
        │       ├── select.blade.php
        │       ├── boolean.blade.php
        │       ├── media.blade.php
        │       └── number.blade.php
        ├── fields/
        │   └── schema-field-renderer.blade.php  ← router للحقول
        ├── repeaters/
        │   ├── services-repeater.blade.php
        │   ├── campaign-features-repeater.blade.php
        │   └── pricing-plans-repeater.blade.php
        ├── blocks/
        │   ├── hero-campaign-cta-fields.blade.php
        │   └── hosting-pricing-config-fields.blade.php
        ├── media/
        │   └── tech-stack-logos.blade.php
        └── custom-presets/
            └── hosting-hero.blade.php
```

---

### Workspace Layout (`sections/layouts/workspace.blade.php`)

Layout مستقل كامل (standalone HTML، لا يرث الـ dashboard layout).

**يتضمن**:
- Header: زر رجوع، عنوان الصفحة، mode badge، language switcher (`variant="builder"`)، روابط Preview + Visual Builder
- Sidebar toggle يُحفظ في `localStorage`
- Scripts: SortableJS، media-picker.js، SweetAlert2، icon-library-modal

**Global JS function**:
```javascript
sectionsShowAlert(type, message)
// type='success' → toast
// type='error'   → modal
```

---

### الـ Workspace الرئيسي (`sections/index.blade.php`)

يدعم mode `admin` و`client` مع labels مختلفة:

| العنصر | admin label | client label |
|--------|-------------|--------------|
| قائمة الأقسام | "Sections" | "Blocks" |
| مكتبة الأقسام | "Section Library" | "Block Library" |

**العناصر الرئيسية**:
1. **Preview iframe**: device switcher (desktop/tablet/mobile) عبر CSS max-width
2. **Section outline list**: SortableJS drag-and-drop، `data-reorder-url`
3. **Section library drawer**: مجمّعة بـ category، search، RTL-aware

**RTL Detection**:
```php
$isRtl = current_dir() === 'rtl';
// يؤثر على اتجاه فتح الـ drawer
```

---

### أنظمة الـ Editor الثلاثة

#### 1. Legacy Editor (typeFlags/fieldFlags)
- يُستخدم للأنواع القديمة غير المنقولة بعد
- `SectionEditorTypeCapabilities::for($type)` يُعيد الـ flags
- الـ view يُظهر/يُخفي أقسام بناءً على الـ flags

#### 2. Custom Preset Editor
- لثلاثة أنواع خاصة: `hosting_hero`، `wordpress_ai_promo`، `website_protection`
- كل preset له blade view خاص به في `custom-presets/`
- يُحدد في `config/sections.php` تحت `custom_preset_registry`

#### 3. Dynamic Editor (الهدف المستقبلي)
- مبني على `SectionDefinition` + `SectionDefinitionField`
- الحقول تُبنى تلقائياً من قاعدة البيانات
- `dynamic-editor/renderer.blade.php` يُكرر على الحقول
- كل نوع حقل له partial مستقل في `dynamic-editor/fields/`

**تدفق اختيار الـ editor في `editor-form.blade.php`**:
```
$editorData['editorMode']
    === 'custom_preset' → include custom-presets/{key}.blade.php
    === 'dynamic'       → include dynamic-editor/renderer.blade.php
    default             → legacy typeFlags rendering
```

---

### Repeaters

كل repeater يتبع نفس النمط:

```html
<!-- قائمة العناصر الموجودة -->
<div data-repeater-list>
  @foreach($items as $index => $item)
    <!-- عنصر مع: drag handle، duplicate، remove، accordion toggle -->
    <input name="content[items][__INDEX__][field]" ...>
  @endforeach
</div>

<!-- template للعناصر الجديدة -->
<template data-repeater-template>
  <!-- نفس البنية مع __INDEX__ placeholder -->
</template>

<!-- زر إضافة عنصر جديد -->
<button data-repeater-add>Add Item</button>
```

JS خارجي يتولى: clone template، replace `__INDEX__`، append to list.

---

## 7. الـ Config (`config/sections.php`)

### `icon_library`
30 أيقونة Tabler، كل أيقونة:
```php
['label' => 'Server', 'value' => 'ti ti-server', 'keywords' => ['server', 'hosting']]
```
تُستخدم في `icon-library-modal` للبحث والاختيار.

---

### `template_registry`
خريطة `template_key → view`:
```php
'fallback_view' => 'front.sections.fallback',
'map' => [
    'hero_basic'    => ['label' => '...', 'view' => 'front.sections.hero.basic', 'category' => 'Hero'],
    'features_grid' => [...],
    // ~30 نوع
]
```

---

### `custom_preset_registry`
```php
'hosting_hero' => [
    'label'   => 'Hosting Hero',
    'view'    => 'dashboard.pages.sections.partials.custom-presets.hosting-hero',
    'builder' => 'buildHostingHeroContent',  // method في SectionEditorDataFactory
],
// + wordpress_ai_promo, website_protection

'legacy_section_key_bridge' => [
    // خريطة تربط أنواع قديمة بـ template keys
]
```

---

## 8. الـ Routes

### Admin Routes (`routes/dashboard.php`)

```php
// Pages CRUD
GET    /admin/pages                           → PageController@index           [dashboard.pages.index]
GET    /admin/pages/create                    → PageController@create
POST   /admin/pages                           → PageController@store
GET    /admin/pages/{page}/edit               → PageController@edit
PUT    /admin/pages/{page}                    → PageController@update
DELETE /admin/pages/{page}                    → PageController@destroy
POST   /admin/pages/{page}/toggle-active      → PageController@toggleActive
POST   /admin/pages/{page}/set-home           → PageController@setHome
POST   /admin/pages/{page}/builder-mode       → PageController@updateBuilderMode

// Visual Builder
GET    /admin/pages/{page}/builder            → PageBuilderController@edit      [dashboard.pages.builder]

// Sections Workspace
GET    /admin/pages/{page}/sections           → SectionController@index         [dashboard.pages.sections.index]
GET    /admin/pages/{page}/sections/preview   → SectionController@preview
POST   /admin/pages/{page}/sections/quick-store → SectionController@quickStore
POST   /admin/pages/{page}/sections/reorder   → SectionController@reorder
GET    /admin/pages/{page}/sections/{section}/editor → SectionController@editor
GET    /admin/pages/{page}/sections/{section}/edit   → SectionController@edit
PUT    /admin/pages/{page}/sections/{section}        → SectionController@update
POST   /admin/pages/{page}/sections/{section}/toggle-active
POST   /admin/pages/{page}/sections/{section}/rename
POST   /admin/pages/{page}/sections/{section}/move
POST   /admin/pages/{page}/sections/{section}/duplicate
DELETE /admin/pages/{page}/sections/{section}        → SectionController@destroy

// Section Definitions CRUD
/admin/section-definitions/{...}
```

---

### Client Routes (`routes/client.php`)

```php
// Tenant Pages Management
GET    /client/subscriptions/{sub}/pages              → SubscriptionPageEditorController@pages
POST   /client/subscriptions/{sub}/pages              → @storePage
PUT    /client/subscriptions/{sub}/pages/{page}       → @updatePageSettings
POST   /client/subscriptions/{sub}/pages/{page}/set-home
DELETE /client/subscriptions/{sub}/pages/{page}

// Tenant Page Sections Workspace
/client/subscriptions/{sub}/pages/{page}/editor/{...} → [نفس endpoints admin sections]

// Homepage Editor
/client/subscriptions/{sub}/homepage/editor/{...}     → SubscriptionHomepageEditorController
```

---

## 9. الـ Front-end Rendering

### `Front\PageController`

```php
home()
  → finds: marketing + is_home=true + active
  → fallback: first active marketing page
  → renders: front.pages.page

show($slug)
  → tries: current locale first
  → fallback: any locale
  → 301 redirect إذا كان الـ slug بلغة مختلفة (canonical redirect)
  → renders: front.pages.page
```

---

### `legacy-section.blade.php` (332 سطر)

المحرك المركزي للـ rendering في الـ front. يُنفّذ:

```
1. SectionRenderer::renderDefinitionDriven($section, $locale)
   → إذا نجح: output raw HTML مباشرة

2. إذا فشل:
   → يحل $content من translation->content[]
   → SectionQueryResolver::resolve() للبيانات من DB (pricing plans, etc.)
   → @switch($resolvedSectionType):
       case 'hero_basic':    → @include('front.sections.hero.basic')
       case 'features_grid': → @include('front.sections.features.grid')
       // ~20 case
       default: <div>section type does not have a renderer yet</div>
```

---

## 10. أنماط برمجية مهمة (Patterns)

### نمط الوراثة في الـ Controllers

```
SectionController (base)
├── Admin\SectionController  (direct use)
├── Client\SubscriptionHomepageEditorController (override workspaceMode → 'client')
└── Client\SubscriptionPageEditorController     (override + adds page management)
```

إضافة سياق workspace جديد = إنشاء controller يرث `SectionController` ويُعيد تعريف الـ overridable methods فقط.

---

### نمط quickStore

```
POST /sections/quick-store
  ↓
  SectionController@quickStore
  ↓
  createDefaultSection($type)
    → defaultContentForType($type) → محتوى افتراضي
    → Section::create(...)
    → SectionTranslation::create(...) per locale
  ↓
  return JSON {
    redirect_url:      رابط للتعديل الكامل
    editor_url:        رابط لـ sidebar editor
    section_card_html: HTML جاهز للإدراج في الـ DOM
  }
```

---

### نمط editor/update عبر AJAX

```javascript
// فتح الـ sidebar editor
fetch(editor_url)                     // GET → يُعيد partial HTML
  .then(html => injectIntoSidebar())

// حفظ التعديلات
fetch(update_url, {method: 'PUT'})    // إذا X-Requested-With: XMLHttpRequest
  → Controller يُعيد JSON بدلاً من redirect
  → JS يُحدّث الـ UI ويُغلق الـ sidebar
```

---

### نمط Reorder

```javascript
// SortableJS drag-and-drop
sortable.onEnd = () => {
  const ids = [...document.querySelectorAll('[data-section-id]')]
                .map(el => el.dataset.sectionId);

  fetch(reorder_url, {
    method: 'POST',
    body: JSON.stringify({ sections: ids }) // مصفوفة IDs بالترتيب الجديد
  });
}

// Controller
reorder(): يحوّل IDs لـ 1-based order ويحفظ
```

---

## 11. نقاط التحذير والـ Technical Debt

| المشكلة | الأثر | الأولوية |
|---------|-------|---------|
| ثلاثة أنظمة editor في وقت واحد (legacy/preset/dynamic) | تعقيد ذهني عالٍ | متوسطة |
| `normalizeContentByType()` switch/case ضخمة | صعبة الصيانة مع كل نوع جديد | متوسطة |
| `legacy-section.blade.php` بـ ~20 case | يتعارض مع SectionRenderer الجديد | منخفضة-متوسطة |
| `content` JSON بدون schema validation | أخطاء صامتة في الـ rendering | عالية |
| `builder_mode` بدون enum/constants | أي string يُقبل في DB | منخفضة |
| `section_definition_id` nullable | لا تمييز برمجي بين "legacy" و"خطأ" | منخفضة |
| Authorization بين admin و tenant sections | **مخاطرة أمنية محتملة** | **عالية جداً** |
| `duplicate()` ينشئ `is_active=false` بصمت | قد يُربك المستخدم | منخفضة |

---

## 12. الخلاصة السريعة للمطور الجديد

```
صفحة جديدة؟
  → PageController@store → Page + PageTranslation per locale

قسم جديد سريع؟
  → quickStore → defaultContentForType → SectionTranslation per locale

تعديل قسم؟
  → editor() AJAX → sidebar-editor partial
  → update() AJAX → JSON response

preview؟
  → SectionWorkspacePreviewViewDataFactory → sections/preview.blade.php (iframe)
  → SectionRenderer::render() لكل section

نوع جديد من الأقسام؟
  1. أضف config في sections.php (template_registry)
  2. أضف defaultContentForType() في SectionController
  3. أضف normalizeContentByType() case
  4. أضف SectionEditorTypeCapabilities::TYPE_CONFIG entry
  5. أنشئ front view في front/sections/
  6. أضف case في legacy-section.blade.php
  --- أو ---
  1. أنشئ SectionDefinition record في DB (editor_mode=dynamic)
  2. أضف SectionDefinitionField records
  3. أنشئ front view + ربطه عبر SectionRenderer
```
