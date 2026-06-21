# Pricing Plans Dynamic — Category Filter Implementation Report

**Date:** 2026-06-22  
**Type:** Feature Implementation  
**Based on:** `docs/PRICING_PLANS_CATEGORY_FILTER_ANALYSIS.md`

---

## الهدف

تمكين الأدمن من تحديد تصنيف الخطط التي تظهر داخل سكشن `pricing_plans_dynamic` من Page Builder، بدلاً من عرض جميع الخطط دائماً.

---

## الملفات المُعدَّلة

### معدّلة

| الملف | التغيير |
|-------|---------|
| `app/Support/Sections/DynamicSectionEditorRenderer.php` | إضافة دعم `options_source` في `fieldOptions()` + دالة `planCategoryOptions()` |
| `resources/views/front/sections/pricing/pricing_plans_dynamic.blade.php` | إضافة `$plan_category_id` من `$data` + `->when()` في الاستعلام |
| `database/seeders/SiteTranslationsSeeder.php` | إضافة `site.All_Categories` |

### جديدة

| الملف | المحتوى |
|-------|---------|
| `database/migrations/2026_06_22_000001_add_plan_category_filter_field_to_pricing_plans_dynamic.php` | يُدرج حقل `plan_category_id` في `section_definition_fields` لـ `pricing_plans_dynamic` |

### غير معدّلة (كما طُلب)

- `app/Models/Plan.php` — بدون تغيير
- `app/Models/PlanCategory.php` — بدون تغيير
- `database/migrations/` (الموجودة) — بدون تغيير
- `_plan_header.blade.php`, `_plan_features.blade.php`, `_plan_cta.blade.php`, `_plan_card_dynamic.blade.php` — بدون تغيير
- منطق الخصومات، الـ Toggle، الـ Featured card — بدون تغيير

---

## Step 1 — Dynamic Options Source

**الملف:** `app/Support/Sections/DynamicSectionEditorRenderer.php`

### التغييرات:

**1. استيراد جديد:**
```php
use App\Models\PlanCategory;
```

**2. تعديل `fieldOptions()`:**
```php
protected function fieldOptions(SectionDefinitionField $field): array
{
    // Dynamic source — query DB instead of reading static JSON.
    $settings = is_array($field->settings) ? $field->settings : [];
    if (isset($settings['options_source'])) {
        return match ($settings['options_source']) {
            'plan_categories' => $this->planCategoryOptions(),
            default           => [],
        };
    }

    // Static JSON options (existing logic — unchanged)
    $options = is_array($field->options) ? $field->options : [];
    // ...
}
```

**3. دالة جديدة `planCategoryOptions()`:**
```php
protected function planCategoryOptions(): array
{
    $categories = PlanCategory::active()
        ->ordered()
        ->with(['translations'])
        ->get()
        ->map(fn (PlanCategory $cat): array => [
            'value' => (string) $cat->id,
            'label' => $cat->title ?? "Category #{$cat->id}",
        ])
        ->all();

    return array_merge(
        [['value' => '', 'label' => t('site.All_Categories', 'All Categories')]],
        $categories,
    );
}
```

**الخصائص:**
- أول خيار دائماً: `['value' => '', 'label' => 'كل التصنيفات']`
- يستخدم `$cat->title` ← `getTitleAttribute()` ← `translatedTitle()` بحسب `app()->getLocale()`
- يُصفّي بـ `active()` — التصنيفات غير النشطة لا تظهر
- يرتب بـ `ordered()` ← `position` إذا وُجد، وإلا `id`
- إذا لا توجد تصنيفات → يُعيد فقط الـ "كل التصنيفات" option

**التوافق الخلفي:** `fieldOptions()` تبقى كما هي للحقول بدون `options_source` — لا كسر لأي حقل آخر.

---

## Step 2 — الـ field في SectionDefinition

**الملف:** `database/migrations/2026_06_22_000001_add_plan_category_filter_field_to_pricing_plans_dynamic.php`

| الخاصية | القيمة |
|---------|--------|
| `field_key` | `plan_category_id` |
| `label` | تصنيف الخطط |
| `field_type` | `select` |
| `field_scope` | `shared` |
| `group_name` | `design` |
| `is_required` | `false` |
| `options` | `null` — لا static JSON |
| `settings` | `{"options_source": "plan_categories"}` |
| `help_text` | اختر تصنيفاً لتصفية الخطط المعروضة. اتركه فارغاً لعرض جميع الخطط النشطة. |
| `sort_order` | آخر حقل + 10 (يُحسب ديناميكياً) |

