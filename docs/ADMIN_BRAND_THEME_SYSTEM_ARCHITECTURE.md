# ADMIN_BRAND_THEME_SYSTEM_ARCHITECTURE.md

**التاريخ:** 2026-06-23  
**النطاق:** تحليل معماري — لا تنفيذ كود  
**الهدف:** تصميم نظام Admin Brand Theme قابل للتوسعة مع تكاملٍ كامل مع DesignTokenRegistry

---

## ١. الوضع الراهن (Current State)

### ١.١ الألوان الثابتة في الواجهة التسويقية

ملف `public/assets/tamplate/css/app.css` (المُولَّد من Tailwind build) يُعرّف في `:root`:

```css
--color-purple-brand: #240a37;
--color-red-brand:    #ba112c;
--color-gray-light:   #f2f2f2;
--color-gray-dark:    #626262;
--color-primary:      #240B36;
--color-secondary:    #AE1028;
--color-tertiary:     #5F4A72;
--color-background:   #F9F6FB;
```

هذه القيم **مضمّنة (hardcoded) في كود المصدر** — مرتبطة بـ `tailwind.config.js`:

```js
colors: {
  purplebrand: 'var(--color-purple-brand)',
  redbrand:    'var(--color-red-brand)',
  graylight:   'var(--color-gray-light)',
  graydark:    'var(--color-gray-dark)',
}
```

وتُستخدم في السكشنات بشكل مباشر:
```blade
text-purple-brand    bg-purple-brand    text-red-brand
text-[#555]          bg-[#EAEAEA]       text-[#626262]
```

**النتيجة:** تغيير البراند = تعديل كود مصدر + rebuild Tailwind.

### ١.٢ ما يُمكن تخصيصه حالياً (Header/Footer Custom Colors)

يوجد نظام جزئي يُغطي Header وFooter فقط، يعمل عبر:

**التخزين:** `general_settings.header_variant_settings` و `footer_variant_settings` (JSON columns)  
تحت المفاتيح: `['purple_topbar']['custom_colors']` و `['palgoals_marketing']['custom_colors']`

**التوليد:** `ThemeCssController` يُنتج CSS response ديناميكياً عند كل طلب:
- `route('frontend.assets.purple_topbar_css')` → CSS vars `--pv-topbar-*` (9 ألوان)
- `route('frontend.assets.palgoals_marketing_footer_css')` → CSS vars `--pf-marketing-*` (6 ألوان)

**التحميل:** في `front/layouts/partials/head.blade.php` — `<link>` مباشر بعد `app.css`

**القيد الجوهري:** لا علاقة بين هذه الألوان وبين الـ section Blades — السكشنات لا تستخدم `--pv-topbar-*` ولا `--pf-marketing-*`.

### ١.٣ ألوان لا تُغطيها أي آلية حالية

| الاستخدام | الملفات | الحل الحالي |
|-----------|---------|-------------|
| `text-purple-brand` في السكشنات | hero, faq, pricing, features... | hardcoded في app.css |
| `bg-purple-brand` | plan cards, hero | hardcoded في app.css |
| `text-red-brand` | أيقونات، CTA | hardcoded في app.css |
| `bg-[#EAEAEA]` | بطاقات الميزات | hardcoded inline |
| `text-[#555]` / `text-[#626262]` | نصوص فرعية | hardcoded inline |

---

## ٢. مكونات النظام القائم القابلة لإعادة الاستخدام (Reusable Components)

### ٢.١ Tenant Theme System — النموذج المُثبت

| المكوّن | المسار | الوظيفة |
|---------|--------|---------|
| `TenantThemeSettings` | `app/Support/Tenancy/TenantThemeSettings.php` | Value Object — يُحكّم صحة القيم ويُوفّر defaults |
| `TenantThemeCssGenerator` | `app/Services/Tenancy/TenantThemeCssGenerator.php` | يُنتج CSS file لكل Subscription على disk |
| `TenantThemeFontLoader` | `app/Support/Tenancy/TenantThemeFontLoader.php` | يُولّد Google Fonts URL |
| `DesignTokenRegistry` | `app/Support/Sections/DesignTokenRegistry.php` | مصدر الحقيقة لـ design tokens وتعيين CSS classes |
| `DesignTokenPresetService` | `app/Support/Sections/DesignTokenPresetService.php` | تطبيق مجموعات tokens دفعةً واحدة |
| `SectionFieldClassifier` | `app/Support/Sections/SectionFieldClassifier.php` | تصنيف الحقول: content vs. design |

