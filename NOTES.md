# Palgoals Website / Templates Platform – NOTES

هذا الملف مخصص لتوثيق قواعد العمل في المشروع:
- كيف نسمي الملفات والراوتات.
- كيف ننظم الصفحات والسكشنات.
- ما هي القواعد الأساسية قبل إضافة أو تعديل أي كود جديد.

> هدفنا: مشروع نظيف، منظم، يمكن تطويره بسهولة بدون لخبطة أو تكرار.
## 1. قواعد التسمية (Naming Conventions)

### 1.1. الراوتات (Routes)

- الصفحات العامة (CMS pages):
  - الراوت الرئيسي للصفحة الرئيسية: `frontend.home`
  - أي صفحة ثابتة/ديناميكية (CMS): `frontend.page.show` مع باراميتر `slug`.
  - مثال: صفحة القوالب الرئيسية هي صفحة CMS slug = `templates`
    - نصل لها عبر: `route('frontend.page.show', 'templates')`

- القوالب (Templates):
  - عرض قالب واحد: `template.show`  → `/templates/{slug}`
  - عرض المعاينة: `template.preview` → `/templates/{slug}/preview`
  - مراجعات القوالب:  
    - `frontend.templates.reviews.store` → `POST templates/{template}/reviews`

- الكاشير / الدفع (Checkout):
  - `checkout`              → `/checkout/client/{template_id}`
  - `checkout.cart`         → `/checkout/cart`
  - `checkout.cart.process` → `POST /checkout/cart/process`
  - `checkout.process`      → `POST /checkout/client/{template_id?}/process/{plan_id?}`

- البحث عن الدومينات:
  - صفحة البحث: `domains.page`  → `/domains`
  - API فحص التوفر: `domains.check` → `/api/domains/check`

> قاعدة عامة:  
> - أسماء الراوتات في الفرونت تبدأ بـ: `frontend.` عندما تكون عامة.  
> - أسماء الراوتات في لوحة التحكم تبقى كما في `dashboard.php`.

---

### 1.2. الكنترولرز (Controllers)

- فرونت:
  - `App\Http\Controllers\Frontend\TemplateController`
  - `App\Http\Controllers\Frontend\CheckoutController`
  - `App\Http\Controllers\Frontend\CartController`
  - `App\Http\Controllers\Frontend\TestimonialSubmissionController`

- داشبورد:
  - داخل `Dashboard\Management\...` مثل:
    - `Dashboard\Management\DomainSearchController`
    - `Dashboard\TemplateController` (لإدارة القوالب من لوحة التحكم)

> قاعدة:
> - أي كنترولر يتعامل مع واجهة الزوار → يوضع في مساحة الأسماء `Frontend`.
> - أي كنترولر إدارة / موظفين → في `Dashboard` أو `Dashboard\Management`.

## 2. تنظيم الصفحات والسكشنات

### 2.1. الصفحات (Page model)

- الصفحة الرئيسية تُحدد في قاعدة البيانات بـ:
  - `pages.is_home = true`
  - `pages.is_active = true`

- أي صفحة عامة (مثلاً: "من نحن" – "القوالب" – "الخدمات"):
  - تُعرّف في جدول `pages` مع `slug` ثابت (مثل: `about`, `templates`, `services`).
  - تعرض عن طريق الراوت:
    - `route('frontend.page.show', $page->slug)`

### 2.2. ملف العرض العام للصفحات

- الملف الرئيسي لعرض الصفحات هو:
  - `resources/views/tamplate/page.blade.php`
- هذا الملف:
  - يستقبل `$page`
  - يعرض السكشنات حسب ما هو موجود في قاعدة البيانات (`sections` + `sections.translations`)
  - **ممنوع** نكتب منطق عمل ثقيل داخل Blade (Queries إضافية).  
    - أي منطق معقد يكون داخل الكنترولر أو كلاس خدمة (Service).

### 2.3. سكشنات خاصة (مثل صفحة القالب)

- صفحة عرض القالب الواحد:
  - الملف: `resources/views/tamplate/template-show.blade.php`
  - المتغيرات التي يجب أن يوفّرها الكنترولر:
    - `$template`
    - `$translation`
    - `$reviews` (مجموعة جاهزة، بدون استعلامات إضافية من داخل Blade)
    - `$reviewsCount`
    - `$finalPrice`, `$basePrice`, `$hasDiscount`, `$endsAt` (أو تُحسب في Blade لكن مرة واحدة فقط في الأعلى).

