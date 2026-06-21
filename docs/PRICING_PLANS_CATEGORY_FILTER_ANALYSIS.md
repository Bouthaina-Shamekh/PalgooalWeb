# Pricing Plans Dynamic — Category Filter Analysis

**Date:** 2026-06-22  
**Type:** Pre-Implementation Analysis (No Code Changed)  
**Scope:** `pricing_plans_dynamic.blade.php` — إضافة فلتر تصنيف الخطط

---

## السؤال المطروح

> الأدمن يريد تحديد تصنيف الخطط التي تظهر داخل السكشن — مثلاً: "أظهر فقط خطط تصنيف الاستضافة".

---

## ١. هل يوجد نظام تصنيف خطط حالياً؟

**نعم — موجود بالكامل:**

| المكوّن | الحالة |
|---------|--------|
| `plan_categories` table | ✅ موجود (migration: 2025_05_03_132311) |
| `plan_category_translations` table | ✅ موجود (title + slug per locale) |
| `PlanCategory` Model | ✅ `app/Models/PlanCategory.php` |
| `PlanCategoryController` | ✅ `app/Http/Controllers/Admin/Management/PlanCategoryController.php` |
| Admin CRUD Views | ✅ `dashboard/management/plan_categories/` |
| FK على `plans` table | ✅ `plan_category_id` nullable → `plan_categories.id` (`nullOnDelete`) |

---

## ٢. اسم الجدول والأعمدة

```sql
-- التصنيفات
plan_categories              (id, is_active, position, timestamps, soft_deletes)
plan_category_translations   (id, plan_category_id, locale, title, slug, description)

-- الخطط
plans                        (..., plan_category_id UNSIGNED BIGINT NULL, ...)
```

**الـ FK:** `plans.plan_category_id → plan_categories.id`

---

## ٣. العلاقات الموجودة في Plan Model

```php
// Plan.php
public function category(): BelongsTo
{
    return $this->belongsTo(PlanCategory::class, 'plan_category_id');
}

// PlanCategory.php
public function plans(): HasMany
{
    return $this->hasMany(Plan::class, 'plan_category_id');
}
```

**إضافة:** `PlanCategory::scopeActive()` و `scopeOrdered()` (يرتب بـ `position` إذا وُجد العمود، وإلا بـ `id`).

---

## ٤. أفضل field_key للسكشن

**التوصية:** `plan_category_id` — **Shared** (قرار تصميمي لا يتغير بين اللغات)

```
field_key: plan_category_id
type:      select          ← قائمة منسدلة
scope:     shared          ← Shared (نفس القيمة لجميع اللغات)
required:  false           ← اختياري — بدون تحديد = جميع الخطط
```

**لماذا `shared`؟** تصنيف الخطط قرار هيكلي عام يسري على جميع لغات الموقع — بالضبط مثل `image_position` و `layout_style`.

---

## ٥. كيف يظهر كـ select في Page Builder؟

### المشكلة الجوهرية:

`DynamicSectionEditorRenderer::fieldOptions()` يقرأ فقط من `field->options` (JSON ثابت مخزّن في DB):

```php
// DynamicSectionEditorRenderer.php — السطر 399
protected function fieldOptions(SectionDefinitionField $field): array
{
    $options = is_array($field->options) ? $field->options : [];
    // يُحوّل الـ JSON الثابت إلى [{value, label}, ...]
    // لا يدعم استعلامات DB ديناميكية
}
```

**الخيارات المتاحة:**

| الخيار | الوصف | المزايا | العيوب |
|--------|-------|---------|--------|
| **أ. Static JSON** | تخزين `[{value: 1, label: "Hosting"}, ...]` في `field.options` | لا تغيير في النظام | يجب تحديثه يدوياً عند إضافة تصنيف جديد — هش |
| **ب. Text field** | الأدمن يكتب ID مباشرة | أبسط | تجربة مستخدم سيئة |
| **ج. Dynamic Source** | توسيع `fieldOptions()` ليدعم `settings.options_source: 'plan_categories'` | مرن — يستعلم DB تلقائياً | يتطلب توسيع النظام |
| **د. SectionQueryResolver** | نقل استعلام الخطط من الـ Blade إلى Resolver مثل `hostingPricingShowcase()` | نظيف معمارياً | تغيير بنيوي أكبر |

**التوصية: الخيار (ج) Dynamic Source** — توسيع بسيط ومحدود لـ `DynamicSectionEditorRenderer`.

---

## ٦. من أين تأتي الـ options؟

```php
// الاستعلام الموصى به لبناء الـ options:
PlanCategory::active()
    ->ordered()
    ->with(['translations'])
    ->get()
    ->map(fn ($cat) => [
        'value' => (string) $cat->id,
        'label' => $cat->title ?? "Category #{$cat->id}",
    ])
    ->prepend(['value' => '', 'label' => 'All Categories'])
    ->all();
```

`$cat->title` يستخدم `getTitleAttribute()` الموجود في Model (يُرجع الترجمة بحسب `app()->getLocale()`).

---

## ٧. Dynamic vs Hardcoded options — القرار

**يجب أن تكون ديناميكية من DB** — لأن:
- الأدمن يُنشئ ويُعدّل التصنيفات من واجهة الإدارة
- لا يوجد عدد ثابت أو أسماء ثابتة للتصنيفات
- الـ PlanCategory يدعم `is_active` و `position` — يجب أن تنعكس هذه التغييرات تلقائياً

**الحل الموصى به:** إضافة آلية `options_source` في `SectionDefinitionField.settings`:

```json
{
  "options_source": "plan_categories"
}
```

