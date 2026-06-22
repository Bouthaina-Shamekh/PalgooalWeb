# ADMIN_BRAND_THEME_PHASE_2_REPORT.md

**التاريخ:** 2026-06-23  
**الفرع:** Phase 2 — DesignTokenRegistry Custom Colors Integration  
**يعتمد على:** Phase 0 (alias layer) + Phase 1 (AdminBrandThemeSettings + AdminBrandCssGenerator)

---

## الهدف

ربط ألوان `custom_1..5` المحفوظة في `admin_brand_settings` مع Page Builder، بحيث تظهر كخيارات في حقلَي `background_token` و `text_token` عند تحرير أقسام الصفحات.

---

## ما الذي لم يتغير

| المكوّن | السبب |
|---------|-------|
| `admin_brand_settings` schema | جاهز من Phase 1 |
| `AdminBrandThemeSettings` | جاهز من Phase 1 |
| Appearance Admin UI | جاهز من Phase 1 |
| بيانات الصفحات المحفوظة (`Section` content) | **لم تُلمَس أبداً** |
| الأقسام القديمة (Blade views) | **لم تُلمَس أبداً** |

---

## Step 1 — CSS Utility Classes

**الملف:** `app/Support/AdminBrand/AdminBrandCssGenerator.php`

### التغيير

تمت إضافة قسم ثانٍ في `buildCss()` يُصدر 10 utility classes ثابتة في نهاية الملف المُولَّد:

```css
/* ── Custom color utility classes (Phase 2 — DesignTokenRegistry) ── */
.bg-admin-custom-1   { background-color: var(--admin-color-custom-1) !important; }
.bg-admin-custom-2   { background-color: var(--admin-color-custom-2) !important; }
.bg-admin-custom-3   { background-color: var(--admin-color-custom-3) !important; }
.bg-admin-custom-4   { background-color: var(--admin-color-custom-4) !important; }
.bg-admin-custom-5   { background-color: var(--admin-color-custom-5) !important; }

.text-admin-custom-1 { color: var(--admin-color-custom-1) !important; }
.text-admin-custom-2 { color: var(--admin-color-custom-2) !important; }
.text-admin-custom-3 { color: var(--admin-color-custom-3) !important; }
.text-admin-custom-4 { color: var(--admin-color-custom-4) !important; }
.text-admin-custom-5 { color: var(--admin-color-custom-5) !important; }
```

### لماذا تُصدَر دائماً (ليس فقط عند وجود قيمة)

- الملف Blade قد يستخدم `bg-admin-custom-2` قبل أن يضع المشرف لوناً
- الكلاس يُشير إلى `var()` — إذا المتغير غير معرَّف → `transparent` (بدون أثر مرئي)
- هذا يتيح نشر Blade views أولاً ثم إضافة الألوان لاحقاً بدون كسر

### تغيير آخر في buildCss()

قسم CSS custom properties لم يتغير في منطقه — `--admin-color-custom-N` لا يُصدر إلا إذا كانت القيمة غير فارغة (هذا محافظ من Phase 1).

---

## Step 2 — DesignTokenRegistry

**الملف:** `app/Support/Sections/DesignTokenRegistry.php`

### background_token — الإضافات

في `options[]`:
```php
['value' => 'custom_1',  'label' => 'Custom Color 1'],
['value' => 'custom_2',  'label' => 'Custom Color 2'],
['value' => 'custom_3',  'label' => 'Custom Color 3'],
['value' => 'custom_4',  'label' => 'Custom Color 4'],
['value' => 'custom_5',  'label' => 'Custom Color 5'],
```

في `css_map[]`:
```php
'custom_1'  => 'bg-admin-custom-1',
'custom_2'  => 'bg-admin-custom-2',
'custom_3'  => 'bg-admin-custom-3',
'custom_4'  => 'bg-admin-custom-4',
'custom_5'  => 'bg-admin-custom-5',
```

### text_token — الإضافات

في `options[]`:
```php
['value' => 'custom_1',  'label' => 'Custom Color 1'],
['value' => 'custom_2',  'label' => 'Custom Color 2'],
['value' => 'custom_3',  'label' => 'Custom Color 3'],
['value' => 'custom_4',  'label' => 'Custom Color 4'],
['value' => 'custom_5',  'label' => 'Custom Color 5'],
```

في `css_map[]`:
```php
'custom_1'  => 'text-admin-custom-1',
'custom_2'  => 'text-admin-custom-2',
'custom_3'  => 'text-admin-custom-3',
'custom_4'  => 'text-admin-custom-4',
'custom_5'  => 'text-admin-custom-5',
```

### تأثير التغيير

| الدالة | النتيجة بعد التغيير |
|--------|---------------------|
| `DesignTokenRegistry::options('background_token')` | تُعيد 10 خيارات (5 أصلية + 5 custom) |
| `DesignTokenRegistry::validValues('background_token')` | تتضمن `'custom_1'`..`'custom_5'` |
| `DesignTokenRegistry::resolveClass('background_token', 'custom_2')` | يُعيد `'bg-admin-custom-2'` |
| `DesignTokenRegistry::options('text_token')` | تُعيد 10 خيارات (5 أصلية + 5 custom) |
| `DesignTokenRegistry::resolveClass('text_token', 'custom_3')` | يُعيد `'text-admin-custom-3'` |

