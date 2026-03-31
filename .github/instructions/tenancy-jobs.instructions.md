---
applyTo: "app/Jobs/**"
description: "استخدم هذا الملف عند تعديل jobs المتعلقة بالاشتراكات أو provisioning أو sync مع مزود خارجي."
---

# تعليمات Tenancy And Provisioning Jobs

استخدم هذه القواعد عند تعديل `app/Jobs/**`.

- بعض الـ jobs هنا جزء من provisioning أو مزامنة الاشتراكات مع مزود خارجي؛ افهم الخدمة أو الـ model الذي تستدعيه قبل التعديل.
- لا تنقل منطق orchestration الكبير إلى route أو controller إذا كان مكانه الطبيعي job أو service.
- عند تعديل job تمس مزوداً خارجياً أو حالة اشتراك، راجع الخدمة المرتبطة وبيانات التيننسي الأساسية.
- انتبه لتأثير retries وqueue behavior عند تعديل المسار.
