# دليل الوكيل في Palgoals

استخدم هذا الملف قبل أي تعديل غير بسيط.

## اقرأ أولاً

اقرأ الملفات التالية بالترتيب:

1. `NOTES.md`
2. `docs/developer-guide.md`
3. `docs/architecture.md`
4. `docs/sections-system.md`
5. `docs/editor-system.md`
6. `docs/refactor-plan.md`

## قواعد أساسية

- اعتبر `Section` للبيانات الهيكلية و`SectionTranslation` للمحتوى المحلي.
- لا تفترض وجود pipeline أصول واحد؛ تحقّق هل الشاشة تستخدم Vite أو Laravel Mix.
- فضّل التحضير في controller أو support layer على Blade الثقيل.
- إذا لمس العمل uploads أو queues أو storage أو tenancy أو مزوداً خارجياً، فتحقّق من الإعدادات أولاً.
- قبل تعديل frontend rendering، حدّد هل الصفحة تعمل عبر published builder HTML أو builder JSON أو legacy sections.

## التعليمات المتخصصة

استخدم ملف التعليمات المناسب داخل `.github/instructions/` حسب المسار الذي تعمل فيه.

- `resources/views/dashboard/pages/sections/**`
- `app/Http/Controllers/Admin/SectionController.php`
- `app/Support/Sections/**`
- `resources/views/front/pages/**`
- `resources/views/components/template/sections/**`
- `routes/client.php`
- `app/Models/Tenancy/**`
- `app/Services/Tenancy/**`
- `app/Jobs/**`

نقطة الدخول الرئيسية لتعليمات workspace هي `.github/copilot-instructions.md`.
