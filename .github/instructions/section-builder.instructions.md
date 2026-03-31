---
applyTo: "resources/views/dashboard/pages/sections/**"
description: "استخدم هذا الملف عند إنشاء أو تعديل section builder في لوحة التحكم، خاصة عند إضافة section type جديدة من مساحة pages sections."
---

# تعليمات Section Builder

استخدم هذه القواعد عند العمل داخل section builder في لوحة التحكم تحت `resources/views/dashboard/pages/sections/**`.

الـ section builder هنا ليس مجرد Blade form. أي section type جديدة لا تعتبر مكتملة إلا إذا كان مسار الإدارة، ومسار الحفظ، ومسار الرندر في الواجهة الأمامية يعتمدون نفس عقد البيانات.

## قاعدة أساسية

- `Section` للبيانات الهيكلية.
- `SectionTranslation` للعنوان والمحتوى المحلي لكل لغة.
- لا تنقل البيانات بينهما بشكل عشوائي.

## الملفات التي يجب مراجعتها قبل إضافة section type جديدة

- `app/Http/Controllers/Admin/SectionController.php`
- `app/Support/Sections/SectionEditorDataFactory.php`
- `app/Support/Sections/SectionEditorRepeaterFactory.php`
- `app/Support/Sections/SectionMediaPreviewBuilder.php`
- `resources/views/dashboard/pages/sections/partials/editor-form.blade.php`
- `resources/views/dashboard/pages/sections/edit.blade.php`
- `resources/views/dashboard/pages/sections/partials/sidebar-editor.blade.php`
- `resources/views/front/pages/page.blade.php`
- `resources/views/front/pages/partials/legacy-section.blade.php`
- `app/Support/Sections/SectionQueryResolver.php` عندما تحتاج section إلى بيانات runtime من قاعدة البيانات

## خطوات إضافة section type جديدة

1. أضف النوع داخل `SectionController::availableSectionTypes()`.
2. أضف starter content داخل `defaultContentForType()` حتى لا ينشئ quick-add section فارغة.
3. وسّع `normalizeContentByType()` وأضف method مخصصة للتطبيع إذا كان payload غير بسيط.
4. حدّث `syncSharedSectionContent()` فقط إذا كان هناك content مشترك بين اللغات.
5. أضف flags وبيانات editor الجاهزة داخل `SectionEditorDataFactory`.
6. إذا كانت section تستخدم repeaters، وسّع `SectionEditorRepeaterFactory`.
7. إذا كانت section تعرض media preview، استخدم `SectionMediaPreviewBuilder`.
8. اعرض الحقول داخل `resources/views/dashboard/pages/sections/partials/editor-form.blade.php` مع الحفاظ على input contract الحالي.
9. حافظ على عمل `edit.blade.php` و `partials/sidebar-editor.blade.php` معاً لأنهما يعتمدان على نفس shared partial.
10. أكمل دعم frontend rendering في `resources/views/front/pages/partials/legacy-section.blade.php` والـ partial الأمامي المناسب.
11. إذا كانت section تعتمد على بيانات ديناميكية، فحمّلها عبر `SectionQueryResolver`.

## قواعد عقد الإدخال

- حافظ على أسماء الطلبات الحالية.
- حافظ على شكل `translations[locale][content][...]`.
- حافظ على `data-*` hooks وبنية DOM الخاصة بالـ repeaters.
- لا تعِد تسمية persisted content keys بدون طبقة توافق.
- لا تجمع بين إعادة كتابة Blade كبيرة وتغيير save-time normalization في نفس الخطوة.

## قواعد Blade

- فضّل البيانات الجاهزة من controller أو support layer على المنطق الثقيل داخل Blade.
- أعد استخدام أنماط الحقول الموجودة في `editor-form.blade.php`.
- تعامل مع `old()` من خلال editor state الجاهز، وليس بحلول موضعية متفرقة لكل حقل.
- أبق shared partial متوافقة مع full-page edit ومع sidebar editor معاً.

## قاعدة الاكتمال

لا تعتبر section type منتهية فقط لأنها ظهرت في محرر الإدارة. قبل اعتبار العمل مكتملًا تحقّق من:

- إمكانية إنشاء النوع من section library
- فتح المحرر في الواجهتين
- بقاء القيم بعد validation re-render
- حفظ نفس content shape المتوقع
- تعرّف frontend rendering على النوع الجديد
- تحميل البيانات الديناميكية بشكل صحيح عند الحاجة

## قائمة التحقق

- Quick-add ينشئ section بنجاح.
- Standalone edit يفتح بنجاح.
- Sidebar editor يفتح بنجاح.
- Reorder و rename و toggle ما زالت تعمل.
- الصفوف القديمة ما زالت تفتح بدون crash.
- غياب ترجمة لغة معينة لا يكسر المحرر.
- الواجهة الأمامية ترندر النوع الجديد بدون التأثير على الأنواع الأخرى.

## Anti-patterns يجب تجنبها

- إضافة type جديدة في Blade فقط بدون تحديث controller save path
- تخزين النصوص المحلية داخل `Section`
- إعادة المنطق الثقيل إلى shared Blade partial
- تكرار query logic في Blade وفي `SectionQueryResolver` معاً
- تغيير JavaScript hooks الخاصة بالإدارة كأثر جانبي غير مقصود من refactor في PHP