**ملاحظة:** المـigration آمن للتكرار — يتحقق من عدم وجود `plan_category_id` قبل الإدراج. إذا لم يُعثر على `pricing_plans_dynamic` في DB يتخطى بصمت.

### تشغيل المـigration:
```bash
php artisan migrate
# ثم:
php artisan db:seed --class=SiteTranslationsSeeder
php artisan cache:clear
```

---

## Step 3 — تعديل الاستعلام في pricing_plans_dynamic.blade.php

### قبل:
```php
$allPlans = \App\Models\Plan::with(['translations'])
    ->active()
    ->orderBy('monthly_price_cents', 'asc')
    ->get();
```

### بعد:
```php
// ── Category filter (optional, shared) ───────────────────────────────
// Empty / missing → no filter → all active plans are shown.
$plan_category_id = is_numeric($data['plan_category_id'] ?? null)
    ? (int) $data['plan_category_id']
    : null;

$allPlans = \App\Models\Plan::with(['translations'])
    ->active()
    ->when($plan_category_id, fn ($q) => $q->where('plan_category_id', $plan_category_id))
    ->orderBy('monthly_price_cents', 'asc')
    ->get();
```

**التوافق الخلفي:**
- `$data['plan_category_id']` = `null` أو `''` أو غير موجود → `$plan_category_id = null`
- `->when(null, ...)` = لا يُضيف أي شرط → **نفس سلوك الكود القديم** (جميع الخطط النشطة)
- السكشنات الموجودة التي لم تُحدَّد لها قيمة تعمل كما هي

---

## Step 4 — الترجمات

### SiteTranslationsSeeder (ar):
```php
'site.All_Categories' => 'كل التصنيفات',
```

### DashboardTranslationsSeeder:
`dashboard.Plan_Category` موجود مسبقاً بقيمة `'التصنيف'` (السطر 98) — لم تُضَف ترجمة جديدة.

---

## كيف يعمل النظام كاملاً

```
١. الأدمن يفتح Page Builder → يختار سكشن pricing_plans_dynamic
٢. يظهر في تبويب التنسيق (design group): حقل "تصنيف الخطط"
٣. DynamicSectionEditorRenderer يكتشف settings.options_source = 'plan_categories'
٤. يستدعي planCategoryOptions() → يقرأ PlanCategory::active()->ordered()
٥. الأدمن يختار تصنيفاً (أو يترك "كل التصنيفات")
٦. القيمة تُحفظ في section_translations.value['plan_category_id'] = '2'
٧. عند رندر الصفحة: pricing_plans_dynamic يقرأ $data['plan_category_id']
٨. ->when($plan_category_id, ...) يُضيف WHERE plan_category_id = 2
٩. تظهر فقط الخطط التابعة للتصنيف المختار
```

---

## نتائج Validation

```
✅ DynamicSectionEditorRenderer: use App\Models\PlanCategory مُضاف
✅ fieldOptions(): يتحقق من settings.options_source قبل الـ static JSON
✅ planCategoryOptions(): تُعيد [] فأكثر (دائماً الـ "كل التصنيفات" مُضمَّن)
✅ Migration: idempotent — آمن على re-run
✅ Migration: يتحقق من وجود pricing_plans_dynamic أولاً
✅ Blade: $plan_category_id = null عند قيمة فارغة/غائبة
✅ Blade: ->when(null, ...) = لا فلتر → backward compatible
✅ Blade: قراءة plan_category_id قبل استعلام الخطط
✅ site.All_Categories مُضافة للـ SiteTranslationsSeeder
✅ dashboard.Plan_Category موجودة مسبقاً (لم تُعاد)
✅ لا تغيير في: Plan model, PlanCategory model, migrations أخرى
✅ لا تغيير في: partials (_plan_header, _plan_features, _plan_cta, _plan_card_dynamic)
✅ لا تغيير في: discount logic, billing toggle, featured card
✅ DashboardTranslationsSeeder: لم يُعدَّل (Plan_Category موجود)
```

---

## أوامر التشغيل بعد الـ Deploy

```bash
php artisan migrate
php artisan db:seed --class=SiteTranslationsSeeder
php artisan cache:clear
```

---

## الـ options_source Architecture

هذا النمط قابل للتوسع بسهولة — لإضافة source جديد:

```php
return match ($settings['options_source']) {
    'plan_categories'    => $this->planCategoryOptions(),
    'server_packages'    => $this->serverPackageOptions(),    // مستقبلاً
    'template_categories'=> $this->templateCategoryOptions(), // مستقبلاً
    default              => [],
};
```

كل source جديد = دالة protected واحدة في `DynamicSectionEditorRenderer`.