ثم في `DynamicSectionEditorRenderer::fieldOptions()`:

```php
protected function fieldOptions(SectionDefinitionField $field): array
{
    // 1. Check for dynamic source
    $settings = is_array($field->settings) ? $field->settings : [];
    if (isset($settings['options_source'])) {
        return match ($settings['options_source']) {
            'plan_categories' => $this->planCategoryOptions(),
            default           => [],
        };
    }

    // 2. Fall back to existing static JSON
    $options = is_array($field->options) ? $field->options : [];
    // ... existing logic unchanged
}

private function planCategoryOptions(): array
{
    return \App\Models\PlanCategory::active()
        ->ordered()
        ->with(['translations'])
        ->get()
        ->map(fn ($cat) => [
            'value' => (string) $cat->id,
            'label' => $cat->title ?? "Category #{$cat->id}",
        ])
        ->prepend(['value' => '', 'label' => '— All Categories —'])
        ->all();
}
```

---

## ٨. كيف تُعدَّل الاستعلام بأقل تغيير؟

### الاستعلام الحالي في `pricing_plans_dynamic.blade.php`:

```php
$allPlans = \App\Models\Plan::with(['translations'])
    ->active()
    ->orderBy('monthly_price_cents', 'asc')
    ->get();
```

### التعديل المقترح (تغيير واحد فقط):

```php
$plan_category_id = is_numeric($data['plan_category_id'] ?? null)
    ? (int) $data['plan_category_id']
    : null;

$allPlans = \App\Models\Plan::with(['translations'])
    ->active()
    ->when($plan_category_id, fn ($q) => $q->where('plan_category_id', $plan_category_id))
    ->orderBy('monthly_price_cents', 'asc')
    ->get();
```

**لا تغيير في:** ترتيب الخطط، منطق featured-in-middle، `$resolveFeatures`، الـ partials، كل الـ views.

---

## مقارنة مع `hostingPricingShowcase`

`SectionQueryResolver::hostingPricingShowcase()` يُطبّق نمطاً أكثر تعقيداً:
- يقبل **مصفوفة** من IDs (`visible_category_ids`)
- يجلب الخطط **مُجمَّعة ضمن التصنيف** (category → plans nested)
- يفلتر بـ `plan_type = TYPE_HOSTING`

**لماذا لا نستخدمه مباشرة؟**
- `pricing_plans_dynamic` ليس مسجلاً في `SectionQueryResolver::resolve()` — يعمل standalone في Blade
- `hostingPricingShowcase` يُغيّر شكل البيانات (categories مع nested plans) مما يتطلب إعادة كتابة الـ Blade بالكامل
- التعديل المقترح (سطر واحد `->when()`) هو الأقل تغييراً والأكثر أماناً

---

## خطوات التنفيذ (بالترتيب)

### Step 1 — توسيع `DynamicSectionEditorRenderer` (optional_source)
```
app/Support/Sections/DynamicSectionEditorRenderer.php
```
إضافة `fieldOptions()` override يدعم `settings.options_source = 'plan_categories'`.

### Step 2 — إضافة field جديد في SectionDefinition
من صفحة Edit للـ SectionDefinition:
```
field_key:    plan_category_id
label:        تصنيف الخطط
type:         select
scope:        shared
required:     false
settings:     {"options_source": "plan_categories"}
```

### Step 3 — تعديل `pricing_plans_dynamic.blade.php`
إضافة `$plan_category_id` قراءة من `$data` + `->when()` على الاستعلام.

### Step 4 — ترجمات
إضافة `site.All_Categories` و `site.Filter_By_Category` في `SiteTranslationsSeeder`.

---

## ملاحظات معمارية

### `visibility_scope = 'admin_only'`
`pricing_plans_dynamic` محمي بـ `admin_only` visibility scope — لا يظهر في client builder. هذا لا يتأثر بالتغيير المقترح.

### التوافق الخلفي (Backward Compatibility)
عند `plan_category_id = null` (قيمة فارغة أو غير محددة) — `->when(null, ...)` لا يُضيف أي شرط → يُعيد **جميع الخطط** كما كان سابقاً. لا كسر للسكشنات الموجودة.

### `plan_type` vs `plan_category_id`
| الفلتر | المستوى | مثال الاستخدام |
|--------|---------|----------------|
| `plan_type` | تقني (`multi_tenant` / `hosting`) | يُحدد نوع الخدمة داخلياً |
| `plan_category_id` | تسويقي (اسم مخصص من الأدمن) | "Starter", "Business", "Enterprise" |

الأدمن يحتاج `plan_category_id` — وهو المعرّض للتغيير في واجهة الإدارة.

---

## التوصية النهائية

```
النهج: Dynamic Source mechanism في DynamicSectionEditorRenderer
الحقل: plan_category_id — type:select, shared, settings.options_source = 'plan_categories'
الاستعلام: إضافة ->when($plan_category_id, ...) في Blade — سطر واحد
```

**الحجم الكلي للتغيير:**
- `DynamicSectionEditorRenderer.php`: ~20 سطر جديد (دالة `planCategoryOptions` + branch في `fieldOptions`)
- `pricing_plans_dynamic.blade.php`: 3 أسطر جديدة في `@php` block
- `DashboardTranslationsSeeder.php`: 1–2 مفتاح (اختياري)
- `SiteTranslationsSeeder.php`: 1 مفتاح (اختياري)
- لا تغيير في DB migrations
- لا تغيير في Models
- لا تغيير في Blade partials

**المخاطر:** منخفضة جداً — الفلتر اختياري بالكامل، لا كسر للسكشنات الحالية.
