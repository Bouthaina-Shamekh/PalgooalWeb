# دليل الوكيل — Palgoals

استخدم هذا الملف قبل أي تعديل على الكود.

---

## اقرأ أولاً (بالترتيب)

```
docs/README.md                  ← نقطة الدخول — اقرأه دائماً
docs/00-project-overview.md     ← ما هو النظام؟
docs/01-system-architecture.md  ← خريطة الوحدات، الـ guards، الـ middleware
docs/22-coding-standards.md     ← t()، تسمية المفاتيح، أنماط UX، قواعد PR
```

ثم اقرأ الوثيقة المتخصصة حسب المهمة:

| المهمة | الوثيقة |
|--------|---------|
| Section / Rendering | `docs/07-section-definitions.md` + `docs/09-rendering-flow.md` |
| قاعدة البيانات / Migration | `docs/03-database-architecture.md` |
| Auth / Permissions / Policies | `docs/24-security-notes.md` |
| Billing / Invoices / Subscriptions | `docs/25-billing-system.md` |
| Languages / t() / Translations | `docs/26-locale-system.md` |
| إعداد بيئة / Onboarding | `docs/21-developer-guide.md` |

---

## قواعد أساسية

- **`t()` فقط** — `__()` و `trans()` محظوران في أي ملف Blade أو PHP.
- **Flash keys**: `session('ok')` للنجاح، `session('error')` للخطأ. لا تستخدم `'success'`.
- **Section content**: `$data` هو المتغير الصحيح في Blade views — ليس `$fields`.
- **RTL**: الموقع عربي RTL بشكل أساسي — افترض RTL في أي تصميم UI جديد.
- **Money**: كل المبالغ تُخزَّن كـ integer cents. لا floats.
- إذا لمس العمل **tenancy أو provisioning أو WHM**: راجع `docs/25-billing-system.md` و `docs/24-security-notes.md` أولاً.
- إذا لمس العمل **Sections أو rendering**: راجع `docs/07-section-definitions.md` أولاً.

---

## لا تقرأ هذه الملفات — إنها مؤرشفة

الملفات التالية في `docs/_archive/` قديمة وقد تحتوي معلومات خاطئة:

```
docs/_archive/legacy-docs/architecture.md
docs/_archive/legacy-docs/developer-guide.md
docs/_archive/legacy-docs/editor-system.md
docs/_archive/legacy-docs/locale-system.md
docs/_archive/legacy-docs/invoice-system.md
docs/_archive/legacy-docs/order-system.md
docs/_archive/legacy-docs/subscription-system.md
docs/_archive/legacy-docs/sections-system.md
docs/_archive/legacy-plans/refactor-plan.md
```

المرجع الصحيح دائماً هو الوثائق المرقّمة في `docs/`.

---

## الملاحظات السياقية

- `CLAUDE.md` في جذر المشروع يحتوي السجل الكامل لتغييرات الجلسات — راجعه لفهم أي تعديل سابق.
- المشروع يعمل على **Laravel 11** + Blade + Tailwind + Alpine.js + GSAP.
- قاعدة البيانات: MySQL على `127.0.0.1:3306`، DB: `palgoalsnewtest1`.
- PHP غير متوفر في sandbox — كل أوامر `artisan` تُشغَّل على جهاز المستخدم.