### ٢.٢ الأنماط المُثبتة في TenantThemeCssGenerator

```
subscriptions.theme_settings (JSON)
    ↓ TenantThemeSettings::fromArray()
    ↓ TenantThemeCssGenerator::buildCss()
    ↓ Storage::disk('public')->put('tenant-themes/{id}.css')
    ↓ head.blade.php: TenantThemeCssGenerator::publicUrlFor() → <link>
```

النمط أنتج:
- CSS custom properties على `:root`
- Utility classes (`.bg-theme-*`, `.text-theme-*`, `.btn-theme-*`, إلخ)
- ربط Tailwind: `--color-theme-*: var(--theme-color-*)` في `app.css`

**هذا النمط قابل للمعالجة مباشرةً للـ Admin Brand Theme.**

### ٢.٣ ما يحتوي عليه DesignTokenRegistry حالياً

| Token | نوع الحقل | الـ CSS Classes المدعومة |
|-------|-----------|--------------------------|
| `background_token` | select | `bg-theme-primary/secondary/surface/muted` |
| `text_token` | select | `text-theme-heading/body/primary/secondary/white` |
| `image_position` | select | (layout order — بدون class واحد) |
| `section_spacing` | select | `py-0` / `py-8 md:py-12` / ... |
| `container_width` | select | `max-w-3xl/5xl/7xl/full` |

---

## ٣. خيارات المعمارية (Architecture Options)

### Option A — Admin Brand Theme مستقل
```
general_settings
  └── admin_brand_settings (JSON column جديد)
        ├── color_primary:   '#240a37'
        ├── color_secondary: '#ba112c'
        ├── color_surface:   '#ffffff'
        ├── color_muted:     '#f8f8f8'
        ├── color_heading:   '#111827'
        ├── color_body:      '#555555'
        ├── color_border:    '#e5e7eb'
        ├── custom_1..5:     null
        └── ... (typography, shape)

AdminBrandThemeSettings (Value Object)
AdminBrandCssGenerator → public/assets/tamplate/css/admin-brand.css
```

**مزاياه:** مستقل تماماً، سهل الفهم والصيانة، لا يُؤثر على Tenant Theme.  
**عيوبه:** بعض التكرار في الكود مع TenantThemeCssGenerator.

---

### Option B — إعادة استخدام Tenant Theme System مباشرةً

```
subscriptions (اشتراك وهمي "admin" ثابت)
    └── theme_settings → TenantThemeCssGenerator::generate()
```

**مزاياه:** لا كود جديد.  
**عيوبه:** خطأ مفاهيمي — Admin Brand ليس subscription. يخلط السياقات. مرفوض.

---

### Option C — ThemeContext موحّد

```
ThemeSettingsContract (interface)
    ├── TenantThemeSettings implements ThemeSettingsContract
    └── AdminBrandSettings implements ThemeSettingsContract

ThemeCssBuilderService::build(ThemeSettingsContract $settings, string $prefix): string
```

**مزاياه:** صيانة مركزية، قابلية توسع مستقبلية.  
**عيوبه:** تعقيد غير ضروري حالياً. المسار الصحيح بعد تثبيت Option A وتوسعه.

---

## ٤. التصميم الموصى به

### التوصية: Option A مع مسار واضح نحو Option C

**المبرر:**
- Option A يُنتج نظاماً عاملاً بأقل تعقيد
- الفصل بين Admin Brand وTenant Theme صحيح معمارياً (سياقات مختلفة: global vs. per-tenant)
- يُمكن لاحقاً استخراج interface مشترك (Option C) دون كسر أي كود

### البنية المقترحة

