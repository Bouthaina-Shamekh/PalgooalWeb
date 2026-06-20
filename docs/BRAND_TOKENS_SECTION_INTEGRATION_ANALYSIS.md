# Brand Tokens ↔ Section Design Fields — Integration Analysis

> Date: 2026-06-20  
> Status: Analysis only — no code changes  
> Purpose: Map the Brand Settings architecture before adding the first real Design field to Sections

---

## 1. Current Brand Settings Architecture

### حيث تُخزَّن الألوان

القيم مخزّنة في **`subscriptions.theme_settings`** — عمود JSON يُكاست تلقائياً إلى `array` في الـ Model.

```
subscriptions
  └── theme_settings (JSON column)
        ├── color_primary
        ├── color_secondary
        ├── color_surface
        ├── color_muted
        ├── color_heading
        ├── color_body
        ├── color_border
        ├── font_primary, font_heading, base_font_size, weight_normal, weight_bold
        ├── radius_sm, radius_md, radius_lg, radius_xl
        ├── button_radius, button_style
        └── button_bg_color, button_text_color, button_hover_bg_color, button_hover_text_color
```

### قراءة وكتابة القيم

```
Brand Settings Drawer (UI)
  → POST brandSettingsUpdateUrl
  → SubscriptionThemeController::update()
  → TenantThemeSettings::fromArray($validated)
  → Subscription::update(['theme_settings' => $settings->toArray()])
  → TenantThemeCssGenerator::generate($subscription)   ← يُولِّد ملف CSS فوراً
```

### Value Object

`App\Support\Tenancy\TenantThemeSettings` — value object لا يحتوي على DB queries أو side effects. يتحقق من صحة القيم (sanitizeHex / sanitizeFont / sanitizeCssSize) قبل التخزين.

---

## 2. Existing Color Tokens

| Token Key (DB) | CSS Variable | Utility Class (bg) | Utility Class (text) | Default Value |
|----------------|-------------|-------------------|---------------------|---------------|
| `color_primary` | `--theme-color-primary` | `.bg-theme-primary` | `.text-theme-primary` | `#7c3aed` (violet-600) |
| `color_secondary` | `--theme-color-secondary` | `.bg-theme-secondary` | `.text-theme-secondary` | `#e11d48` (rose-600) |
| `color_surface` | `--theme-color-surface` | `.bg-theme-surface` | — | `#ffffff` |
| `color_muted` | `--theme-color-muted` | `.bg-theme-muted` | `.text-theme-muted` | `#f8fafc` (slate-50) |
| `color_heading` | `--theme-color-heading` | — | `.text-theme-heading` | `#0f172a` (slate-900) |
| `color_body` | `--theme-color-body` | — | `.text-theme-body` | `#475569` (slate-600) |
| `color_border` | `--theme-color-border` | `.border-theme-border` | — | `#e2e8f0` (slate-200) |

### Tokens غير اللونية:
- `--theme-font-primary/heading` → `.font-theme-body / .font-theme-heading`
- `--theme-radius-sm/md/lg/xl` → `.rounded-theme-sm/md/lg/xl`
- `--theme-button-bg/text/hover-bg/hover-text` → `.btn-theme-primary / .btn-theme-secondary`

---

## 3. How Tokens Reach the Frontend

### الطريق الكامل:

```
1. DB: subscriptions.theme_settings (JSON)
           ↓
2. PHP: TenantThemeCssGenerator::generate()
           ↓
3. File: storage/app/public/tenant-themes/{subscription_id}.css
   يحتوي على:
     :root { --theme-color-primary: #7c3aed; --theme-color-surface: #fff; ... }
     .bg-theme-primary   { background-color: var(--theme-color-primary) !important; }
     .bg-theme-surface   { background-color: var(--theme-color-surface) !important; }
     .bg-theme-muted     { background-color: var(--theme-color-muted)   !important; }
           ↓
4. HTML: resources/views/front/layouts/partials/head.blade.php
   يُحمِّل: <link rel="stylesheet" href="/storage/tenant-themes/42.css">
           ↓
5. Section Blade: class="bg-theme-surface font-theme-body ..."
```

### طبقات CSS (بالترتيب):

