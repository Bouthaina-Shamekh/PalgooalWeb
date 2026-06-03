# CLAUDE.md — قواعد وملاحظات مشروع PalgooalWeb

## اللغات المتعددة (i18n / Localization)

المشروع يدعم **لغات متعددة** (عربي + إنجليزي على الأقل).

### قاعدة مهمة جداً:
**عند أي تعديل على نصوص الواجهة، استخدم دائماً دالة الترجمة ولا تكتب نصاً ثابتاً (hardcoded).**

### صيغة دالة الترجمة:
```blade
{{ t('section.Key_Name', 'Fallback Text') }}
```

**المعامل الأول**: مفتاح الترجمة بصيغة `section.Key_Name` (نقطة تفصل القسم عن المفتاح)  
**المعامل الثاني**: النص الاحتياطي (fallback) يظهر إذا لم يوجد المفتاح في ملفات الترجمة

### أمثلة صحيحة:
```blade
{{ t('dashboard.Add_Page', 'Add Page') }}
{{ t('checkout.Order_Complete', 'Order completed successfully') }}
{{ t('template.Buy_Now', 'Buy Now') }}
{{ t('common.Cancel', 'Cancel') }}
```

### ملاحظات:
- النصوص الثابتة المكتوبة مباشرة في Blade بدون `t()` تُعتبر خطأ
- الـ fallback (المعامل الثاني) يكتب بالإنجليزية عادةً
- أسماء المفاتيح تستخدم `Snake_Case` مع أول حرف كبير لكل كلمة
- أسماء الأقسام (sections) تكون lowercase: `dashboard`, `checkout`, `template`, `common`...

---

## UX / Design Language
- الموقع RTL عربي بشكل أساسي
- خط Cairo مستخدم في الواجهة
- Tailwind CSS + Alpine.js + GSAP للتأثيرات
- Laravel 11 Blade templates
