## تعليمات Workspace لمشروع Palgoals

استخدم هذه التعليمات في كل العمل داخل هذا المستودع.

### اقرأ أولاً

قبل أي تعديل غير بسيط، اقرأ:

1. `NOTES.md`
2. `docs/developer-guide.md`
3. `docs/architecture.md`
4. `docs/sections-system.md`
5. `docs/editor-system.md`
6. `docs/refactor-plan.md`

بعد ذلك استخدم ملف التعليمات المتخصص المناسب داخل `.github/instructions/`.

### قواعد المشروع

- التزم بقواعد التسمية والراوتات الموجودة في `NOTES.md`.
- اعتبر `Section` للبيانات الهيكلية و`SectionTranslation` للمحتوى المحلي.
- لا تفترض وجود Vite أو Mix وحده؛ تحقّق من الشاشة المتأثرة أولاً.
- فضّل البيانات المجهزة في controller أو support layer على Blade الثقيل.
- في section editor حافظ على request names وtranslation payload shape وrepeater hooks وsave-time behavior إلا إذا كانت المهمة تتطلب تغيير ذلك صراحة.

### مناطق التعليمات المتخصصة

- `resources/views/dashboard/pages/sections/**`: قواعد واجهة section builder
- `app/Http/Controllers/Admin/SectionController.php`: قواعد backend الخاصة بالـ section builder
- `app/Support/Sections/**`: قواعد support layer الخاصة بالأقسام
- `resources/views/front/pages/**`: قواعد frontend section rendering
- `resources/views/components/template/sections/**`: قواعد frontend section partials النهائية
- `routes/client.php`: قواعد مسارات client subscription editors وtenancy flows
- `app/Models/Tenancy/**`: قواعد tenancy models
- `app/Services/Tenancy/**`: قواعد tenancy services
- `app/Jobs/**`: قواعد jobs الخاصة بالـ provisioning والاشتراكات

### فحوصات أمان

- لا تفترض أن `.env` المحلي يطابق الإنتاج.
- إذا لمس العمل uploads أو queues أو storage أو tenancy أو مزودات خارجية، فتحقّق من الإعدادات المرتبطة أولاً.
- قبل تعديل frontend rendering، حدّد هل الصفحة تستخدم published builder HTML أو builder JSON أو legacy sections.
