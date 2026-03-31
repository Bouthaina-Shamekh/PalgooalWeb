---
applyTo: "app/Support/Sections/**"
description: "استخدم هذا الملف عند تعديل support classes الخاصة بالأقسام مثل SectionEditorDataFactory و SectionEditorRepeaterFactory و SectionQueryResolver."
---

# تعليمات Sections Support Layer

استخدم هذه القواعد عند تعديل `app/Support/Sections/**`.

- الهدف من هذه الطبقة هو نقل التحضير والمنطق القابل لإعادة الاستخدام خارج Blade قدر الإمكان.
- `SectionEditorDataFactory` مسؤول عن editor state الجاهز للعرض، وليس عن الحفظ.
- `SectionEditorRepeaterFactory` مسؤول عن repeaters المجهزة للعرض وإعادة التعبئة بعد validation.
- `SectionMediaPreviewBuilder` مسؤول عن تحويل media IDs والمسارات إلى preview URLs.
- `SectionQueryResolver` مسؤول عن runtime enrichment والبيانات الديناميكية التي تحتاجها بعض section types في الواجهة الأمامية.
- لا تكرر نفس القاعدة في أكثر من support class بدون سبب واضح.
- لا تنقل منطق render النهائي إلى هذه الطبقة إذا كان مكانه الطبيعي في frontend partial.
- عند إضافة section type جديدة، اجعل أسماء المفاتيح والـ payload منسجمة مع `SectionController` و `editor-form.blade.php`.
- حافظ على backward compatibility مع الأسماء القديمة أو aliases الموجودة عندما تكون البيانات القديمة ما زالت مستخدمة.