| الطبقة | الملف | محتوى Token |
|--------|-------|-------------|
| Default fallbacks | `resources/css/app.css` → `@layer base :root` | قيم افتراضية للـ `--theme-color-*` — fallback في حال عدم وجود ملف tenant |
| Tailwind theme vars | `tailwind.config.js` → `colors.primary` | تُشير لـ `var(--color-primary)` — ليست tokens المشروع، مجرد legacy |
| Per-tenant CSS | `storage/app/public/tenant-themes/{id}.css` | **القيم الحقيقية** مُعرَّفة هنا فوق `:root` + utility classes |

---

## 4. Does the Page Builder Preview Use the Same Tokens?

**نعم — بشكل كامل.**

الـ preview في Page Builder هو `<iframe>` يُحمِّل URL حقيقياً للـ frontend:

```html
<iframe id="sections-preview-frame"
        src="{{ $previewUrl }}"   <!-- URL فعلي للصفحة -->
        class="sections-preview-frame">
```

هذا يعني أن الـ iframe يُحمِّل:
- نفس `head.blade.php` الذي يُدرج `<link rel="stylesheet" href="/storage/tenant-themes/{id}.css">`
- نفس `app.css` مع الـ defaults
- نفس `--theme-color-*` variables

**النتيجة**: أي تغيير على `background_token` يتحول لـ `.bg-theme-muted` يظهر فوراً في الـ preview بعد إعادة التحميل.

---

## 5. هل توجد Tailwind Classes مثل `bg-primary` أو `bg-brand-primary`؟

| Class | الحالة |
|-------|--------|
| `bg-primary` | ✅ موجودة — لكنها تشير لـ `var(--color-primary)` وهو مختلف عن `--theme-color-primary` |
| `bg-theme-primary` | ✅ موجودة — **ليست Tailwind** بل utility class في ملف tenant CSS |
| `bg-brand-primary` | ❌ غير موجودة |
| `bg-surface` | ❌ غير موجودة في Tailwind |
| `bg-theme-surface` | ✅ موجودة في tenant CSS |
| `text-heading` | ❌ غير موجودة |
| `text-theme-heading` | ✅ موجودة في tenant CSS |

**خلاصة مهمة**: الـ Classes المفيدة هي `.bg-theme-*` المولَّدة في ملف الـ tenant CSS — وليست Tailwind classes.

---

## 6. Existing Section Usage — Proof

السكشنات الموجودة تستخدم فعلاً هذه الـ classes:

```blade
{{-- hero_campaign.blade.php --}}
<section class="bg-theme-surface font-theme-body ...">
<span class="bg-theme-muted text-theme-primary ...">
<h2 class="text-theme-heading font-theme-heading ...">
<p class="text-theme-body ...">
```

ملفات تستخدمها: `hero_campaign`, `service_showcase`, `service_masonry_gallery`, `service_gallery_showcase`, `process_steps`, `promo_image_features`, `reviews_slider`, `tech_stack_logos`, `portfolio_slider`, `templates_showcase` — أي **10 ملفات** على الأقل.

---

## 7. Recommended Section Field Name

**الاسم المقترح: `background_token`**

| الاسم | السبب |
|-------|-------|
| `background_token` ✅ | يعكس بدقة أن القيمة هي "token معرَّف في Brand Settings" وليس لوناً حراً |
| `background_variant` | أكثر غموضاً — قد يشير لـ layout variants |
| `surface_token` | ضيق جداً — لا يشمل primary / transparent |
| `background_color` | ❌ مربك — يُوحي بقيمة hex حرة |

### قيم `background_token` المقترحة:

```
primary     → .bg-theme-primary
secondary   → .bg-theme-secondary
surface     → .bg-theme-surface
muted       → .bg-theme-muted
transparent → (لا class)
```

---

## 8. Recommended Implementation Approach

### أ. Blade Logic (بدون inline style)

```blade
@php
    $bgToken = trim((string) ($data['background_token'] ?? ''));
    $bgClass = match($bgToken) {
        'primary'   => 'bg-theme-primary',
        'secondary' => 'bg-theme-secondary',
        'surface'   => 'bg-theme-surface',
        'muted'     => 'bg-theme-muted',
        default     => '',   // transparent — لا خلفية
    };
@endphp

<section class="{{ $bgClass }} py-16 px-4">
    ...
</section>
```

لا `style=` — لا قيم hex — فقط class معياري.

### ب. Field Definition (في SectionPackageGenerator أو يدوياً)

