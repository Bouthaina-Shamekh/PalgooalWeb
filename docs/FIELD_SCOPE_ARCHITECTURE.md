# Field Scope Architecture — Multi-Tenant Platform Decision

**Status:** Adopted  
**Date:** 2026-06-18  
**Applies to:** All `SectionDefinitionField` entries, Field Presets, and future Section Definitions

---

## المشكلة

عند تصميم الحقول في Section Definitions، قرار "هل هذا الحقل Translatable أم Shared؟" يبدو بسيطاً في موقع واحد لكنه يؤثر على آلاف القوالب والمشتركين.

**الخطأ الشائع:** الحكم على الحقل بناءً على احتياجات موقع Palgoals.com فقط.  
**القرار الصحيح:** الحكم بناءً على طبيعة المنصة متعددة الاستخدامات (Multi-Tenant Template Platform).

---

## السياق: ما هذه المنصة؟

المنصة تخدم في آنٍ واحد:

- **موقع Palgoals الرسمي** — موقع شركة واحد باللغتين
- **مواقع العملاء والمشتركين** — كل عميل موقع مستقل بلغاته ودومينه
- **قوالب جاهزة للبيع** — يُعاد استخدامها في قطاعات ولغات مختلفة
- **مواقع متعددة اللغات** — قد تصل إلى 5+ لغات بهياكل URL مختلفة تماماً
- **قطاعات متنوعة** — شركات، متاجر، مطاعم، SaaS، تعليم، عقارات، خدمات، استضافة

---

## القاعدة المعمارية

> **إذا كان من المعقول أن تختلف قيمة الحقل بين نسختين لغويتين من نفس الصفحة في أي موقع أو قالب أو عميل — فهو Translatable.**  
> **إذا كانت القيمة ثابتة بغض النظر عن اللغة — فهو Shared.**

---

## جدول التصنيف

### Translatable — يختلف بين اللغات

| الحقل | السبب |
|-------|-------|
| `eyebrow` | نص يُقرأ — يُترجم دائماً |
| `title` | عنوان رئيسي — يُترجم دائماً |
| `subtitle` | عنوان فرعي — يُترجم دائماً |
| `description` | نص وصفي — يُترجم دائماً |
| `highlight_text` | نص callout/badge — يُترجم دائماً |
| `button_label` | نص الزر — يُترجم دائماً |
| **`button_url`** | **راجع القسم التالي** |
| `image_alt` | نص بديل للصورة — يُترجم لـ SEO وإمكانية الوصول |
| `meta_title` | عنوان الصفحة في محركات البحث — يُترجم لـ SEO |
| `meta_description` | وصف الصفحة — يُترجم لـ SEO |
| `features[].title` | عنوان عنصر في repeater — يُترجم |

### Shared — لا يتغير بين اللغات

| الحقل | السبب |
|-------|-------|
| `image` | ملف الصورة/الأصل البصري — لا يتغير بالترجمة |
| `icon` | مُعرِّف CSS class للأيقونة (e.g. `ti-star`) — رمز بصري عالمي |
| `icon_media` | صورة الأيقونة — أصل بصري، لا يتغير |
| `icon_source` | `class` أو `media` — قرار تصميمي لا لغوي |
| `image_position` | left/right/center — قرار تخطيط لا لغوي |
| `button_target` | `_self` / `_blank` — سلوك المتصفح لا يرتبط باللغة |
| `layout_style` | نمط التخطيط — قرار تصميمي عالمي |
| `theme_variant` | متغير الثيم — قرار بصري عالمي |
| `background_color` | لون الخلفية — قرار بصري عالمي |

---

## لماذا `button_url` هو **Translatable** وليس Shared

هذا القرار يستحق توثيقاً منفصلاً لأنه مخالف للحدس الأول.

### أمثلة حقيقية تثبت أن الـ URL يختلف بين اللغات:

```
# هيكل URL بـ locale prefix (الأكثر شيوعاً):
ar  →  /contact          /services          /pricing
en  →  /en/contact       /en/services       /en/pricing
fr  →  /fr/contact       /fr/services       /fr/tarifs

# روابط WhatsApp (رقم مختلف لكل بلد):
ar  →  https://wa.me/966501234567
en  →  https://wa.me/14155238886

# Landing pages مترجمة:
ar  →  https://palgoals.com/ar/enterprise
en  →  https://palgoals.com/en/enterprise

# روابط بـ UTM parameters مترجمة:
ar  →  /offers?lang=ar&src=hero
en  →  /offers?lang=en&src=hero
```

### ماذا يحدث إذا جعلناه Shared؟

- قوالب تبيع لمواقع متعددة اللغات **تكسر** — الزر يذهب دائماً للرابط العربي بغض النظر عن لغة الزائر
- عميل يستخدم locale-prefixed URLs لن يتمكن من تخصيص الرابط لكل لغة
- استحالة استخدام نفس القالب في موقعين بلغتين مختلفتين بروابط مختلفة

### الاستثناء المقبول:

إذا أراد مطور قالب معين أن يكون `button_url` مشتركاً (مثلاً رابط ثابت لصفحة homepage)، يمكنه تغيير scope الحقل يدوياً لذلك القالب. لكن **الـ default يجب أن يكون Translatable** لأقصى مرونة.

---

## قاعدة القرار السريع

عند الشك في scope حقل جديد، اسأل:

> **"في قالب يخدم موقعاً بالعربية والإنجليزية والفرنسية، هل من المعقول أن تختلف قيمة هذا الحقل بين اللغات الثلاث؟"**
>
> - نعم → **Translatable**
> - لا → **Shared**

---

## تطبيق القاعدة على Field Presets

جميع الـ presets في `FieldPresetLibrary` مُصنَّفة وفق هذه القواعد مع تعليق توضيحي عند كل حقل يشرح السبب. راجع `app/Support/Sections/FieldPresetLibrary.php`.

### ملخص القرارات في الـ Presets الحالية:

| الحقل | النطاق | السبب |
|-------|-------|-------|
| eyebrow, title, subtitle, description | Translatable | نصوص — دائماً |
| highlight_text, button_label | Translatable | نصوص — دائماً |
| button_url | **Translatable** | يختلف بين locales — راجع الأمثلة أعلاه |
| button_target | Shared | سلوك متصفح — لا يتغير باللغة |
| image | Shared | أصل بصري — لا يتغير باللغة |
| image_alt | Translatable | نص بديل — يُترجم لـ SEO |
| image_position | Shared | قرار تخطيط — لا يتغير باللغة |
| icon, icon_media, icon_source | Shared | رمز بصري — لا يتغير باللغة |
| features[].title | Translatable | نص عنصر — يُترجم |
| features[].icon_* | Shared | أصل/رمز بصري — لا يتغير |
| meta_title, meta_description | Translatable | SEO — يتغير بكل لغة |

---

## الحقول التي ستُضاف مستقبلاً — توجيه مسبق

| الحقل | التوصية | السبب |
|-------|---------|-------|
| `badge_text` | Translatable | نص ظاهر |
| `video_url` | Translatable | قد تختلف نسخ الفيديو بين اللغات |
| `video_embed` | Shared | embed code لا يتغير |
| `background_color` | Shared | قرار تصميمي |
| `layout_direction` | Shared | RTL/LTR يُحدَّد من اللغة نفسها لا من الحقل |
| `items_count` | Shared | رقم تخطيط — لا يتغير |
| `section_id` | Shared | مُعرِّف تقني — لا يتغير |

---

## مراجع

- `app/Support/Sections/FieldPresetLibrary.php` — تطبيق القواعد مع تعليقات
- `app/Models/Sections/SectionDefinitionField.php` — constants: `FIELD_SCOPE_SHARED` / `FIELD_SCOPE_TRANSLATABLE`
- `docs/section-definitions.md` — معمارية نظام Section Definitions الكاملة
- `CLAUDE.md` — §٧ قواعد Field Scope (مرجع سريع للجلسات)
