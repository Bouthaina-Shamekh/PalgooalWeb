---
applyTo: "app/Http/Controllers/Admin/SectionController.php"
description: "استخدم هذا الملف عند تعديل SectionController أو مسار الحفظ والتحضير الخاص بإضافة section type جديدة في admin section builder."
---

# تعليمات Backend Section Builder

استخدم هذه التعليمات عند تعديل `SectionController` أو منطق الحفظ والتحضير المرتبط بالـ section builder.

- `availableSectionTypes()` هو registry الفعلي للأنواع الظاهرة في workspace library.
- `defaultContentForType()` يجب أن يزرع starter content مفيداً لكل section جديدة.
- `normalizeContentByType()` هو مدخل التطبيع الرئيسي قبل الحفظ؛ أضف method مخصصة للأنواع غير البسيطة.
- `syncSharedSectionContent()` مخصص فقط للمحتوى المشترك بين اللغات؛ لا تستخدمه لمحتوى محلي فعلاً.
- `sectionEditorViewData()` يجب أن يبقى مصدر editor state المشترك بين standalone edit و sidebar editor.
- لا تغيّر request names أو translation payload shape أو save-time behavior إلا إذا كان ذلك مقصوداً ومغطى بالتوافق.
- لا تخلط بين refactor read-time preparation وبين rewrite كبير لمسار الحفظ في نفس التعديل.
- إذا احتاجت section بيانات ديناميكية من قاعدة البيانات، فكر أولاً في `SectionQueryResolver` بدلاً من إضافة Blade queries أو منطق مبعثر.