```php
[
    'field_key'    => 'background_token',
    'label'        => 'Background',
    'field_type'   => 'select',
    'field_scope'  => 'shared',     // نفس الخلفية لجميع اللغات
    'is_required'  => false,
    'options'      => json_encode([
        ['value' => '',            'label' => 'Transparent (none)'],
        ['value' => 'surface',     'label' => 'Surface (white/card)'],
        ['value' => 'muted',       'label' => 'Muted (light gray)'],
        ['value' => 'primary',     'label' => 'Primary brand color'],
        ['value' => 'secondary',   'label' => 'Secondary brand color'],
    ]),
    'sort_order'   => 99,
]
```

### ج. SectionFieldClassifier

أضف `background_token` لـ `DESIGN_FIELD_KEYS` حتى يظهر في تبويب "التنسيق":

```php
public const DESIGN_FIELD_KEYS = [
    'align',
    'animation',
    'background_color',
    'background_image',
    'background_token',    // ← أضف هنا
    ...
];
```

---

## 9. Risks

### أ. Tailwind لا يولد classes ديناميكية

**الخطر**: إذا كتبنا في Blade: `class="bg-theme-{{ $bgToken }}"` فـ Tailwind لن يُولِّد هذه الـ class في البناء.

**الحل**: لا نستخدم interpolation في class names. نستخدم `match()` ثابت كما في مثال الـ Blade أعلاه. الـ classes مكتوبة بشكل كامل → Tailwind safe لأن `.bg-theme-*` ليست Tailwind أصلاً بل tenant CSS.

### ب. الـ Classes تحتاج ملف tenant CSS موجود

**الخطر**: في بيئة dev أو admin preview بدون subscription محدد، الـ CSS file قد لا يكون موجوداً.

**الحل**: `app.css` يوفر `@layer base :root` بقيم افتراضية — الـ variables موجودة دائماً حتى بدون tenant file.

### ج. اختلاف Token Names بين Dashboard و Frontend

**لا يوجد خطر** — نفس أسماء الـ tokens (مثل `surface` → `.bg-theme-surface`) تُولَّد من نفس `TenantThemeCssGenerator` وتُستهلك في نفس Blade files.

### د. مشاكل Multi-Tenant

**لا يوجد خطر** — كل اشتراك له ملف CSS خاص به (`tenant-themes/{id}.css`). الـ CSS variables تُعرَّف لكل tenant على حدة في `:root`.

### هـ. Preview iframe و CORS

**لا يوجد خطر** — الـ preview هو iframe لنفس الـ domain (وليس cross-origin). يُحمِّل الـ tenant CSS بشكل طبيعي.

### و. `transparent` edge case

إذا اختار المستخدم "transparent" فلا class يُضاف → الـ section يرث خلفية الصفحة بشكل طبيعي. هذا السلوك صحيح ومقصود.

---

## 10. ملخص الاستنتاجات

| السؤال | الإجابة |
|--------|---------|
| أين تُحفظ الألوان؟ | `subscriptions.theme_settings` (JSON في DB) |
| DB أم config أم JSON؟ | DB — JSON column |
| كيف تصل للواجهة؟ | ملف CSS مُولَّد per-tenant + `<link>` في head |
| هل يُولَّد CSS variables؟ | **نعم** — `--theme-color-primary/secondary/surface/muted/heading/body/border` |
| هل توجد `bg-theme-*` classes؟ | **نعم** — مُولَّدة في tenant CSS، تستخدمها 10+ ملفات section |
| Page Builder preview — نفس tokens؟ | **نعم** — الـ preview هو iframe للصفحة الفعلية |
| هل Section Blade يمكنه استخدام tokens؟ | **نعم مباشرة** — `class="bg-theme-muted"` يعمل الآن |
| الاسم المقترح للحقل؟ | `background_token` (نوع: select، scope: shared) |
| طريقة التطبيق؟ | `match($bgToken) { 'muted' => 'bg-theme-muted', ... }` في Blade |

---

## المرحلة التالية المقترحة

1. أضف `background_token` لـ `SectionFieldClassifier::DESIGN_FIELD_KEYS`
2. عرِّف الحقل في `FieldPresetLibrary` كـ preset قابل لإعادة الاستخدام
3. أضفه لـ `ComponentLibrary` في component مناسب (مثل: component جديد `background`)
4. في أول Section يدعمه (مثل `hero_main`) أضف الـ `@php match()` في الـ Blade
5. اختبر في Preview — يجب أن يعمل فوراً بدون أي تغيير على CSS

لا يحتاج هذا إلى:
- أي تعديل على Tailwind config
- أي CSS جديد
- أي migration
- أي تعديل على `TenantThemeCssGenerator`
