# دليل سريع لملفات التعليمات

استخدم هذا الملف كخريطة سريعة، وليس كتعليمات always-on.

## المدخلات الرئيسية

- `.github/copilot-instructions.md`: مدخل workspace العام
- `AGENTS.md`: مدخل إضافي على مستوى الجذر
- `.github/instructions/palgoals-global.instructions.md`: قواعد عامة على مستوى المشروع كله

## حسب المسار

- `resources/views/dashboard/pages/sections/**`: `section-builder.instructions.md`
- `app/Http/Controllers/Admin/SectionController.php`: `section-builder-backend.instructions.md`
- `app/Support/Sections/**`: `sections-support.instructions.md`
- `resources/views/front/pages/**`: `frontend-section-rendering.instructions.md`
- `resources/views/components/template/sections/**`: `frontend-section-partials.instructions.md`
- `routes/client.php`: `tenancy-client-routes.instructions.md`
- `app/Models/Tenancy/**`: `tenancy-models.instructions.md`
- `app/Services/Tenancy/**`: `tenancy-services.instructions.md`
- `app/Jobs/**`: `tenancy-jobs.instructions.md`

## قاعدة عملية

إذا كانت المهمة تمس section type جديدة، فغالباً ستحتاج أكثر من ملف تعليمات واحد: واجهة الإدارة، backend الحفظ، support layer، ثم frontend rendering.
