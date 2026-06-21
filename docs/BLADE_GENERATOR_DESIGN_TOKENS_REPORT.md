# BladeGenerator — Design Token Integration Report

**Date:** 2026-06-22  
**File changed:** `app/Support/Sections/BladeGenerator.php`

---

## المشكلة

قبل هذا التحديث، كان `BladeGenerator` يعامل حقول Design Tokens (مثل `background_token`,
`section_spacing`) كحقول محتوى عادية، فيولّد كوداً مثل:

```blade
@php
    $background_token = trim((string) ($data['background_token'] ?? ''));
@endphp

{{-- ... --}}
@if ($background_token)
    <p>{{ $background_token }}</p>
@endif
```

هذا خاطئ وظيفياً لسببين:
1. الـ default المستخدم (`''`) يتجاهل قيمة الـ default المسجلة في `DesignTokenRegistry`.
2. الـ token يُعرض كنص في `<p>` بدلاً من أن يُحوَّل إلى Tailwind class ويُطبَّق على الـ wrapper.

---

## كيف يتعرف BladeGenerator على Design Tokens

البوابة الوحيدة:

```php
DesignTokenRegistry::has($fieldKey)  // → bool
```

إذا أعادت `true`، يُعامَل الحقل كـ design token في كلا المكانين:
- **PHP block**: قراءة خاصة بـ Registry default + class resolver.
- **HTML block**: يُطبَّق على الـ wrapper ولا يُنتج أي HTML في جسم الصفحة.

---

## استخدام Registry Defaults في PHP Block

بدلاً من `'' ` كـ default:

```php
// قديم
$background_token = trim((string) ($data['background_token'] ?? ''));

// جديد
$background_token = trim((string) ($data['background_token'] ?? 'muted'));
//                                                                ↑
//                              DesignTokenRegistry::defaultValue('background_token')
```

كل token يأخذ default-ه من `DesignTokenRegistry::ALL_TOKENS[$key]['default']`.

---

## استخدام resolveClass() في PHP Block

لكل token له `css_map` غير فارغ، يُنتج البُنية التالية:

```php
$background_token = trim((string) ($data['background_token'] ?? 'muted'));
$backgroundClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('background_token', $background_token);

$text_token = trim((string) ($data['text_token'] ?? 'heading'));
$textClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('text_token', $text_token);

$section_spacing = trim((string) ($data['section_spacing'] ?? 'md'));
$sectionSpacingClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('section_spacing', $section_spacing);

$container_width = trim((string) ($data['container_width'] ?? 'default'));
$containerWidthClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('container_width', $container_width);
```

### قاعدة تسمية class variables

يحوّل `tokenClassVarName()` اسم الـ token إلى اسم المتغير:

| token key         | class var name        |
|-------------------|-----------------------|
| `background_token`| `$backgroundClass`    |
| `text_token`      | `$textClass`          |
| `section_spacing` | `$sectionSpacingClass`|
| `container_width` | `$containerWidthClass`|

الخوارزمية:
1. أزل لاحقة `_token` (إن وُجدت): `background_token` → `background`
2. حوّل snake_case إلى camelCase: `section_spacing` → `sectionSpacing`
3. أضف `Class` في النهاية: `sectionSpacing` → `sectionSpacingClass`

---

## تطبيق الـ Class Variables في HTML Block

### تطبيق على `<section>`

الـ tokens التالية (إن وُجدت في الحقول) تُضاف إلى `<section>`:

| Token             | Class var             | مكان التطبيق |
|-------------------|-----------------------|--------------|
| `background_token`| `$backgroundClass`    | `<section>`  |
| `section_spacing` | `$sectionSpacingClass`| `<section>`  |

مثال الناتج:

```blade
<section class="section-hero {{ $backgroundClass }} {{ $sectionSpacingClass }} px-4 sm:px-6 lg:px-12">
```

### تطبيق على container `<div>`

| Token             | Class var             | مكان التطبيق     |
|-------------------|-----------------------|------------------|
| `container_width` | `$containerWidthClass`| container `<div>`|
| `text_token`      | `$textClass`          | container `<div>`|

مثال الناتج:

```blade
    <div class="{{ $containerWidthClass }} {{ $textClass }} mx-auto">
```

### لا يوجد HTML في جسم الصفحة

Design tokens تُعيد `null` من `renderFieldHtml()` — لا `<p>`, لا `<span>`, لا أي عنصر.
في الـ ungrouped loop يُتجاهل الـ token صراحةً بـ `continue`.

---

## ما لم يتم دعمه بعد: `image_position`

`image_position` مسجّل في `DesignTokenRegistry` لكن `css_map` فارغ:

