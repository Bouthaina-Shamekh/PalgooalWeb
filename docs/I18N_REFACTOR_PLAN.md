# I18N_REFACTOR_PLAN — تحويل `__()` إلى `t()` بالكامل

> **نوع الوثيقة:** خطة Refactor تنفيذية
> **الهدف:** تحويل المشروع تدريجياً من `__()` إلى `t()` في الكود المملوك للمشروع، مع إصلاح مفاتيح flash الخاطئة.
> **الحالة:** خطة — لم يُنفَّذ أي تعديل بعد.
> **تاريخ الخطة:** 2026-06-15

---

## الأرقام الإجمالية

| المصدر | عدد الملفات | عدد الاستخدامات |
|---|---|---|
| `app/` (PHP) | 31 ملف | **185** |
| `resources/views/` (Blade) | 112 ملف | **1,018** |
| **الإجمالي** | **~143 ملف** | **~1,203** |

بالإضافة إلى: **~25 ملف** يستخدم `session('success')` بدلاً من `session('ok')` — بعضها يحتوي `__()` في نفس الوقت (Combined).

> **ملاحظة خاصة — مفاتيح flash غير معيارية:**
> وُجد استخدام لمفاتيح flash غير موثقة في المعيار:
> - `session('toast_success')` — في `SubscriptionController`
> - `session('brand_settings_success')` — في `SubscriptionThemeController` (Admin + Client)
> يجب توحيدها ضمن Phase 1/2 بحسب تعقيدها.

---

## أعلى 10 ملفات من حيث العدد

| # | الملف | عدد `__()` | ملاحظة |
|---|---|---|---|
| 1 | `resources/views/dashboard/pages/sections/create.blade.php` | 47 | Section editor — معقد |
| 2 | `resources/views/dashboard/pages/sections/partials/repeaters/build-steps-repeater.blade.php` | 48 | Repeater partial |
| 3 | `resources/views/dashboard/pages/sections/partials/repeaters/pricing-plans-repeater.blade.php` | 45 | Repeater partial |
| 4 | `resources/views/dashboard/pages/sections/partials/repeaters/outputs-repeater.blade.php` | 43 | Repeater partial |
| 5 | `resources/views/dashboard/pages/sections/partials/repeaters/services-repeater.blade.php` | 42 | Repeater partial |
| 6 | `resources/views/dashboard/pages/sections/partials/brand-settings-drawer.blade.php` | 40 | Drawer UI |
| 7 | `app/Http/Controllers/Admin/Management/DomainController.php` | 28 | Combined: `__()` + `session('success')` |
| 8 | `resources/views/dashboard/management/invoices/index.blade.php` | 38 | Invoice list |
| 9 | `resources/views/dashboard/pages/sections/partials/repeaters/campaign-features-repeater.blade.php` | 38 | Repeater partial |
| 10 | `app/Support/Sections/SectionDefinitionImportService.php` | 16 | Validation error messages |

---

## تصنيف جميع الملفات

### تصنيف PHP (`app/`)

