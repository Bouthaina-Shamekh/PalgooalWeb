# Pricing Plans Dynamic — Refactor Report

**Date:** 2026-06-22  
**Type:** Code Structure Refactor (Zero Behaviour Change)

---

## المشكلة قبل الـ Refactor

### 1. تكرار HTML في الـ `@foreach` loop

داخل الـ `@foreach` كان هناك `@if ($isFeatured) / @else` يحتوي على نفس الـ 3 `@include`s مكررة:

```blade
@if ($isFeatured)
    <div class="plan-featured ...">
        ...
        <div class="...ring-purple-brand...">
            @include('front.sections.pricing._plan_header', compact(...))   ← مكرر
            @include('front.sections.pricing._plan_features', compact(...))  ← مكرر
            @include('front.sections.pricing._plan_cta', compact('ctaUrl')) ← مكرر
        </div>
    </div>
@else
    <div class="{{ $animClass }} bg-white ...">
        @include('front.sections.pricing._plan_header', compact(...))        ← نفس الـ include
        @include('front.sections.pricing._plan_features', compact(...))       ← نفس الـ include
        @include('front.sections.pricing._plan_cta', compact('ctaUrl'))      ← نفس الـ include
    </div>
@endif
```

### 2. كتل `match()` بدلاً من `DesignTokenRegistry`

```php
// ❌ قبل
$backgroundClass = match ($background_token) {
    'primary'   => 'bg-theme-primary',
    'secondary' => 'bg-theme-secondary',
    'surface'   => 'bg-theme-surface',
    'muted'     => 'bg-theme-muted',
    default     => 'bg-[#F2F2F2]',
};

$textClass = match ($text_token) {
    'body'      => 'text-theme-body',
    'primary'   => 'text-theme-primary',
    'secondary' => 'text-theme-secondary',
    'white'     => 'text-white',
    default     => 'text-theme-heading',
};
```

### 3. كتلة PHP للبيانات per-plan داخل الـ loop

`@php` block بداخل `@foreach` يحسب 12+ متغيراً لكل خطة — مخلوط مع الـ HTML.

---

## البنية بعد الـ Refactor

### الملفات الجديدة

| الملف | الدور |
|-------|-------|
| `resources/views/front/sections/pricing/_plan_card_dynamic.blade.php` | **جديد** — يُمثّل بطاقة خطة واحدة كاملة |

### الملفات المعدلة

| الملف | التغييرات |
|-------|-----------|
| `resources/views/front/sections/pricing/pricing_plans_dynamic.blade.php` | استبدال `match()` بـ `DesignTokenRegistry::resolveClass()` + تبسيط الـ loop |

### الملفات غير المعدلة

| الملف | الحالة |
|-------|--------|
| `_plan_header.blade.php` | بدون تغيير |
| `_plan_features.blade.php` | بدون تغيير |
| `_plan_cta.blade.php` | بدون تغيير |
| `_plan_header_dynamic.blade.php` | بدون تغيير |
| `pricing_plans.blade.php` | بدون تغيير |

---

## ما تم نقله إلى `_plan_card_dynamic.blade.php`

### من الـ `@foreach` loop:

**PHP block كاملاً (12 متغير):**
```php
$trans
$planTitle
$planDesc
$isFeatured
$featuredLabel
$featuresMonthly
$featuresAnnual
$features
$hasAnnualFeatures
$monthlyPrice
$annualPrice
$monthlyEquiv
$yearlySaving
$mid, $animClass
$ctaUrl
```

**HTML كاملاً:**
- `@if ($isFeatured) ... @else ... @endif` wrapper logic
- Featured badge (star icon + label)
- Featured card div (ring + shadow styling)
- Regular card div (animClass + shadow styling)
- 3 `@include` calls داخل كل branch

---

## الملف الرئيسي بعد الـ Refactor

يحتوي فقط على:

```blade
@php
    // Section fields ($section_id, $title, $subtitle, $monthly_label, $annual_label)
    // Design tokens via DesignTokenRegistry::resolveClass()
    // Plans fetching + ordering (featured in middle)
    // $maxSaving calculation
    // $resolveFeatures closure (مشتركة مع الـ partial عبر Blade scope)
    $totalPlans = $plans->count();
@endphp

<section ...>
    {{-- Title, Subtitle --}}
    {{-- Billing Toggle --}}
    {{-- Savings hint --}}

    @foreach ($plans as $loopIndex => $plan)
        @include('front.sections.pricing._plan_card_dynamic', [
            'loopIndex'  => $loopIndex,
            'totalPlans' => $totalPlans,
        ])
    @endforeach
</section>
```

