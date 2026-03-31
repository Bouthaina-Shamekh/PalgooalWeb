---
applyTo: "resources/views/components/template/sections/**"
description: "استخدم هذا الملف عند إنشاء أو تعديل frontend section partials الخاصة بالأنواع التي يرندرها النظام في الواجهة العامة."
---

# تعليمات Frontend Section Partials

استخدم هذه القواعد عند تعديل `resources/views/components/template/sections/**`.

- هذه الملفات هي طبقة الرندر النهائية للـ section بعد أن يتم تجهيز البيانات مسبقاً.
- لا تضف queries ثقيلة داخل هذه partials.
- توقّع أن تأتي البيانات من `SectionTranslation->content` أو من `SectionQueryResolver` أو من controller/support layer.
- حافظ على تحمل القيم الناقصة والصفوف القديمة قدر الإمكان.
- إذا كان النوع الجديد يحتاج partial جديدة، اربطها أيضاً في `resources/views/front/pages/partials/legacy-section.blade.php` وفي أي mapping آخر مستخدم في `resources/views/front/pages/page.blade.php`.
- إذا كانت section تعتمد على alias قديم، حافظ على التوافق ولا تكسر البيانات القديمة.
- لا تغيّر واجهات الأنواع القديمة أثناء إضافة نوع جديد إلا إذا كان ذلك جزءاً مطلوباً من المهمة.

## قبل إنهاء العمل

- تأكد أن partial تستقبل shape البيانات الفعلي القادم من النظام.
- تأكد أن fallback للترجمة أو القيم الفارغة لا يكسر الصفحة.
- تأكد أن الرندر الجديد لا يعتمد على منطق مكرر موجود أصلاً في resolver أو support classes.
