---
applyTo: "routes/client.php"
description: "استخدم هذا الملف عند تعديل مسارات client area أو subscription editors أو tenancy-related client flows."
---

# تعليمات Client Routes And Subscription Flows

استخدم هذه القواعد عند تعديل `routes/client.php`.

- هذا الملف يضم مسارات client dashboard ومسارات subscriptions ومسارات محررات الصفحات والهوم الخاصة بالعميل.
- لا تغيّر أسماء المسارات أو بنية الروابط بدون سبب واضح لأن الواجهات والـ redirects تعتمد عليها.
- محررات صفحات الاشتراك والهوم تستخدم مسار section editor مشابه للإدارة لكن داخل client area؛ راقب التوافق بين المسارات والـ controllers المرتبطة.
- حافظ على middlewares الحالية ما لم تكن المهمة تتطلب تغييراً مقصوداً في الوصول أو المصادقة.
- أي تغيير في مسار subscriptions أو editor flows يجب التحقق معه من controllers المقابلة وسلوك redirects.
- لا تخلط بين marketing admin routes وبين client subscription editor routes.