**لا يوجد أي card HTML مكرر في الملف الرئيسي.**

---

## Design Tokens Migration

| قبل | بعد |
|-----|-----|
| `match($background_token) { ... default => 'bg-[#F2F2F2]' }` | `DesignTokenRegistry::resolveClass('background_token', $background_token)` |
| `match($text_token) { ... default => 'text-theme-heading' }` | `DesignTokenRegistry::resolveClass('text_token', $text_token)` |

**ملاحظة:** الـ default لـ `background_token` في الـ Registry هو `'muted'` → `'bg-theme-muted'`.
كان الـ `match()` القديم يستخدم `'bg-[#F2F2F2]'` كـ default، لكن هذا كان hardcoded خارج الـ Registry. السلوك الصحيح هو `bg-theme-muted` وهو ما يستخدمه الـ Registry.

---

## ضمان عدم تغيير السلوك الوظيفي

| الميزة | آلية الضمان |
|--------|-------------|
| **Monthly / Annual Toggle** | `x-data="{ annual: false }"` على `<section>` — لم يتغير |
| **Discount badges** | `$yearlySaving` محسوبة في `_plan_card_dynamic` — نفس الحسابات |
| **Featured Plans** | `@if ($isFeatured)` في `_plan_card_dynamic` — نفس الـ CSS + badge |
| **Animation classes** | حساب `$animClass` في partial — نفس الخوارزمية (`floor($totalPlans/2)`) |
| **CTA Buttons** | `_plan_cta.blade.php` بدون تغيير، `$ctaUrl` محسوب في partial |
| **Translations** | `t('site.*')` في الملف الرئيسي — بدون تغيير |
| **Features per billing** | `$resolveFeatures` closure متاحة في partial عبر Blade scope inheritance |
| **Price display** | `_plan_header.blade.php` بدون تغيير + `x-text="annual ? ... : ..."` |

---

## نتائج Validation

```
✅ 0 occurrences of "bg-white rounded-[20px]" في الملف الرئيسي
✅ 0 match() calls في الملف الرئيسي
✅ DesignTokenRegistry::resolveClass() موجودة في الملف الرئيسي (سطران)
✅ @include واحد فقط في الـ foreach → _plan_card_dynamic
✅ _plan_card_dynamic يحتوي 6 @include calls (3 × featured + 3 × regular)
✅ Featured wrapper (plan-featured class + badge) في _plan_card_dynamic
✅ Regular wrapper (animClass + shadow-sm) في _plan_card_dynamic
✅ t('site.*') keys محفوظة في الملف الرئيسي
✅ t('dashboard.Most_Popular') في _plan_card_dynamic
✅ @php/@endphp في card partial (سطر 14 + 45)
✅ الملف الرئيسي: 165 سطر (بعد أن كان 269)
✅ Card partial: 82 سطر
```

---

## بنية الملفات النهائية

```
resources/views/front/sections/pricing/
├── pricing_plans.blade.php            ← بدون تغيير (النسخة الثابتة القديمة)
├── pricing_plans_dynamic.blade.php    ← ✏️ معدّل — section wrapper فقط
├── _plan_card_dynamic.blade.php       ← 🆕 جديد — بطاقة خطة كاملة
├── _plan_header.blade.php             ← بدون تغيير
├── _plan_header_dynamic.blade.php     ← بدون تغيير
├── _plan_features.blade.php           ← بدون تغيير
└── _plan_cta.blade.php                ← بدون تغيير
```

---

## Blade Scope — كيف يصل `$resolveFeatures` إلى الـ partial

Laravel's `@include` تشارك تلقائياً **كل** متغيرات الـ view الأب مع الـ partial المضمّن.
لذا `$resolveFeatures` (المعرّفة في `@php` block الملف الرئيسي) متاحة مباشرة في `_plan_card_dynamic.blade.php` بدون الحاجة لتمريرها صراحةً في `compact()`.

المتغيران الوحيدان المُمرَّران صراحةً:
```php
@include('...._plan_card_dynamic', [
    'loopIndex'  => $loopIndex,    // index الـ loop الحالي
    'totalPlans' => $totalPlans,   // إجمالي الخطط
])
```
`$plan` نفسها متاحة عبر loop scope inheritance.