```
┌─────────────────────────────────────────────────────────────┐
│                    general_settings row                      │
│  admin_brand_settings: {                                     │
│    color_primary, color_secondary, color_surface,            │
│    color_muted, color_heading, color_body, color_border,     │
│    custom_1..5,                                              │
│    font_primary, base_font_size,                             │
│    radius_sm, radius_md, radius_lg, radius_xl                │
│  }                                                           │
└──────────────────────────┬──────────────────────────────────┘
                           ↓
           AdminBrandThemeSettings::fromArray()
           (Value Object — نفس نمط TenantThemeSettings)
                           ↓
           AdminBrandCssGenerator::generate()
           (نفس نمط TenantThemeCssGenerator)
                           ↓
    public/assets/tamplate/css/admin-brand.{version}.css
           (ملف ثابت — يُعاد توليده عند الحفظ فقط)
                           ↓
           head.blade.php: <link rel="stylesheet" href="...">
```

### CSS Variables المقترحة للـ Admin Brand

```css
/* Generated by AdminBrandCssGenerator */
:root {
  /* --- Brand Colors --- */
  --admin-color-primary:   #240a37;
  --admin-color-secondary: #ba112c;
  --admin-color-surface:   #ffffff;
  --admin-color-muted:     #f8f8f8;
  --admin-color-heading:   #111827;
  --admin-color-body:      #555555;
  --admin-color-border:    #e5e7eb;

  /* --- Custom Colors --- */
  --admin-custom-1: transparent;
  --admin-custom-2: transparent;
  --admin-custom-3: transparent;
  --admin-custom-4: transparent;
  --admin-custom-5: transparent;

  /* --- Typography --- */
  --admin-font-primary:   'Cairo', sans-serif;
  --admin-font-size-base: 16px;

  /* --- Shape --- */
  --admin-radius-sm: 0.25rem;
  --admin-radius-md: 0.5rem;
  --admin-radius-lg: 0.75rem;
  --admin-radius-xl: 1rem;
}

/* --- Background utilities --- */
.bg-admin-primary   { background-color: var(--admin-color-primary) !important; }
.bg-admin-secondary { background-color: var(--admin-color-secondary) !important; }
.bg-admin-surface   { background-color: var(--admin-color-surface) !important; }
.bg-admin-muted     { background-color: var(--admin-color-muted) !important; }
.bg-admin-custom-1  { background-color: var(--admin-custom-1) !important; }
/* ... custom-2 → custom-5 */

/* --- Text utilities --- */
.text-admin-primary   { color: var(--admin-color-primary) !important; }
.text-admin-secondary { color: var(--admin-color-secondary) !important; }
.text-admin-heading   { color: var(--admin-color-heading) !important; }
.text-admin-body      { color: var(--admin-color-body) !important; }
.text-admin-custom-1  { color: var(--admin-custom-1) !important; }
/* ... custom-2 → custom-5 */
```

---

## ٥. استراتيجية Custom Colors

### ٥.١ أين تُعرَّف؟

في `AdminBrandThemeSettings` كـ properties منفصلة مع default فارغ:

```php
public readonly string $custom1; // default: ''
public readonly string $custom2; // default: ''
// ...
```

القيمة الفارغة `''` تعني "لا لون مخصص" — تُولَّد كـ `transparent` في CSS.

### ٥.٢ هل تدخل ضمن CSS Variables؟

نعم. يجب أن تُنتج CSS variable حتى لو كانت فارغة:
```css
--admin-custom-1: transparent; /* عند عدم التعيين */
--admin-custom-1: #3b82f6;     /* عند التعيين */
```

**السبب:** Tailwind لا يستطيع إنتاج class لـ `var()` مجهول القيمة وقت البناء. الـ `.bg-admin-custom-1` class يحتاج CSS var معرّف دائماً.

### ٥.٣ كيف تدخل ضمن DesignTokenRegistry؟

إضافة options جديدة لـ `background_token` و `text_token`:

```php
// في background_token options:
['value' => 'custom_1', 'label' => 'Custom Color 1'],
['value' => 'custom_2', 'label' => 'Custom Color 2'],
// ...

// في background_token css_map:
'custom_1' => 'bg-admin-custom-1',
'custom_2' => 'bg-admin-custom-2',

// في text_token options:
['value' => 'custom_1', 'label' => 'Custom Color 1'],
// ...

// في text_token css_map:
'custom_1' => 'text-admin-custom-1',
```