| الملف | Count | Category | ملاحظة تفصيلية |
|---|---|---|---|
| `Controllers/Admin/Management/DomainController.php` | 28 | **E** | `session('success')` + `__()` + hardcoded Arabic + variable replacement |
| `Support/Sections/SectionDefinitionImportService.php` | 16 | **B** | رسائل validation errors بالإنجليزي — تحتاج مفاتيح `dashboard.Import_*` |
| `Support/Sections/SectionQueryResolver.php` | 15 | **E** | fallback labels تظهر في الأقسام الحية (frontend) — تحتاج مفاتيح `site.*` |
| `Controllers/Admin/Management/InvoiceController.php` | 13 | **E** | 3x flash messages مع `with('ok', __(...))` صحيح + 5x email body strings |
| `Support/Sections/ShellSectionEditorSupport.php` | 12 | **B** | labels لمحرر Shell — تحتاج مفاتيح `dashboard.Editor_*` |
| `Controllers/Client/SubscriptionPageEditorController.php` | 11 | **E** | `session('success')` + `__()` + navigation labels داخل JSON response |
| `Support/Sections/SectionSidebarEditorViewDataFactory.php` | 9 | **B** | labels لـ sidebar المحرر — JSON data للـ frontend |
| `Controllers/Client/SubscriptionSiteShellEditorController.php` | 9 | **B** | navigation + editor labels في JSON response |
| `Notifications/Tenancy/SubscriptionProvisionedNotification.php` | 7 | **C** | محتوى بريد إلكتروني — context خارج HTTP |
| `Support/Sections/SectionDefinitionFrontendViewDataFactory.php` | 6 | **B** | رسائل خطأ dev-facing عند عدم وجود template |
| `Http/Requests/UpdateDomainDnsRequest.php` | 6 | **B** | validation messages في FormRequest |
| `Controllers/Client/SubscriptionHomepageEditorController.php` | 6 | **E** | `session('success')` + `__()` + navigation labels |
| `Controllers/Admin/SectionDefinitionImportExportController.php` | 6 | **E** | `session('success')` + `__()` في flash + strategy labels |
| `Support/Sections/SectionWorkspacePreviewViewDataFactory.php` | 5 | **B** | preview labels في JSON data |
| `Controllers/Admin/Management/SubscriptionController.php` | 5 | **E** | `session('toast_success')` غير معياري + `__()` |
| `Controllers/Admin/HomeController.php` | 5 | **E** | `session('success')` + `__()` — Arabic strings |
| `Controllers/Admin/AppearanceController.php` | 4 | **E** | 4x `with('success', __(...))` — flash key خاطئ + `__()` |
| `Actions/Fortify/PasswordValidationRules.php` | 4 | **C** | Fortify boundary — validation messages للـ Fortify |
| `Support/Sections/SectionRenderer.php` | 3 | **B** | fallback text لـ legacy renderer |
| `Controllers/Front/TestimonialSubmissionController.php` | 2 | **A** | رسائل بسيطة: شكر + خطأ عام للواجهة العامة |
| `Controllers/Admin/Management/SubscriptionThemeController.php` | 2 | **E** | `session('success')` + `session('brand_settings_success')` غير معياري |
| `Controllers/Admin/Management/OrderController.php` | 2 | **E** | mix: `with('ok', __(...))` + `with('success', __(...))` |
| `Notifications/Tenancy/AdminSubscriptionProvisioned.php` | 1 | **C** | بريد إلكتروني |
| `Models/Plan.php` | 1 | **A** | `__('Most Popular')` — label بسيط |
| `Livewire/Admin/Sections/FaqSection.php` | 1 | **D** | Livewire (TD-8) |
| `Livewire/Admin/Sections/CtaSection.php` | 1 | **D** | Livewire (TD-8) |
| `Http/Requests/Admin/UpdateSectionDefinitionRequest.php` | 1 | **B** | inline `$fail(__(...))` في validation rule |
| `Http/Requests/Admin/StoreSectionDefinitionRequest.php` | 1 | **B** | inline `$fail(__(...))` في validation rule |
| `Controllers/Admin/UserController.php` | 1 | **A** | `__('Edit')` كـ label لزر |
| `Actions/Fortify/UpdateUserPassword.php` | 1 | **C** | Fortify boundary |
| `Controllers/Client/SubscriptionThemeController.php` | 1 | **E** | `session('brand_settings_success')` غير معياري |

**ملفات flash-key فقط (لا `__()` — لكن `session('success')` خاطئ):**

| الملف | Count | Category | ملاحظة |
|---|---|---|---|
| `Controllers/Admin/LanguageController.php` | 2 | **A** | hardcoded English — لا `__()` |
| `Controllers/Admin/TemplateReviewController.php` | 3+ | **A** | hardcoded Arabic — لا `__()` |
| `Controllers/Client/DomainController.php` | 6 | **A** | hardcoded Arabic + hardcoded English |
| `Controllers/Client/DomainDnsController.php` | 1 | **A** | hardcoded |
| `Controllers/Client/HomeController.php` | 1 | **A** | hardcoded English |
| `Controllers/Client/InvoiceCheckoutController.php` | 1 | **A** | demo payment message hardcoded |
| `Controllers/Admin/MenuController.php` | 7 | **A** | يستخدم `t()` لكن مفتاح `success` لا يزال خاطئاً |
| `Controllers/Admin/PageController.php` | 5 | **A** | نفس المشكلة |
| `Controllers/Admin/SectionController.php` | 7 | **A** | نفس المشكلة |

---