> قاعدة:
> - السكشنات العامة التي تتكرر (Features, Hero, Templates List, Testimonials) يُستحسن تحويلها لاحقًا إلى Blade components أو partials.  
> - الآن نلتزم بعدم إنشاء ملفات جديدة، لكن نلتزم بتنظيف ما هو موجود وعدم تكرار الكود.

## 3. قواعد سير العمل (Workflow)

### 3.1. قبل أي تعديل كبير

1. تأكد من أن المشروع يعمل بدون أخطاء:
   - `php artisan serve`
   - فتح الهوم + صفحة القوالب + صفحة قالب واحد + صفحة الدومينات.
2. إذا كان التعديل كبير (مثلاً تغيير منطق عرض القوالب):
   - نكتب وصف للتعديل في هذا الملف تحت عنوان: "التعديل رقم X – تاريخ اليوم".

### 3.2. أثناء التعديل

- **ممنوع**:
  - إنشاء موديلات جديدة بدون حاجة حقيقية.
  - إنشاء Routes جديدة لنفس الصفحة الموجودة سابقًا.
- **مسموح**:
  - تحسين الكنترولر الحالي.
  - تحسين Blade الحالي.
  - إضافة Trait/Service فقط لو كان عندنا تكرار فعلي في أكثر من مكان.

### 3.3. بعد التعديل

- اختبار يدوي للنقاط التالية:
  - عرض الهوم (Home) يعمل.
  - `/templates` (صفحة قائمة القوالب – CMS page).
  - `/templates/{slug}` عرض قالب واحد.
  - `/templates/{slug}/preview` المعاينة.
  - `/checkout/cart` و `/checkout/client/{template_id}`.
  - `/domains` + فحص دومين واحد.
- كتابة سطر أو سطرين في NOTES.md تحت قسم "سجل التغييرات" يشرح:
  - ماذا عدّلنا؟
  - هل استبدلنا منطق قديم بمنطق جديد؟

## 4. سجل التغييرات (Mini Changelog)

### 2025-11-16

- ترتيب ملف `routes/web.php`:
  - إضافة اسم `frontend.home` و `frontend.page.show`.
  - تثبيت مسار `/templates/{slug}` لعرض القالب عبر `TemplateController@show`.
  - توحيد أسماء الراوت الخاصة بالـ Checkout والـ Domains.
- توثيق قواعد التسمية وتنظيم الصفحات في هذا الملف NOTES.md.
### 2025-11-19

- اعتماد جدول `subscriptions` كوحدة تينانت رسمية وإضافة الحقول الخاصة بالمحتوى والتفعيل (template, engine, subdomain، بيانات cPanel، حالة provisioning).
- بناء خدمة التفعيل `TenantProvisioningService` مع Job `ProvisionSubscription`، وربطها بعملية الشراء (Checkout) وبزر إعادة التفعيل في لوحة الإدارة، مع إشعارات البريد والـNotifications.
- إضافة حقل `plan_type` لتمييز خطط Multi-Tenant عن خطط الاستضافة/WordPress، وتحديث نماذج لوحة الإدارة (`plans/create`, `plans/edit`) ليتحكم بها الفريق من الواجهة.
- إنشاء نظام المحتوى المخصص للتينانت (`subscription_pages`, `subscription_sections`, الترجمات) وخدمة `TemplateBlueprintService` مع blueprint افتراضي تحت `resources/blueprints/default.php`.
- توفير صفحة إدارة الاشتراك داخل لوحة العميل (`client/subscriptions/{subscription}`) تعرض الصفحات والأقسام وتسمح بتعديل العناوين والمحتوى (نص عادي أو JSON اختياري).

#### التطويرات القادمة

- واجهة عرض فعلية لصفحات التينانت على الدومين/السّب دومين (public frontend) باستخدام بيانات `subscription_pages`.
- أدوات تحرير أسهل داخل لوحة العميل (Page Builder مبسط مع معاينة مباشرة) وربما مكتبة عناصر جاهزة.
- دعم أعضاء الفريق وصلاحياتهم لكل تينانت (مثلاً دعوة محررين).
- إمكانية اختيار Blueprint مختلف لكل قالب، مع استيراد/تصدير المحتوى.
- دمج خطوات استضافة WordPress (عند `plan_type = hosting`) مع تثبيت القالب والإضافات تلقائياً بعد إنشاء cPanel.