### ٥.٤ كيف تستخدمها السكشنات؟

بدلاً من:
```blade
{{-- ❌ الحالة الراهنة --}}
<h2 class="text-purple-brand ...">
<div class="bg-[#EAEAEA] ...">
```

تُصبح:
```blade
{{-- ✅ بعد الترحيل --}}
@php $bgClass = DesignTokenRegistry::resolveClass('background_token', $background_token ?? null); @endphp
<section class="{{ $bgClass }} ...">

@php $textClass = DesignTokenRegistry::resolveClass('text_token', $text_token ?? null); @endphp
<h2 class="{{ $textClass }} ...">
```

السكشنات التي تحتاج دائماً لون البراند (مثل hero header) تستخدم مباشرةً:
```blade
<section class="bg-admin-primary ...">
```

---

## ٦. التكامل مع DesignTokenRegistry

### الوضع الحالي

`background_token` يُعيّن: `'primary' → 'bg-theme-primary'`  
أي: الألوان التي تعمل حالياً هي فقط **ألوان الـ Tenant Theme** (per-subscription).

### المشكلة

الواجهة التسويقية الرئيسية لا تعمل في سياق subscription عادةً — إما تكون الصفحة التسويقية للمنصة نفسها، أو أن الـ tenant CSS لم يُحمَّل بعد.

### الحل المقترح: CSS Variables Layer

في `app.css` (مصدر Tailwind)، أضف alias layer:
```css
:root {
  /* Admin brand aliases — تُحيل للـ admin-brand.css عند تحميله */
  --color-purple-brand: var(--admin-color-primary,   #240a37);
  --color-red-brand:    var(--admin-color-secondary, #ba112c);
  --color-gray-light:   var(--admin-color-muted,     #f2f2f2);
  --color-gray-dark:    var(--admin-color-body,      #626262);
}
```

**الميزة:** بدون تغيير أي blade — الـ `text-purple-brand` يُصبح يشير لـ `var(--admin-color-primary)` تلقائياً. إذا لم يُحمَّل `admin-brand.css` بعد، تعمل القيم الافتراضية.

### توسيع DesignTokenRegistry للـ Custom Colors

```php
// في ALL_TOKENS['background_token']['options']:
['value' => 'custom_1', 'label' => 'Custom 1'],
['value' => 'custom_2', 'label' => 'Custom 2'],
['value' => 'custom_3', 'label' => 'Custom 3'],
['value' => 'custom_4', 'label' => 'Custom 4'],
['value' => 'custom_5', 'label' => 'Custom 5'],

// في ALL_TOKENS['background_token']['css_map']:
'custom_1' => 'bg-admin-custom-1',
'custom_2' => 'bg-admin-custom-2',
'custom_3' => 'bg-admin-custom-3',
'custom_4' => 'bg-admin-custom-4',
'custom_5' => 'bg-admin-custom-5',
```

**لا نحتاج Registry جديد.** نفس الـ Registry يُوسَّع فقط.

---

## ٧. استراتيجية الترحيل (Migration Strategy)

### المرحلة صفر — بدون كسر (No-break baseline)

الـ alias layer في `app.css` (القسم ٦ أعلاه) يضمن:
- `--color-purple-brand` يشير دائماً لـ `var(--admin-color-primary, #240a37)`
- الفالباك هو القيمة الحالية — لا شيء ينكسر قبل تحميل admin-brand.css

### المرحلة الأولى — البنية التحتية

1. إضافة `admin_brand_settings` JSON column لـ `general_settings`
2. إنشاء `AdminBrandThemeSettings` (Value Object)
3. إنشاء `AdminBrandCssGenerator` (يُنتج إلى `public/assets/tamplate/css/admin-brand.css`)
4. تحميل الملف في `head.blade.php` بعد `app.css`
5. إضافة UI في لوحة الأدمن: Appearance → Brand Colors

### المرحلة الثانية — ترحيل السكشنات (تدريجي)

ترحيل السكشنات من hardcoded colors إلى:
- `bg-admin-primary` بدلاً من `bg-purple-brand`
- `text-admin-secondary` بدلاً من `text-red-brand`
- `text-admin-body` بدلاً من `text-[#555]`