### تصنيف Blade Views

| المجموعة | الملفات | الاستخدامات | Category |
|---|---|---|---|
| `dashboard/pages/sections/` (section editor) | 39 | 582 | **D→Phase 3** |
| `components/template/sections/` | ~20 | ~104 | **D→Phase 3** |
| `front/sections/` | ~15 | 75 | **D→Phase 3** |
| `tenant/sections/` | ~10 | 41 | **D→Phase 3** |
| `dashboard/management/` | 8 | 107 | **D→Phase 2** |
| `dashboard/section_definitions/` (import views) | ~5 | 37 | **D→Phase 2** |
| `dashboard/users/` | ~3 | 35 | **D→Phase 2** |
| `auth/` | ~5 | 23 | **D→Phase 2** |
| `client/` | ~2 | 4 | **D→Phase 2** |

---

## خطة الإصلاح بالترتيب

---

### Phase 1 — Low Risk (Quick Wins)

**الوصف:** ملفات فيها فقط flash messages بسيطة أو labels واضحة.
العمل الوحيد: `__()` → `t()` + `session('success')` → `session('ok')`.
لا تحتاج تصميم مفاتيح جديدة — معظمها patterns موجودة.

**ملفات PHP:**

| الملف | الإجراء |
|---|---|
| `Models/Plan.php` | `__('Most Popular')` → `t('dashboard.Most_Popular', 'Most Popular')` (مفتاح موجود) |
| `Controllers/Admin/UserController.php` | `__('Edit')` → `t('dashboard.Edit', 'Edit')` |
| `Controllers/Admin/LanguageController.php` | `with('success', '...')` → `with('ok', t('dashboard.Language_Created', ...))` |
| `Controllers/Admin/TemplateReviewController.php` | `with('success', '...')` → `with('ok', t('dashboard.Review_Approved', ...))` x3 |
| `Controllers/Admin/AppearanceController.php` | `with('success', __(...))` × 4 → `with('ok', t('dashboard.Appearance_*', ...))` |
| `Controllers/Admin/HomeController.php` | `with('success', __(...))` × 2 + `with('error', __(...))` × 3 → `t('dashboard.Settings_*', ...)` |
| `Controllers/Admin/MenuController.php` | flash key فقط: `with('success', t(...))` → `with('ok', t(...))` — 7 أماكن |
| `Controllers/Admin/PageController.php` | flash key فقط: `with('success', t(...))` → `with('ok', t(...))` — 5 أماكن |
| `Controllers/Admin/SectionController.php` | flash key فقط: `with('success', t(...))` → `with('ok', t(...))` — 7 أماكن |
| `Controllers/Front/TestimonialSubmissionController.php` | `__('شكراً...')` → `t('site.Testimonial_Received', ...)` + `with('success', ...)` → `with('ok', ...)` |
| `Controllers/Client/DomainController.php` | `with('success', '...')` × 6 → `with('ok', t('client.Domain_*', ...))` |
| `Controllers/Client/DomainDnsController.php` | `with('success', ...)` → `with('ok', t('client.Nameservers_Updated', ...))` |
| `Controllers/Client/HomeController.php` | `with('success', '...')` → `with('ok', t('client.Account_Updated', ...))` |
| `Controllers/Client/InvoiceCheckoutController.php` | `with('success', '...')` → `with('ok', t('client.Demo_Payment_Done', ...))` |

**مفاتيح ترجمة جديدة تُضاف للـ Seeders (تقدير Phase 1): ~25 مفتاح**

---

### Phase 2 — Medium Risk

**الوصف:** ملفات تحتوي `__()` مع variable replacement (`:name`, `:count`)، أو validation messages، أو controllers تُعيد JSON + redirect.

**ملفات PHP:**