---

## Step 3 — Backfill Existing Fields

### المشكلة

`SectionDefinitionField.options` (JSON) يُخزَّن في DB وقت إنشاء الحقل عبر `DesignTokenPresetService::apply()`. الحقول الموجودة قبل Phase 2 لديها `options` قديم بدون `custom_1..5`.

### الحل — Artisan Command (idempotent)

```bash
php artisan admin-brand:sync-token-options
php artisan admin-brand:sync-token-options --dry-run  # معاينة بدون كتابة
```

**الملف:** `routes/console.php`

### المنطق

```
foreach ['background_token', 'text_token']:
    canonicalOptions = DesignTokenRegistry::options($tokenKey)
    
    foreach SectionDefinitionField.field_key = $tokenKey:
        currentValues  = pluck('value') من options الحالية
        canonicalValues = pluck('value') من Registry
        
        if currentValues === canonicalValues:
            skip (already up-to-date)
        else:
            UPDATE section_definition_fields SET options = $canonicalOptions
```

### ضمانات السلامة

| الضمان | التفصيل |
|--------|---------|
| **لا يمسّ بيانات الصفحات** | يُحدِّث `section_definition_fields` فقط — الـ `Section` content في جدول منفصل |
| **Idempotent** | الصفوف التي `options` تساوي Registry تُتخطى |
| **لا migration** | أمر Artisan عادي — يعمل بدون downtime |
| **لا schema change** | العمود `options` موجود — فقط JSON content يتغير |

---

## Flow الكامل في Page Builder

```
المشرف يفتح Page Builder → Section Editor (تبويب التنسيق)
        ↓
DynamicSectionEditorRenderer يقرأ SectionDefinitionField.options
        ↓
select.blade.php يعرض القائمة المنسدلة مع خيارات custom_1..5
        ↓
المشرف يختار "Custom Color 2"
        ↓
يُحفظ قيمة 'custom_2' في Section content
        ↓
عند الرندر:
DesignTokenRegistry::resolveClass('background_token', 'custom_2') → 'bg-admin-custom-2'
        ↓
Blade يُطبّق class 'bg-admin-custom-2' على الـ section
        ↓
admin-brand.css:
.bg-admin-custom-2 { background-color: var(--admin-color-custom-2) !important; }
        ↓
:root { --admin-color-custom-2: #ff6600; }  ← من AdminBrandCssGenerator
        ↓
الـ section يظهر بخلفية برتقالية #ff6600 ✓
```

---

## ماذا يحدث إذا لم يُعيَّن لون مخصص؟

```
admin_brand_settings.custom_2 = ""  →  لا --admin-color-custom-2 في CSS
.bg-admin-custom-2 { background-color: var(--admin-color-custom-2) !important; }
                                             ↑ undefined variable
                                         → browser uses initial value (transparent)
                                         → no visible background = safe no-op
```

المشرف يمكنه إضافة لون لاحقاً من Brand Colors → يُعيد الحفظ → CSS يُعاد توليده فوراً.

---

## الملفات المُعدَّلة

| الملف | التعديل |
|-------|---------|
| `app/Support/AdminBrand/AdminBrandCssGenerator.php` | إضافة 10 utility classes في `buildCss()` |
| `app/Support/Sections/DesignTokenRegistry.php` | إضافة custom_1..5 في options + css_map لكلا التوكنَين |
| `routes/console.php` | إضافة أمر `admin-brand:sync-token-options` |
| `resources/views/dashboard/appearance/brand.blade.php` | تحديث نص تلميح custom colors |

---

## أوامر التشغيل (على جهاز المستخدم)

```bash
# 1. تحقق أن Phase 1 شغّلت migrate + storage:link + seeder مسبقاً

# 2. شغّل الـ backfill لتحديث options في الحقول الموجودة
php artisan admin-brand:sync-token-options --dry-run  # معاينة أولاً
php artisan admin-brand:sync-token-options            # تطبيق

# 3. مسح الكاش
php artisan cache:clear

# 4. اذهب لـ Admin UI → الإعدادات → المظهر → ألوان البراند
#    ضع لوناً في Custom Color 1 واحفظ
#    → admin-brand.css سيُعاد توليده مع .bg-admin-custom-1 وقيمة الـ --admin-color-custom-1

# 5. في Page Builder → Section Editor → تبويب التنسيق
#    → background_token dropdown سيعرض "Custom Color 1" كخيار جديد
```

---

## ملخص تقني

| البند | القيمة |
|-------|--------|
| CSS classes المضافة | 10 (`.bg-admin-custom-1..5` + `.text-admin-custom-1..5`) |
| Token options المضافة | 5 لـ background_token + 5 لـ text_token |
| Artisan command | `admin-brand:sync-token-options` (idempotent, --dry-run support) |
| Schema changes | صفر |
| Page data changes | صفر |
| Blade files edited | 1 (brand.blade.php — تحديث نص تلميح فقط) |
| Backward compat | كامل — custom class بدون CSS var → transparent (no-op) |