```php
'css_map' => [],  // لا class واحد يُعبّر عن موضع الصورة
```

السبب: موضع الصورة يُحدّد ترتيب عناصر CSS Grid/Flex، وليس class واحداً.

الناتج الحالي في PHP block:

```php
$image_position = trim((string) ($data['image_position'] ?? 'right')); // design-token / shared
// TODO: use $image_position to apply layout classes (no css_map in Registry).
```

#### الاستخدام المقصود (للمطور بعد إنشاء الـ scaffold):

```blade
@php
    $imageOrder     = $image_position === 'left' ? 'order-first lg:order-first' : 'order-last lg:order-last';
    $contentOrder   = $image_position === 'left' ? 'order-last lg:order-last'   : 'order-first lg:order-first';
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
    <div class="{{ $imageOrder }}">
        @if ($image) <img src="{{ $image }}" alt="{{ $image_alt }}"> @endif
    </div>
    <div class="{{ $contentOrder }}">
        {{-- content --}}
    </div>
</div>
```

---

## مثال Scaffold كامل

Section يحتوي: `background_token`, `text_token`, `section_spacing`, `container_width`,
`eyebrow`, `title`, `subtitle`, `button_url`, `image`.

```blade
@php
    // Auto-generated scaffold: content_showcase — 2026-06-22
    // $data contains all field values (shared + translatable merged).
    // Design token classes resolved via DesignTokenRegistry::resolveClass().

    $background_token = trim((string) ($data['background_token'] ?? 'muted')); // design-token / shared
    $backgroundClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('background_token', $background_token);

    $text_token = trim((string) ($data['text_token'] ?? 'heading')); // design-token / shared
    $textClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('text_token', $text_token);

    $section_spacing = trim((string) ($data['section_spacing'] ?? 'md')); // design-token / shared
    $sectionSpacingClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('section_spacing', $section_spacing);

    $container_width = trim((string) ($data['container_width'] ?? 'default')); // design-token / shared
    $containerWidthClass = \App\Support\Sections\DesignTokenRegistry::resolveClass('container_width', $container_width);

    $eyebrow = trim((string) ($data['eyebrow'] ?? '')); // text / trans
    $title = trim((string) ($data['title'] ?? '')); // text / trans
    $subtitle = trim((string) ($data['subtitle'] ?? '')); // text / trans
    $button_url = trim((string) ($data['button_url'] ?? '')); // url / trans
    $button_label = trim((string) ($data['button_label'] ?? '')); // text / trans
    $button_target = trim((string) ($data['button_target'] ?? '')); // text / shared
    $image = \App\Support\Sections\SectionFrontendMediaResolver::resolve($data['image'] ?? null); // media / shared
@endphp

<section class="section-content-showcase {{ $backgroundClass }} {{ $sectionSpacingClass }} px-4 sm:px-6 lg:px-12">
    <div class="{{ $containerWidthClass }} {{ $textClass }} mx-auto">

        {{-- Intro --}}
        @if ($eyebrow)
            <span class="section-eyebrow">{{ $eyebrow }}</span>
        @endif
        @if ($title)
            <h2 class="section-title">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="section-subtitle">{{ $subtitle }}</p>
        @endif

        {{-- CTA --}}
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

    </div>
</section>
```

---

## ملخص التغييرات في BladeGenerator.php

| المكان                  | قبل                                        | بعد                                                  |
|-------------------------|--------------------------------------------|------------------------------------------------------|
| `buildPhpBlock()`       | `?? ''` لجميع الحقول                      | `?? 'default'` من Registry لـ tokens                |
| `buildPhpBlock()`       | لا شيء بعد قراءة الـ token              | `$xxxClass = DesignTokenRegistry::resolveClass(...)` |
| `buildHtmlBlock()`      | `<section class="section-key">`            | يضم `{{ $backgroundClass }} {{ $sectionSpacingClass }}`|
| `buildHtmlBlock()`      | `<div class="container">`                  | يضم `{{ $containerWidthClass }} {{ $textClass }}`    |
| `renderFieldHtml()`     | يولّد `<p>{{ $background_token }}</p>`     | يُعيد `null` لجميع design tokens                    |
| ungrouped loop          | يُحاول render كل حقل                     | يتجاوز (`continue`) أي design token صراحةً          |
| `tokenClassVarName()`   | غير موجودة                                 | helper جديد لتحويل key → camelCase class var        |
| `tokenHasCssMap()`      | غير موجودة                                 | helper جديد: `true` إذا `css_map` غير فارغ          |
| `SECTION_TOKENS` const  | غير موجودة                                 | `['background_token', 'section_spacing']`            |
| `CONTAINER_TOKENS` const| غير موجودة                                 | `['container_width', 'text_token']`                  |