| الملف | الإجراء | Risk |
|---|---|---|
| `Controllers/Admin/Management/InvoiceController.php` | 3 flash → `t()` + flash key fix؛ 5 email strings → `t()` (تحقق من context) | Medium |
| `Controllers/Admin/Management/SubscriptionController.php` | 4 flash + fix `toast_success` → `ok`؛ 1 bulk message | Medium |
| `Controllers/Admin/Management/OrderController.php` | fix flash mix: `with('ok', __(...))` × 1 + `with('success', __(...))` × 1 | Low |
| `Controllers/Admin/Management/SubscriptionThemeController.php` | `with('success', __(...))` + `with('brand_settings_success', ...)` → `with('ok', t(...))` | Medium |
| `Controllers/Client/SubscriptionThemeController.php` | `with('brand_settings_success', ...)` → `with('ok', t(...))` | Medium |
| `Controllers/Admin/SectionDefinitionImportExportController.php` | fix `session('success')` + strategy labels + import result message مع variables | Medium |
| `Http/Requests/UpdateDomainDnsRequest.php` | `__()` × 6 في messages() → `t('dashboard.Dns_*', ...)` | Low |
| `Http/Requests/Admin/StoreSectionDefinitionRequest.php` | `$fail(__(...))` → `$fail(t('dashboard.Template_Key_Invalid', ...))` | Low |
| `Http/Requests/Admin/UpdateSectionDefinitionRequest.php` | نفس الإصلاح | Low |
| `Controllers/Client/SubscriptionHomepageEditorController.php` | fix `session('success')` + navigation labels في JSON | Medium |

**Blade Views:**

| المجموعة | الإجراء |
|---|---|
| `dashboard/management/invoices/index.blade.php` (38) | تحويل labels، statuses، column headers |
| `dashboard/management/domains/dns.blade.php` (34) | labels DNS وحقول التحقق |
| `dashboard/management/domains/renew.blade.php` (18) | form labels |
| `dashboard/management/domains/register.blade.php` (17) | form labels |
| `dashboard/section_definitions/import.blade.php` (15) | import UI labels |
| `dashboard/section_definitions/import-preview.blade.php` (22) | preview UI labels |
| `dashboard/users/index.blade.php` (8) | user list labels |
| `dashboard/users/_form.blade.php` (11) | user form labels |
| `dashboard/users/profile.blade.php` (8) | profile page labels |
| `auth/login.blade.php` (12) | auth form labels |
| `auth/client/*.blade.php` (~11) | auth forms |
| `client/site/layouts/*.blade.php` (~4) | client layout labels |

**مفاتيح ترجمة جديدة تُضاف للـ Seeders (تقدير Phase 2): ~60 مفتاح**

---

### Phase 3 — High Risk / Needs Architecture Decision

**الوصف:** ملفات تتطلب قرارات معمارية أو تأثير مباشر على واجهة العميل الحية أو حدود مع packages خارجية.

**لا تبدأ أي ملف في هذه المرحلة قبل إغلاق المسائل المطروحة أدناه.**

| الملف | الإجراء المطلوب | السبب |
|---|---|---|
| `Support/Sections/SectionQueryResolver.php` | `__()` × 15 → `t('site.*')` | Labels تظهر في الأقسام الحية للعميل. تغييرها يؤثر على runtime sections مباشرة. تحتاج مفاتيح `site.*` موجودة في DB قبل التعديل. |
| `Support/Sections/ShellSectionEditorSupport.php` | `__()` × 12 → تحتاج مراجعة | Labels لمحرر Shell — تُمرَّر كـ JSON للـ frontend. تحتاج دراسة هل يجب أن تكون `dashboard.*` أم `site.*`. |
| `Support/Sections/SectionSidebarEditorViewDataFactory.php` | `__()` × 9 → labels للـ sidebar | JSON data للـ frontend editor — نفس المشكلة. |
| `Support/Sections/SectionWorkspacePreviewViewDataFactory.php` | `__()` × 5 → preview UI | JSON data للـ preview context. |
| `Support/Sections/SectionDefinitionFrontendViewDataFactory.php` | `__()` × 6 → error messages | رسائل خطأ للـ dev عند عدم وجود template. هل يجب أن تكون مترجمة أم ثابتة بالإنجليزي؟ |
| `Support/Sections/SectionDefinitionImportService.php` | `__()` × 16 → validation errors | Service layer — يُستدعى من controller. هل تُعرض للمستخدم أم فقط للـ dev؟ تحتاج مفاتيح `dashboard.Import_*` مصمَّمة. |
| `Support/Sections/SectionRenderer.php` | `__()` × 3 → legacy fallback text | Legacy renderer — هل لا يزال active؟ |
| `Notifications/Tenancy/SubscriptionProvisionedNotification.php` | `__()` × 7 → email body | **مشكلة context**: `t()` تعتمد على `app()->getLocale()` — في queued jobs هل اللغة صحيحة؟ |
| `Notifications/Tenancy/AdminSubscriptionProvisioned.php` | `__()` × 1 → email body | نفس مشكلة Context |
| `Livewire/Admin/Sections/FaqSection.php` | `__()` × 1 | TD-8 — Livewire غير مقرر دعمه |
| `Livewire/Admin/Sections/CtaSection.php` | `__()` × 1 | TD-8 — نفس المشكلة |
| `Controllers/Admin/Management/DomainController.php` | `__()` × 28 + `session('success')` × 5 | أعلى ملف من حيث التعقيد — variable replacement في كل مكان + hardcoded Arabic + registrar messages |
| `Controllers/Client/SubscriptionPageEditorController.php` | `__()` × 11 + `session('success')` × 4 | navigation labels في JSON response — تحتاج مفاتيح `client.*` |
| `Controllers/Client/SubscriptionSiteShellEditorController.php` | `__()` × 9 | JSON response labels |