**الترتيب:** السكشنات الأكثر استخداماً أولاً (hero → pricing → features → faq).

### المرحلة الثالثة — Custom Colors في Page Builder

إضافة `custom_1..5` options لـ `background_token` و `text_token` في `DesignTokenRegistry`.

### المرحلة الرابعة — إزالة الـ Hardcoding المتبقي

استبدال `text-[#...]` و `bg-[#...]` Tailwind arbitrary values بـ:
- `text-admin-body` أو `text-admin-muted`
- `bg-admin-surface` أو `bg-admin-muted`

---

## ٨. المخاطر

| الخطر | المستوى | التخفيف |
|-------|---------|---------|
| **Tailwind JIT** — الـ `bg-admin-custom-1` class غير مُستخدم في Blade files قبل الترحيل → لن يُولَّد | عالٍ | الـ classes تُعرَّف في `admin-brand.css` كـ utility يدوية (خارج JIT) وليس في Tailwind config |
| **التداخل مع Tenant CSS** — `.bg-admin-primary` و `.bg-theme-primary` قد يتعارضان | متوسط | الـ prefix مختلف: `admin-*` vs `theme-*`. لا تعارض. |
| **Cache بدون invalidation** — تغيير اللون لكن الـ browser يُخزّن الـ CSS القديم | متوسط | اسم الملف يتضمن timestamp أو hash عند التوليد: `admin-brand.{hash}.css` |
| **ترحيل السكشنات** — عشرات الملفات تحتاج تعديل | منخفض-متوسط | الـ alias layer يعمل بدون ترحيل فوري. الترحيل تدريجي. |
| **ThemeCssController مع `no-store`** — طلب PHP لكل زائر | لا ينطبق | `AdminBrandCssGenerator` يُنتج ملفاً ثابتاً — لا PHP request |

---

## ٩. التوسع المستقبلي

### توسيع CSS Variables

يُمكن إضافة أي token جديد:
```php
// في AdminBrandThemeSettings:
public readonly string $colorAccent;    // لون تمييز إضافي
public readonly string $colorInfo;      // أزرق إعلاني

// في AdminBrandCssGenerator:
"  --admin-color-accent: {$t->colorAccent};"
```

### Option C — ThemeContext موحّد (مستقبلاً)

```php
interface ThemeSettingsContract {
    public function toCssVariables(string $prefix): string;
    public function toUtilityClasses(string $prefix): string;
}

class AdminBrandThemeSettings implements ThemeSettingsContract { ... }
class TenantThemeSettings implements ThemeSettingsContract { ... }

class UnifiedThemeCssBuilder {
    public function build(ThemeSettingsContract $settings, string $prefix, string $outputPath): void { ... }
}
```

### Dark Mode Support

CSS variables تتيح Dark Mode دون CSS duplication:
```css
@media (prefers-color-scheme: dark) {
  :root {
    --admin-color-surface: #1e293b;
    --admin-color-body:    #94a3b8;
  }
}
```

---

## ملخص التوصية النهائي

| السؤال | الإجابة |
|---------|---------|
| أين تُخزَّن ألوان الأدمن؟ | `general_settings.admin_brand_settings` (JSON column جديد) |
| هل يُعاد استخدام Tenant Theme System؟ | كنموذج/نمط فقط — كود منفصل |
| هل نحتاج جدول إعدادات جديد؟ | لا — `general_settings` كافٍ (single-row settings table) |
| كيف يُحمَّل CSS الجديد؟ | ملف ثابت مُولَّد → `<link>` في `head.blade.php` |
| هل نحتاج Registry جديد للـ custom colors؟ | لا — توسيع `DesignTokenRegistry` كافٍ |
| ماذا يتغير في السكشنات فوراً؟ | لا شيء — alias layer يضمن backward compatibility |
| ما الـ prefix للـ CSS Variables الجديدة؟ | `--admin-*` (مقابل `--theme-*` للـ Tenant) |
| هل هناك كود ينكسر أثناء التطبيق؟ | لا — بفضل alias layer والـ CSS custom property fallback |
