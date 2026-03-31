---
applyTo: "app/Models/Tenancy/**"
description: "استخدم هذا الملف عند تعديل tenancy models مثل Subscription و Site وما يرتبط ببيانات التيننسي الرسمية."
---

# تعليمات Tenancy Models

استخدم هذه القواعد عند تعديل `app/Models/Tenancy/**`.

- هذه الموديلات تمثل البيانات الرسمية الخاصة بالاشتراكات والتيننسي، وليست مجرد طبقة عرض.
- حافظ على backward compatibility مع البيانات الحالية قدر الإمكان.
- لا تنقل منطق عرض أو تنسيق UI إلى الموديلات.
- إذا كان التعديل يؤثر على provisioning أو domains أو runtime site serving، راجع أيضاً الخدمات والـ jobs والـ config المرتبط.
- تحقّق دائماً هل الكيان يخص marketing content العام أم tenant-owned content قبل تعديل العلاقات أو الحقول.