**Blade Views — Phase 3:**

| المجموعة | الاستخدامات | السبب |
|---|---|---|
| `dashboard/pages/sections/create.blade.php` + كل الـ partials (39 ملف) | 582 | Section editor معقد — requires complete t() key design strategy |
| `front/sections/` (15 ملف) | 75 | Public-facing frontend — affects live client pages |
| `tenant/sections/` (10 ملف) | 41 | Client tenant pages — live sites |
| `components/template/sections/` (20 ملف) | ~104 | Template sections — complex, frontend |
| `components/front/` + `components/dashboard/` | ~mixed | Components used across contexts |

---

## الملفات التي يجب عدم لمسها حالياً

| الملف | السبب |
|---|---|
| `Actions/Fortify/PasswordValidationRules.php` | Fortify integration — `__()` هنا مرتبط بـ Fortify's own localization pipeline. تغييرها قد يكسر رسائل validation للـ auth forms. |
| `Actions/Fortify/UpdateUserPassword.php` | نفس السبب — Fortify boundary. |
| `Notifications/Tenancy/SubscriptionProvisionedNotification.php` | `t()` تعتمد على HTTP request locale — في queued jobs قد لا تعمل بشكل صحيح. يحتاج تحقق. |
| `Notifications/Tenancy/AdminSubscriptionProvisioned.php` | نفس مشكلة Context. |
| `Livewire/Admin/Sections/FaqSection.php` | TD-8 — Livewire غير مقرر معمارياً. |
| `Livewire/Admin/Sections/CtaSection.php` | TD-8 — نفس السبب. |
| جميع `front/sections/` و `tenant/sections/` Blade views | يتطلب تصميم مفاتيح `site.*` كاملة + التحقق من أن جميع المفاتيح في DB قبل النشر. |
| `dashboard/pages/sections/` (section editor Blade views) | 582 استخدام — يتطلب استراتيجية تصميم مفاتيح متكاملة قبل البدء. |

---

## مسائل مفتوحة تحتاج إجابة قبل Phase 3

### 1. هل `t()` تعمل في Queued Jobs / Notifications؟

`t()` تستخدم `app()->getLocale()` — في queued jobs يكون locale هو `config('app.locale')` الافتراضي وليس locale المستخدم الحالي.

**الخيارات:**
- A) تمرير `$locale` صراحةً كـ constructor argument للـ Notification وتعيين `app()->setLocale()` داخله
- B) إبقاء `__()` في Notifications وقبوله كـ exception موثق
- C) بناء helper مخصص `t_locale(string $key, string $locale, ?string $default)`

**الحل المقترح:** B مؤقتاً (موثق كـ Exception في هذه الخطة) — حتى يتم بناء حل مناسب في مرحلة لاحقة.

### 2. Section Runtime Labels — `site.*` أم `dashboard.*`؟

Labels في `SectionQueryResolver` مثل `__('Visit')`, `__('Choose Now')`, `__('Buy Now')` تظهر في الأقسام الحية للعميل.

- إذا كانت تُقرأ بواسطة زائر الموقع العام → `site.*`
- إذا كانت تُقرأ فقط في محرر الإدارة → `dashboard.*`

**يحتاج تحقق** قبل تصميم المفاتيح.

