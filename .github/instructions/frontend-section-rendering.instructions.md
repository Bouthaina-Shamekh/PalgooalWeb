---
applyTo: "resources/views/front/pages/**"
description: "استخدم هذا الملف عند تعديل frontend section rendering أو عند إكمال دعم section type جديدة خارج محرر الإدارة."
---

# تعليمات Frontend Section Rendering

استخدم هذه القواعد عند تعديل مسار رندر الأقسام في الواجهة الأمامية.

- إضافة section type جديدة في لوحة التحكم لا تكفي وحدها؛ يجب إكمال مسار frontend rendering أيضاً.
- افحص `resources/views/front/pages/page.blade.php` لتحديد مسار الرندر النشط.
- افحص `resources/views/front/pages/partials/legacy-section.blade.php` لأنه يحتوي mapping فعلياً لعدة section types إلى frontend partials.
- إذا كان النوع يستخدم alias قديم مثل `templates-pages`، حافظ على طبقة التوافق ولا تكسر البيانات القديمة.
- إذا كانت section تعتمد على بيانات runtime مثل reviews أو portfolio أو hosting pricing أو template listings، استخدم `SectionQueryResolver` لتحضير البيانات.
- لا تنقل queries ثقيلة إلى Blade.
- حافظ على fallback behavior عند غياب الترجمة الحالية، واستفد من `translation()` helpers عندما تكون متاحة.
- عند إضافة frontend partial جديد، اتبع النمط الحالي تحت `resources/views/components/template/sections/` أو العائلة القائمة المناسبة.
- لا تغيّر سلوك الأنواع القديمة أثناء إضافة نوع جديد إلا إذا كان هذا جزءاً مقصوداً من المهمة.

## تحقق قبل اعتبار العمل مكتملًا

- الصفحة العامة تتعرف على section type الجديدة.
- المحتوى المترجم يظهر باللغة الحالية أو fallback آمن.
- البيانات الديناميكية يتم تحميلها من resolver المناسب.
- الأنواع القديمة ما زالت ترندر كما كانت.
