---
applyTo: "app/Services/Tenancy/**"
description: "استخدم هذا الملف عند تعديل Tenancy services أو خدمات provisioning والدومينات المرتبطة بالاشتراكات."
---

# تعليمات Tenancy Services

استخدم هذه القواعد عند تعديل `app/Services/Tenancy/**`.

- هذه الطبقة مناسبة لمنطق provisioning وdomain handling وruntime tenancy behavior.
- لا تفترض أن `.env` المحلي يطابق الإنتاج، خاصة في الدومينات والتخزين والـ queue والخدمات الخارجية.
- إذا كان التعديل يمس provisioning أو external providers أو domain verification، راجع `config/tenancy.php` والإعدادات المرتبطة أولاً.
- أبق منطق orchestration في الخدمات، ولا تسرّبه إلى Blade أو routes.
- إذا كان التغيير يؤثر على jobs أو notifications أو controllers، راجع نقاط الاستدعاء قبل اعتماد التعديل.