### 3. Section Editor JSON Labels — كيف تُترجَم؟

Labels في `ShellSectionEditorSupport` و `SectionSidebarEditorViewDataFactory` تُمرَّر كـ JSON للـ frontend JavaScript.

إذا تغيرت لـ `t()` — هل يرى المحرر النص بالعربي أم بالإنجليزي؟ ما اللغة المستخدمة في هذا السياق؟

**يحتاج تحقق** من `app()->getLocale()` في هذا الـ request context.

---

## هل نحتاج ADR؟

### الإجابة: **لا ADR جديد للتحويل نفسه.**

معيار `t()` موثق في `docs/22-coding-standards.md` القسم 8.1 ويكفي لـ Phase 1 و Phase 2.

### لكن يُنصح بـ ADR-005 لمسألة واحدة:

**ADR-005: Translation Strategy for Non-HTTP Contexts (Notifications & Queued Jobs)**

**السبب:** `t()` مكتوبة لـ HTTP context. استخدامها في queued Notifications يثير تساؤلاً معمارياً حقيقياً:
هل يجب توحيد API الترجمة لكل السياقات، أم قبول استثناء موثق للـ Notifications؟

هذا قرار معماري حقيقي — يستحق ADR.

---

## ترتيب التنفيذ الموصى به

```
Phase 1 (الجلسة القادمة أو القادمتين):
  ├── Flash key fixes فقط: MenuController, PageController, SectionController
  ├── AppearanceController — 4 استخدامات
  ├── HomeController — 5 استخدامات
  ├── LanguageController — 2 استخدامات
  ├── TemplateReviewController — 3 استخدامات
  ├── Plan model — 1 استخدام
  ├── UserController — 1 استخدام
  ├── TestimonialSubmissionController — 2 استخدامات
  ├── Client/DomainController — 6 استخدامات
  ├── Client/DomainDnsController — 1 استخدام
  ├── Client/HomeController — 1 استخدام
  └── Client/InvoiceCheckoutController — 1 استخدام

Phase 2 (جلسات لاحقة):
  ├── FormRequests: UpdateDomainDnsRequest, Store/UpdateSectionDefinitionRequest
  ├── OrderController, SubscriptionController, SubscriptionThemeController (Admin+Client)
  ├── InvoiceController (flash فقط — email strings تُؤجَّل)
  ├── SectionDefinitionImportExportController
  ├── Blade: dashboard/management/ (invoices, domains)
  ├── Blade: dashboard/section_definitions/ (import views)
  ├── Blade: dashboard/users/
  └── Blade: auth/

Phase 3 (بعد إغلاق المسائل المفتوحة):
  ├── SectionQueryResolver (بعد تحديد سياق اللغة)
  ├── ShellSectionEditorSupport + SectionSidebarEditorViewDataFactory
  ├── SectionDefinitionImportService (بعد تصميم مفاتيح Import_*)
  ├── DomainController (الأكثر تعقيداً)
  ├── Client Page/Homepage/Shell Editor Controllers
  ├── Blade: dashboard/pages/sections/ (section editor)
  ├── Blade: front/ و tenant/ و components/
  └── ADR-005 ثم Notifications
```

---

## القواعد الصارمة لكل مرحلة

1. أي `__('string')` بسيطة بدون variables → `t('section.Key_Name', 'string')`
2. أي `__('string :var', ['var' => $v])` → `strtr(t('section.Key_Name', 'string :var'), [':var' => $v])`
3. أي `with('success', ...)` → `with('ok', ...)` بدون استثناء
4. أي `with('toast_success', ...)` أو `with('brand_settings_success', ...)` → `with('ok', ...)`
5. كل مفتاح جديد يُضاف للـ Seeder المناسب **في نفس الجلسة** التي يُضاف فيها
6. لا تُضاف مفاتيح عشوائية — المفتاح يعكس السياق بدقة (`dashboard.Domain_Created` وليس `dashboard.Success`)

---

## المراجع

- `docs/22-coding-standards.md` — Section 8: Translation Standards
- `docs/22-coding-standards.md` — Section 17: Technical Debt (TD-8 Livewire)
- `database/seeders/DashboardTranslationsSeeder.php`
- `database/seeders/SiteTranslationsSeeder.php`
- `app/helpers.php` — `t()` definition
