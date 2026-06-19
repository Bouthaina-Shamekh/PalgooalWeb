# CLAUDE.md — قواعد وملاحظات مشروع PalgooalWeb

---

## ١. اللغات المتعددة (i18n / Localization)

المشروع يدعم **لغات متعددة** (عربي + إنجليزي على الأقل).

### قاعدة مهمة جداً:
**عند أي تعديل على نصوص الواجهة، استخدم دائماً دالة الترجمة ولا تكتب نصاً ثابتاً (hardcoded).**

### صيغة دالة الترجمة:
```blade
{{ t('section.Key_Name', 'Fallback Text') }}
```

**المعامل الأول**: مفتاح الترجمة بصيغة `section.Key_Name`
**المعامل الثاني**: النص الاحتياطي (fallback) بالإنجليزية

### أمثلة صحيحة:
```blade
{{ t('dashboard.Add_Page', 'Add Page') }}
{{ t('site.View_Website', 'View website') }}
{{ t('dashboard.Api_Token', 'API Token') }}
```

### قواعد التسمية:
- أسماء المفاتيح: `Snake_Case` مع أول حرف كبير لكل كلمة
- أسماء الأقسام: lowercase — `dashboard`, `site`, `checkout`, `template`, `common`
- لا تستخدم `__()` أبداً — استخدم `t()` فقط

### دالة t() — تفاصيل تقنية مهمة:
```php
// تقبل معاملين فقط — لا تدعم parameter replacement
t(string $key, ?string $default = null): string
```
**لاستبدال المتغيرات** (مثل `:step`, `:total`) استخدم `strtr()` من الخارج:
```blade
{{ strtr(t('site.Step_Of_Total', 'Step :step of :total'), [':step' => $n, ':total' => $total]) }}
```

### الـ Seeders:
- `SiteTranslationsSeeder` — ترجمات واجهة العميل (site.*)
- `DashboardTranslationsSeeder` — ترجمات لوحة الإدارة (dashboard.*)

لتشغيلها:
```bash
php artisan db:seed --class=SiteTranslationsSeeder
php artisan db:seed --class=DashboardTranslationsSeeder
php artisan cache:clear
```

---

## ٢. UX / Design Language

- الموقع **RTL عربي** بشكل أساسي
- خط **Cairo** مستخدم في الواجهة
- **Tailwind CSS** + Alpine.js + GSAP للتأثيرات
- **Laravel 11** Blade templates
- لوحة الإدارة تستخدم مكوّن `<x-dashboard-layout>`

---

## ٢أ. قاعدة Field Scope — Multi-Tenant Platform

هذه المنصة تخدم آلاف القوالب والمشتركين والمواقع متعددة اللغات. **لا يتم تحديد scope بناءً على احتياجات Palgoals.com فقط.**

### السؤال الفاصل:
> "في قالب يخدم موقعاً بالعربية والإنجليزية والفرنسية، هل يمكن أن تختلف قيمة هذا الحقل بين اللغات؟"
> - **نعم → Translatable**
> - **لا → Shared**

### Translatable دائماً:
`eyebrow`, `title`, `subtitle`, `description`, `highlight_text`, `button_label`, **`button_url`**, `image_alt`, `meta_title`, `meta_description`, أي نص يُقرأ من المستخدم

### Shared دائماً:
`image`, `icon`, `icon_media`, `icon_source`, `image_position`, `button_target`, `layout_style`, `theme_variant`, `background_color`, أي قرار تصميمي/بصري

### لماذا `button_url` هو Translatable:
الروابط تختلف بين اللغات — locale prefixes (`/ar/contact` vs `/en/contact`)، أرقام WhatsApp، landing pages محلية، UTM parameters مترجمة.

**المرجع الكامل:** `docs/FIELD_SCOPE_ARCHITECTURE.md`  
**التطبيق:** `app/Support/Sections/FieldPresetLibrary.php` (تعليق عند كل حقل)

---

## ٣. بنية الملفات الهامة

```
resources/views/
├── dashboard/
│   ├── layouts/partials/nav.blade.php      ← Sidebar لوحة الإدارة
│   └── management/servers/
│       ├── index.blade.php
│       ├── create.blade.php
│       ├── edit.blade.php
│       └── accounts.blade.php
├── client/
│   ├── site/
│   │   ├── dashboard.blade.php             ← Site dashboard للعميل
│   │   └── partials/sidebar.blade.php      ← Sidebar واجهة العميل
│   └── subscriptions/site.blade.php
database/seeders/
├── SiteTranslationsSeeder.php              ← ترجمات site.*
└── DashboardTranslationsSeeder.php         ← ترجمات dashboard.*
app/
├── helpers.php                             ← دالة t()
└── Http/Controllers/Admin/Management/
    └── ServerController.php
```

---

## ٤. أنماط UX معتمدة

### نمط صفحة القائمة (Index):
```blade
{{-- Flash success --}}
@if(session('ok'))
    <div class="alert alert-success ...">{{ session('ok') }}</div>
@endif

{{-- Toolbar: Search + Per-page + Add button --}}
<form method="GET" ...>
    <input type="text" name="search" value="{{ $search ?? '' }}" />
    <select name="per_page" onchange="this.form.submit()">...</select>
    @if($search) <a href="...">Clear</a> @endif
    <a href="create" class="btn btn-primary">Add</a>
</form>

{{-- Empty state --}}
@empty
    <div class="flex flex-col items-center py-16">
        <svg>...</svg>
        @if($search)
            {{-- No results state --}}
        @else
            {{-- Empty state with CTA --}}
        @endif
    </div>
@endforelse
```

### Controller للقائمة مع بحث:
```php
public function index(Request $request)
{
    $search  = $request->get('search');
    $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
        ? (int) $request->get('per_page') : 20;

    $items = Model::latest()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->paginate($perPage)
        ->withQueryString();

    return view('...', compact('items', 'search', 'perPage'));
}
```

### نمط صفحة الإنشاء (Create) — أقسام:
الفورم يُقسَّم لأقسام مرقمة: معلومات أساسية → تفاصيل اتصال → بيانات دخول

```blade
<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 xl:col-span-8"> {{-- الفورم الرئيسي --}}
        <div class="card mb-4">
            <div class="card-header">
                <span class="badge-number">١</span>
                <h5>اسم القسم</h5>
            </div>
            <div class="card-body">...</div>
        </div>
    </div>
    <div class="col-span-12 xl:col-span-4"> {{-- Help Sidebar --}}
        <div class="card sticky top-6">...</div>
    </div>
</div>
```

### Status field — تحذير مهم (PHP loose comparison bug):
```blade
{{-- ❌ خطأ: null == 0 يُرجع true في PHP --}}
<option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>معطل</option>

{{-- ✅ صحيح: استخدم radio buttons أو strict comparison --}}
<input type="radio" name="is_active" value="1"
       {{ old('is_active', '1') === '1' ? 'checked' : '' }} />
<input type="radio" name="is_active" value="0"
       {{ old('is_active') === '0' ? 'checked' : '' }} />
```

### حقول IP / Hostname / API Token:
تُضاف `dir="ltr"` و `font-mono` عليها دائماً:
```blade
<input type="text" name="ip" class="form-control font-mono" dir="ltr" placeholder="192.168.1.1" />
<input type="text" name="hostname" class="form-control font-mono" dir="ltr" placeholder="server.example.com" />
<input type="password" name="api_token" class="form-control" dir="ltr" autocomplete="new-password" />
```

---

## ٥. قاعدة البيانات

- **MySQL** — host: `127.0.0.1:3306`، DB: `palgoalsnewtest1`، user: `root`، no password
- الترجمات مخزنة في جدول `translation_values` عبر موديل `TranslationValue`
- **PHP غير متوفر في sandbox** — كل أوامر `artisan` تُشغَّل على جهاز المستخدم مباشرة

---

## ٦. سجل التغييرات الهامة

### Session: Site Dashboard (واجهة العميل)
- `resources/views/client/site/dashboard.blade.php` — استبدال كل `__()` و hardcoded strings بـ `t('site.*')`
- `resources/views/client/site/partials/sidebar.blade.php` — نفس الإصلاح
- `database/seeders/SiteTranslationsSeeder.php` — إنشاء (40+ ترجمة)

### Session: Admin Servers (لوحة الإدارة)
- `resources/views/dashboard/layouts/partials/nav.blade.php` — إصلاح `__('Users')` → `t('dashboard.Users', ...)`
- `resources/views/dashboard/management/servers/index.blade.php` — إعادة كتابة كاملة: بحث حقيقي + empty state + flash messages
- `resources/views/dashboard/management/servers/create.blade.php` — إعادة كتابة: 3 أقسام + help sidebar + إصلاح bug الحالة
- `resources/views/dashboard/management/servers/edit.blade.php` — إصلاح `Hostname` label
- `app/Http/Controllers/Admin/Management/ServerController.php` — إضافة بحث + per_page للـ index
- `database/seeders/DashboardTranslationsSeeder.php` — إنشاء (80+ ترجمة)

### Session: Admin Plans (لوحة الإدارة)
- `resources/views/dashboard/management/plans/index.blade.php` — إعادة كتابة كاملة:
  - إزالة `wire:model` (Livewire غير موجود) → استبدال بـ GET form حقيقي
  - إضافة بحث server-side يشمل `name` + `slug` + `translations.title`
  - إضافة `session('ok')` flash message (كان مفقوداً)
  - empty state احترافي مع أيقونة + CTA
  - تنسيق السعر: القيمة بارزة + label رمادي صغير
  - `__('Most Popular')` → `t('dashboard.Most_Popular', ...)`
  - كل النصوص مُحوَّلة لـ `t()`
- `app/Http/Controllers/Admin/Management/PlanController.php` — إصلاح `index()`:
  - إضافة بحث + per_page
  - eager loading: `with(['translations', 'category.translations', 'server'])` لإزالة N+1

### Session: Admin Plans Create (لوحة الإدارة)
- `resources/views/dashboard/management/plans/create.blade.php` — إعادة كتابة كاملة:
  - 4 أقسام مرقمة: معلومات أساسية → تسعير → إعدادات → مميزات
  - تخطيط `col-span-8` (فورم) + `col-span-4` (help sidebar)
  - breadcrumb بصيغة `page-header` المعتمدة
  - استبدال `is_active` من checkbox → radio buttons (إصلاح PHP loose comparison bug)
  - كل النصوص مُحوَّلة من hardcoded/`__()` → `t('dashboard.*')`
  - `form-control` بدلاً من classes مخصصة على كل حقل
  - `btn btn-primary` / `btn btn-light` للأزرار
  - help sidebar: نوع الباقة + الباقة المميزة + حزمة السيرفر
  - تبويبات اللغة مُزامنة بين القسم 1 والقسم 4
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 30+ ترجمة جديدة:
  - `dashboard.Add_Hosting_Plan`، `dashboard.Plan_Slug`، `dashboard.Plan_Type`
  - `dashboard.Monthly_Price_USD`، `dashboard.Annual_Price_USD`
  - `dashboard.Active_Available`، `dashboard.Inactive_Hidden`
  - `dashboard.Featured_Plan`، `dashboard.Featured_Badge_Label`
  - `dashboard.Server_Package`، `dashboard.Plan_Features`، `dashboard.Available`
  - `dashboard.Add_Feature`، `dashboard.Remove_Feature`، `dashboard.Create_Plan`
  - `dashboard.Help_Plan_Type`، `dashboard.Help_Featured`، `dashboard.Help_Server_Package`

### Session: Admin Plan Categories (لوحة الإدارة)
- `resources/views/dashboard/management/plan_categories/index.blade.php` — إعادة كتابة كاملة:
  - كل النصوص من hardcoded English → `t('dashboard.*')`
  - إضافة flash messages (session('ok') + session('error'))
  - إضافة بحث server-side + per_page + clear button
  - empty state احترافي مع SVG + dual-state (فارغ / لا نتائج)
  - toggle button: badge-style بدلاً من custom CSS مباشر
- `resources/views/dashboard/management/plan_categories/create.blade.php` — إعادة كتابة كاملة:
  - كل النصوص → `t()`
  - `col-span-8` (فورم) + `col-span-4` (help sidebar)
  - إصلاح `is_active` من checkbox غير مُعالَج → radio buttons مع strict comparison
  - `dir="ltr" font-mono` على حقول slug
  - Tab styling محسّن بـ `border-b-2 border-primary`
- `resources/views/dashboard/management/plan_categories/edit.blade.php` — نفس إصلاحات create
- `app/Http/Controllers/Admin/Management/PlanCategoryController.php`:
  - **إصلاح بق خطير**: `store()` كان يفعل `create([])` بدون `is_active` — أصبح `create(['is_active' => $request->boolean('is_active', true)])`
  - **إصلاح بق**: `update()` لم يكن يحفظ `is_active` — أُضيف `$plan_category->is_active = $request->boolean('is_active', false)`
  - `index()`: إضافة بحث + per_page + تمرير المتغيرات للـ view
  - جميع flash messages: hardcoded strings → `t('dashboard.*')`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 30+ ترجمة جديدة

### Session: Admin Subscriptions Create (لوحة الإدارة)
- `resources/views/dashboard/management/subscriptions/create.blade.php` — إعادة كتابة كاملة:
  - **إصلاح بق خطير**: `$plansArray` كان مُعرَّفاً مرتين بـ attribute خاطئ (`price_cents` و `price`) — الصحيح `monthly_price` عبر `data-price` على الـ `<option>`
  - **إصلاح بق خطير**: 3 `<script>` blocks كانت خارج `</x-dashboard-layout>` — نُقلت داخل اللاي-أوت ودُمجت في block واحد
  - 3 أقسام مرقمة: معلومات الاشتراك → الدومين والسيرفر → المواعيد
  - تخطيط `col-span-8` (فورم) + `col-span-4` (help sidebar)
  - breadcrumb: الرئيسية → الاشتراكات → إضافة اشتراك
  - كل النصوص مُحوَّلة من hardcoded → `t('dashboard.*')`
  - `dir="ltr" font-mono` على حقول `domain_name` و `username`
  - status options: `old('status', 'pending') === 'pending'` (strict comparison, default pending)
  - price auto-fill يقرأ من `data-price` attribute مباشرةً بدلاً من JSON array
  - domain hint text يتغير ديناميكياً حسب نوع الدومين المختار
- `app/Http/Controllers/Admin/Management/SubscriptionController.php`:
  - `store()`: `__()` → `t('dashboard.Subscription_Created', ...)`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 30+ ترجمة جديدة:
  - `dashboard.Add_New_Subscription`، `dashboard.Subscription_Info`
  - `dashboard.Domain_Type`، `dashboard.Domain_Subdomain/Existing/New`
  - `dashboard.Username_Label`، `dashboard.Suggest`، `dashboard.Schedule`
  - `dashboard.Start_Date`، `dashboard.Next_Due_Date`، `dashboard.End_Date`
  - `dashboard.Create_Subscription`، `dashboard.Subscription_Created`
  - `dashboard.Help_Price`، `dashboard.Help_Domain_Type`، `dashboard.Help_Server`، `dashboard.Help_Schedule`

### Session: Admin Subscriptions Index (لوحة الإدارة)
- `resources/views/dashboard/management/subscriptions/index.blade.php` — إعادة كتابة كاملة:
  - إصلاح breadcrumb: `<a href="#">` → `route('dashboard.home')` + `t('dashboard.Home')`
  - استبدال custom CSS classes بـ `card table-card` + `table table-hover`
  - كل النصوص مُحوَّلة من hardcoded/`__()` → `t('dashboard.*')`
  - domain verification badge labels: hardcoded English → `t('dashboard.Domain_*')`
  - status badges: hardcoded Arabic → `t('dashboard.Status_*')`
  - sync result badges: → `t('dashboard.Sync_*')`
  - إضافة per_page selector (10/20/50) مع `onchange="this.form.submit()"`
  - إضافة `onchange="this.form.submit()"` على status/sort/direction dropdowns
  - إضافة clear filters button عند وجود أي فلتر نشط
  - empty state احترافي مع SVG icon + dual-state (فارغ vs. لا نتائج)
  - **إصلاح بق JS خطير**: `document.addEventListener('submit', ...)` كان مُسجَّلاً داخل `document.addEventListener('click', ...)` — يُضاعف handlers مع كل نقرة. تم نقل AJAX handler للمستوى الأعلى
  - `colspan="7"` → `colspan="8"` في empty state (الجدول 8 أعمدة)
  - توحيد Flash messages في مكان واحد (كانت في مكانين)
  - حذف `<script>` خارج `<x-dashboard-layout>` — الآن داخل Layout بشكل صحيح
- `app/Http/Controllers/Admin/Management/SubscriptionController.php` — تحديث `index()`:
  - إضافة `$perPage` parameter (10/20/50)
  - تمرير المتغيرات للـ view بشكل صريح: `q`, `domain`, `status`, `sort`, `direction`, `perPage`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 40+ ترجمة جديدة:
  - `dashboard.Subscriptions_List`، `dashboard.Add_Subscription`، `dashboard.Search_Client_Domain`
  - `dashboard.Status_Active/Pending/Suspended/Cancelled` (status badges)
  - `dashboard.Sync_Success/Failed/Pending/Unknown` (sync result badges)
  - `dashboard.Domain_Platform_Active/Custom_Active/SSL_Pending/DNS_Pending/Verification_Failed`
  - `dashboard.Bulk_*`، `dashboard.Terminate_Confirm`، `dashboard.Provision_Reactivate`
  - `dashboard.Sort_By/Domain/Start_Date`، `dashboard.Ascending/Descending`

### Session: Admin Clients Index (لوحة الإدارة)
- `resources/views/dashboard/clients.blade.php` — إعادة كتابة كاملة:
  - كل النصوص مُحوَّلة من hardcoded English → `t('dashboard.*')`
  - استبدال `btn btn-outline-primary` → `btn btn-light` (معيار المشروع)
  - إضافة clear search button عند وجود `$search`
  - إصلاح per_page: labels مُحوَّلة لأرقام فقط (بدون "X per page" hardcoded)، `Per_Page` label منفصل بـ `t()`
  - status badges: `Active/Inactive/No Login` hardcoded → `t('dashboard.Status_Active')` / `t('dashboard.Client_Inactive')` / `t('dashboard.No_Login')`
  - "Never" hardcoded → `t('dashboard.Never')`
  - empty state: single-state → dual-state (فارغ / لا نتائج بحث) مع SVG احترافي
  - Action titles: "View Details", "Edit Client" إلخ → `t()`
  - Delete confirm: `t('dashboard.Confirm_Delete_Client', ...)`
  - `card table-card` + `table table-hover`
- `resources/views/dashboard/clients/_alerts.blade.php`:
  - **إصلاح بق اتساق**: `session('success')` → `session('ok')` (توحيد مع المشروع)
- `app/Http/Controllers/Admin/ClientController.php`:
  - كل `->with('success', 'hardcoded string')` → `->with('ok', t('dashboard.*', '...'))`
  - 8 flash messages مُحوَّلة: store, update, destroy, impersonate, storeContact, destroyContact, storeNote, destroyNote
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 22 ترجمة جديدة:
  - `dashboard.Clients_List`، `dashboard.Add_Client`، `dashboard.Search_Clients`، `dashboard.Search`
  - `dashboard.Client_Name`، `dashboard.Company`، `dashboard.Phone`، `dashboard.Location`
  - `dashboard.Last_Login`، `dashboard.Joined`، `dashboard.Client_Inactive`، `dashboard.No_Login`، `dashboard.Never`
  - `dashboard.View_Details`، `dashboard.Login_As_Client`، `dashboard.Confirm_Delete_Client`
  - `dashboard.No_Clients`، `dashboard.No_Clients_Desc`، `dashboard.Client_Avatar`
  - `dashboard.Client_Created`، `dashboard.Client_Updated`، `dashboard.Client_Deleted`
  - `dashboard.Client_Impersonated`، `dashboard.Contact_Created`، `dashboard.Contact_Deleted`
  - `dashboard.Note_Created`، `dashboard.Note_Deleted`

### Session: Admin Clients Create/Edit (لوحة الإدارة)
- `resources/views/dashboard/clients/create.blade.php` — إعادة كتابة:
  - breadcrumb مع `t()`
  - تخطيط `col-span-8` (فورم) + `col-span-4` (help sidebar)
  - حذف `submitLabel` hardcoded — الآن يتحكم به `_form` مباشرة
- `resources/views/dashboard/clients/edit.blade.php` — نفس إصلاحات create + breadcrumb موسّع (يتضمن اسم العميل)
- `resources/views/dashboard/clients/_form.blade.php` — إعادة كتابة كاملة:
  - 4 أقسام مرقمة: معلومات أساسية (١) → العنوان (٢) → كلمة المرور (٣) → الصورة (٤)
  - كل النصوص من hardcoded English → `t('dashboard.*')`
  - `status` و `can_login`: من `<select>` → radio buttons مع strict comparison (إصلاح PHP loose comparison bug)
  - `dir="ltr" font-mono` على حقول: email, phone, zip_code, password
  - `btn btn-secondary` → `btn btn-light`
  - `@error()` validation error messages مضافة على كل حقل
  - Help sidebar: Status + Login Access + Password hints
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 35+ ترجمة جديدة:
  - `dashboard.Add_New_Client`، `dashboard.Edit_Client`، `dashboard.Update_Client`، `dashboard.Create_Client`
  - `dashboard.First_Name`، `dashboard.Last_Name`، `dashboard.Company_Name`، `dashboard.Email_Address`، `dashboard.Mobile_Number`
  - `dashboard.Client_Status`، `dashboard.Login_Access`، `dashboard.Can_Login`، `dashboard.No_Login_Access`
  - `dashboard.Address_Info`، `dashboard.City`، `dashboard.Address`، `dashboard.Zip_Code`، `dashboard.Select_Country`
  - `dashboard.Security_Info`، `dashboard.Change_Password_Optional`، `dashboard.New_Password`، `dashboard.Confirm_Password`
  - `dashboard.Password_Hint`، `dashboard.Password_Keep_Hint`
  - `dashboard.Profile_Picture`، `dashboard.Avatar_Label`، `dashboard.Avatar_Hint`، `dashboard.Current_Avatar`
  - `dashboard.Help_Client_Status*`، `dashboard.Help_Login_Access*`، `dashboard.Help_Password*`
  - `dashboard.Choose_From_Media`، `dashboard.No_Image_Selected`، `dashboard.Clear`
  - **الصورة الشخصية**: تم استبدال `<input type="file">` بمكتبة الميديا:
    - `<input type="hidden" name="avatar">` مع زر `btn-open-media-picker` وخاصية `data-store-value="path"`
    - الـ Controller: validation من `nullable|image|max:2048` → `nullable|string|max:500`
    - `buildClientPayload`: من `$request->hasFile('avatar')` → `$request->filled('avatar')` مع `$request->input('avatar')`

### Session: Admin Portfolios Index (لوحة الإدارة)
- `resources/views/dashboard/portfolios/index.blade.php` — إعادة كتابة كاملة:
  - إضافة `page-header` + breadcrumb مع `t()`
  - Flash: `session('success')` + `$errors->has('error')` → `session('ok')` + `session('error')`
  - استبدال `container mx-auto py-6` بـ `grid grid-cols-12`
  - `card table-card` + `table table-hover`
  - إضافة بحث server-side + per_page (10/25/50) + clear button
  - `@foreach` → `@forelse` مع dual-state empty state + SVG image icon
  - `btn btn-warning` + `btn btn-danger` → icon buttons مع hover colors
  - عمود Image محسّن: rounded-lg مع placeholder icon
  - Type + Status: badges ملونة بدلاً من نص خام
  - `colspan="8"` صحيح في empty state
  - `withQueryString()` على الـ paginator
- `app/Http/Controllers/Admin/PortfolioController.php`:
  - `index()`: إضافة بحث (title + type + client) + per_page + `withQueryString()`
  - ترتيب `orderBy('order')` بدلاً من default
  - كل flash messages: `__()` → `t()` و `'success'` → `'ok'`
  - Error flash: `->withErrors(['error' => ...])` → `->with('error', t(...))`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 17 ترجمة:
  - `dashboard.Portfolio_List`، `dashboard.Add_Portfolio`، `dashboard.Search_Portfolios`
  - `dashboard.Portfolio_Image/Title/Type/Status/Client/Order`
  - `dashboard.No_Portfolios`، `dashboard.No_Portfolios_Desc`، `dashboard.Confirm_Delete_Portfolio`
  - `dashboard.Portfolio_Created/Updated/Deleted/Error`

### Session: Admin Portfolios Create/Edit (لوحة الإدارة)
- `resources/views/dashboard/portfolios/create.blade.php` — إعادة كتابة كاملة:
  - `page-header` + breadcrumb مع `t()`
  - تخطيط `col-span-8` (فورم) + `col-span-4` (help sidebar)
  - `alert alert-danger` بدلاً من custom red div
  - كل النصوص → `t('dashboard.*')`
- `resources/views/dashboard/portfolios/edit.blade.php` — نفس إصلاحات create:
  - breadcrumb موسّع يتضمن اسم المعرض
  - إصلاح `$portfolio->translation()?->name` (كان يُسبب خطأ) → `translations->firstWhere()`
  - إزالة emoji `✏️` hardcoded
- `resources/views/dashboard/portfolios/_form.blade.php` — إعادة كتابة كاملة:
  - **حذف Dead Code**: Bootstrap `mediaModal` (lines 352-399) + jQuery CDN + jQuery handler — المشروع يستخدم `x-dashboard.media-picker`
  - **حذف Dead Code**: CSS masonry block `@push('styles')` الثاني (للمودال القديم)
  - **إضافة**: `showSuggestions()` + `handleTypeKeydown()` vanilla JS — كانت مستدعاة لكن غير معرّفة
  - إعادة تنظيم بأقسام مرقمة (٣):
    - **القسم ١**: الصور (`default_image` + `images` media pickers)
    - **القسم ٢**: بيانات المشروع (order, delivery_date, implementation_period_days, client)
    - **القسم ٣**: ترجمات المعرض (language tabs)
  - كل الـ labels من hardcoded Arabic → `t('dashboard.*')`
  - `form-label` على كل الحقول (بدلاً من `block text-sm font-medium`)
  - `btn btn-secondary` → `btn btn-light`
  - إصلاح status select `==` → `===` (strict comparison)
  - Tab styling: `border-b-2 border-primary` (بسيط وموحّد)
  - Tab JS: محافظ على الوظيفة (keyboard nav + localStorage) مع تبسيط الـ classes
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 26 ترجمة جديدة:
  - `dashboard.Add_New_Portfolio`، `dashboard.Edit_Portfolio`
  - `dashboard.Create_Portfolio`، `dashboard.Update_Portfolio`
  - `dashboard.Portfolio_Images_Section`، `dashboard.Portfolio_Default_Image`
  - `dashboard.Portfolio_Choose_Image`، `dashboard.Portfolio_Images`، `dashboard.Portfolio_Choose_Images`
  - `dashboard.Portfolio_Project_Info`، `dashboard.Portfolio_Display_Order`
  - `dashboard.Portfolio_Delivery_Date`، `dashboard.Portfolio_Implementation_Days`، `dashboard.Portfolio_Client_Name`
  - `dashboard.Portfolio_Translations`، `dashboard.Portfolio_Description`
  - `dashboard.Portfolio_Materials`، `dashboard.Portfolio_Link`، `dashboard.Portfolio_Select_Status`
  - `dashboard.Help_Portfolio_Images/Images_Desc/Order/Order_Desc/Translations/Translations_Desc`

### Session: Admin Testimonials (لوحة الإدارة)
- `resources/views/dashboard/testimonials/index.blade.php` — إعادة كتابة كاملة:
  - `page-header` + breadcrumb مع `t()`
  - `session('success')` → `session('ok')` + إضافة `session('error')`
  - `table-striped table-bordered` → `card table-card` + `table table-hover`
  - إضافة بحث server-side (name + major) + per_page (10/25/50) + clear button
  - `@foreach` → `@forelse` مع dual-state empty state + SVG chat icon
  - `btn btn-warning` + `btn btn-danger btn-sm` → icon buttons مع hover colors
  - عمود النجوم: مع أيقونات `ti-star-filled` / `ti-star`
  - `colspan="7"` صحيح في empty state
  - كل النصوص → `t('dashboard.*')`
- `resources/views/dashboard/testimonials/create.blade.php` — إعادة كتابة كاملة:
  - **حذف `<style>` block خطير**: كان يُعيد تعريف `.btn`, `.form-control`, `.btn-primary`, `.btn-secondary`, `.border`, `.modal-content` — يكسر CSS المشروع
  - `page-header` + breadcrumb معياري
  - تخطيط `col-span-8` (فورم) + `col-span-4` (help sidebar)
  - `alert alert-danger` بدلاً من custom div مع gradient
- `resources/views/dashboard/testimonials/edit.blade.php` — إعادة كتابة كاملة:
  - `$testimonial = $testimonial ?? $testimonial ?? null` — حذف التعيين المضاعف الزائد
  - breadcrumb موسّع يتضمن اسم صاحب التقييم
  - نفس layout create: `col-span-8` + `col-span-4` help sidebar
- `resources/views/dashboard/testimonials/_form.blade.php` — إعادة كتابة كاملة:
  - إزالة decorative SVG icons من كل label → `form-label` معيارية
  - إزالة custom `bg-white rounded-2xl border` cards → أقسام مرقمة بـ `card`
  - إصلاح `is_approved` من toggle checkbox غير معياري → radio buttons مع strict comparison `=== '1'`
  - إزالة inline styles من أزرار الحفظ/الإلغاء → `btn btn-light` / `btn btn-primary`
  - كل النصوص → `t('dashboard.*')`
  - Tab JS: محافظ على وظيفة error-highlighting (لون أحمر للتبويبات التي بها خطأ)
  - ٣ أقسام مرقمة: الصورة الشخصية، بيانات الشهادة، ترجمات الشهادة
- `app/Http/Controllers/Admin/TestimonialsController.php`:
  - `index()`: إضافة `$search` + `$perPage` parameters
  - `store()`: `->with('success', ...)` → `->with('ok', t('dashboard.Testimonial_Created', ...))`
  - `update()`: نفس الإصلاح
  - `destroy()`: نفس الإصلاح
  - `withErrors(['error' => __(...)])` → `->with('error', t(...))`
  - `__()` في validation messages → `t()` مع `strtr()` للمتغيرات
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 45+ ترجمة جديدة:
  - `dashboard.Testimonials_List`، `dashboard.Add_Testimonial`، `dashboard.Search_Testimonials`
  - `dashboard.Testimonial_Image/Name/Stars/Feedback/Approved/Pending/Approval/Order`
  - `dashboard.No_Testimonials`، `dashboard.No_Testimonials_Desc`، `dashboard.Confirm_Delete_Testimonial`
  - `dashboard.Testimonial_Created/Updated/Deleted/Error`
  - `dashboard.Add_New_Testimonial`، `dashboard.Edit_Testimonial`، `dashboard.Create_Testimonial`، `dashboard.Update_Testimonial`
  - `dashboard.Testimonial_Featured_Image`، `dashboard.Testimonial_Choose_Image`، `dashboard.Testimonial_Details`
  - `dashboard.Testimonial_Display_Order`، `dashboard.Testimonial_Stars_Count`، `dashboard.Testimonial_Approval_Status`
  - `dashboard.Testimonial_Approved_Label`، `dashboard.Testimonial_Pending_Label`
  - `dashboard.Testimonial_Translations`، `dashboard.Testimonial_Author_Name`، `dashboard.Testimonial_Major`، `dashboard.Testimonial_Text`
  - `dashboard.Help_Testimonial_Image/Order/Approval/Translations` (+ Desc)
  - `dashboard.Field_Required_For_Lang`، `dashboard.Translation_Required`

### Session: Admin Templates (لوحة الإدارة)
- `resources/views/dashboard/templates/index.blade.php`:
  - إصلاح flash key: `session('success')` → `session('ok')`
  - إصلاح error block: `$errors->has('error')` + `$errors->first('error')` → `session('error')`
- `resources/views/dashboard/templates/category-management.blade.php`:
  - إصلاح flash keys: `session('success')` → `session('ok')`
  - إضافة `session('error')` block منفصل
  - إضافة `@if ($errors->any())` block مع `t('dashboard.Category_Validation_Error', ...)`
  - إصلاح 5 استخدامات `__()` متبقية:
    - `__('translations')` → `t('dashboard.Translations_Count', 'ترجمات')`
    - `__('Untitled Category')` → `t('dashboard.Untitled_Category', 'فئة بدون عنوان')`
    - `__('No slug yet')` → `t('dashboard.No_Slug_Yet', 'لا يوجد رابط بعد')`
    - `__('No description yet.')` → `t('dashboard.No_Description_Yet', 'لا يوجد وصف بعد.')`
    - `__('Category translation fields...')` → `t('dashboard.Category_Translation_Fields', ...)`
- `app/Http/Controllers/Admin/TemplateController.php`:
  - `store()` / `update()` / `destroy()`: `->with('success', ...)` → `->with('ok', t('dashboard.Template_Created/Updated/Deleted', ...))`
  - `store()` / `update()`: `->withErrors(['error' => ...])` → `->with('error', t('dashboard.Template_Error', ...))`
  - `storeCategory()`: `->with('ok', t('dashboard.Category_Created', ...))`
  - `updateCategory()`: `->with('ok', t('dashboard.Category_Updated', ...))`
  - `destroyCategory()`: `->with('ok', t('dashboard.Category_Deleted', ...))` و `->with('error', t('dashboard.Category_In_Use_Error', ...))`
  - `templateCategoryMessages()`: كل `__()` → `t()` + إصلاح typo `'ranslations.*'` → `'translations.*'`
  - **إصلاح بق خطير — `edit()`**: لم يكن يُمرر `$languages` → صفحة edit تُعطي 500 error.
    الإصلاح: `$languages = Language::where('is_active', true)->orderBy('id')->get()` + إضافتها لـ `compact()`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 21+ ترجمة:
  - `dashboard.Template_Created/Updated/Deleted/Error`
  - `dashboard.Category_Created/Updated/Deleted/In_Use_Error/Validation_Error`
  - `dashboard.Validation_Name_Required/Max/Slug_Required/Alpha_Dash/Unique/Max/Description_String`
  - `dashboard.Translations_Count`، `dashboard.Untitled_Category`، `dashboard.No_Slug_Yet`
  - `dashboard.No_Description_Yet`، `dashboard.Category_Translation_Fields`
  - إضافة ~85 مفتاح `dashboard.templates.form.*` (نصوص صفحتي create/edit للقوالب):
    - hero/stats: `template_creator_badge`, `create_title`, `edit_title`, `languages`, `categories`, `plans`, إلخ
    - core setup: `category_label`, `plan_label`, `base_price`, `discount_price`, `main_image_*`, إلخ
    - translations: `translations_badge`, `template_name`, `slug_label`, `generate_slug`, `preview_url`, إلخ
    - features/gallery/details: `features_title`, `gallery_title`, `development_tools_title`, `dashboard_title`, `compatible_browsers_title`, `tags_title`, إلخ
    - sidebar/actions: `save_template`, `save_changes`, `back_to_list`, `before_save_rule_*`, إلخ
    - JS i18n: `dashboard.templates.form.js.*` (11 مفتاح للنصوص المُحقونة في JavaScript)

### ملاحظة: نمط `$languages` في create/edit
كل view يستخدم تبويبات اللغة يحتاج `$languages` مُمرَّرة من الـ Controller:
```php
$languages = Language::where('is_active', true)->orderBy('id')->get();
return view('...', compact('template', 'categories', 'languages', 'plans'));
```
**تحقق دائماً** أن كلاً من `create()` و `edit()` يُمرران `$languages` — غيابها يُسبب 500 error صامت.

### Session: Admin Dashboard Home (لوحة الإدارة)
- `resources/views/dashboard/index.blade.php` — إعادة كتابة كاملة (كانت الصفحة فارغة ثم تحتاج تحسين):
  - **إصلاح `page-header` markup**: كان يستخدم `page-header-left`/`page-header-right` → أصبح `page-block > breadcrumb + page-header-title` (النمط المعياري في المشروع)
  - **4 بطاقات KPI**: العملاء + الاشتراكات النشطة + الإيرادات + القوالب/الباقات
    - كل بطاقة: `style="border-top: 3px solid #..."` للتميز اللوني
    - أيقونات `ti ti-*` داخل `div` بـ `inline-style` للألوان (بدلاً من `bg-opacity-10` من Bootstrap التي لم تعمل)
    - زر "عرض الكل" في `card-footer`
    - مؤشر ثانوي: عملاء هذا الشهر / اشتراكات معلقة / فواتير غير مدفوعة / باقات نشطة
  - **جدول آخر الاشتراكات** (`col-span-7`): عميل + باقة + status badge + وقت نسبي
    - status badges: Tailwind-native `bg-green-100 text-green-700` إلخ (لا Bootstrap)
  - **قائمة آخر العملاء** (`col-span-5`): avatar بحرف أول + اسم + إيميل + badge الحالة + وقت نسبي
    - **إصلاح avatar**: `style="width:36px;height:36px;background:#4f46e5;"` مع `text-white` صريح (كان يظهر مربعاً ملوناً بدون حروف)
  - **Quick Actions** (6 روابط): إضافة عميل، إضافة اشتراك، القوالب، الباقات، الشهادات، المحافظ
    - كل رابط: أيقونة ملونة بـ `inline-style` + نص `t()` + `border-dashed border-gray-200`
  - كل النصوص تستخدم `t('dashboard.*')`
- `app/Http/Controllers/Admin/HomeController.php` — تحديث `index()`:
  - إضافة 10 إحصائيات في `$stats` array (clients_total, clients_this_month, subs_active, subs_pending, subs_suspended, subs_total, revenue_paid, revenue_unpaid, plans_active, templates_total)
  - `$recentSubscriptions = Subscription::with(['client', 'plan'])->latest()->limit(6)->get()`
  - `$recentClients = Client::latest()->limit(6)->get()`
  - استخدام `Invoice::paid()->sum('total_cents') / 100` و `Invoice::unpaid()->sum('total_cents') / 100`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 28 ترجمة:
  - `dashboard.Dashboard`، `dashboard.Total_Clients`، `dashboard.This_Month`، `dashboard.No_New_This_Month`
  - `dashboard.Active_Subscriptions`، `dashboard.Total_Subs`، `dashboard.Pending`
  - `dashboard.Paid_Revenue`، `dashboard.Unpaid`، `dashboard.All_Paid`
  - `dashboard.Templates_And_Plans`، `dashboard.Active_Plans`
  - `dashboard.View_All`، `dashboard.View_Templates`، `dashboard.View_Invoices`
  - `dashboard.Recent_Subscriptions`، `dashboard.Recent_Clients`
  - `dashboard.No_Subscriptions_Yet`، `dashboard.No_Clients_Yet`
  - `dashboard.Client`، `dashboard.Plan`، `dashboard.Status`، `dashboard.Date`
  - `dashboard.Quick_Actions`، `dashboard.Templates`، `dashboard.Plans`، `dashboard.Testimonials`، `dashboard.Portfolios`

### ملاحظة: أنماط CSS للـ KPI Cards والـ Avatars
**أيقونات KPI** — لا تستخدم Bootstrap `bg-opacity-*` مع Tailwind (لا يعمل). استخدم `inline-style`:
```blade
<div class="flex items-center justify-center rounded-xl text-indigo-600"
     style="width:48px;height:48px;background:#eef2ff;">
    <i class="ti ti-users" style="font-size:22px;"></i>
</div>
```

**Client Avatar بحرف أول** — لا تستخدم Bootstrap utility classes. استخدم `inline-style` كاملاً:
```blade
<div class="flex items-center justify-center rounded-full font-bold text-white text-sm"
     style="width:36px;height:36px;background:#4f46e5;line-height:1;">
    {{ strtoupper(mb_substr($client->first_name ?? 'U', 0, 1)) }}
</div>
```

**Status Badges** — Tailwind-native (لا Bootstrap `badge`):
```blade
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
    {{ t('dashboard.Status_Active', 'نشط') }}
</span>
```

---

### Session: Admin Plans Edit (لوحة الإدارة)
- `resources/views/dashboard/management/plans/edit.blade.php` — إعادة كتابة كاملة:
  - **breadcrumb معياري**: الرئيسية → الباقات → تعديل الباقة + اسم الباقة في العنوان
  - **4 أقسام مرقمة** بأرقام عربية: معلومات أساسية → التسعير → حزمة السيرفر → ترجمات الباقة
  - **تخطيط** `col-span-8` (فورم) + `col-span-4` (help sidebar) مثل create
  - **إصلاح بق**: `is_active` و`is_featured` من checkbox → radio buttons مع strict comparison على `$plan->is_active ? '1' : '0'`
  - **server package warning**: رسالة واضحة باللون البرتقالي عند عدم وجود باقات (بدلاً من قائمة فارغة)
  - كل النصوص → `t('dashboard.*')`
  - إزالة `__()` الموجودة (Monthly, Annual, Available, إلخ)
- `app/Http/Controllers/Admin/Management/ServerController.php`:
  - **`packages()`**: عند إرجاع `packages:[]` يُضاف `warning` يوضح السبب للمشرف
  - إزالة debug `_raw` المؤقتة من الـ response
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 19 ترجمة:
  - `dashboard.Edit_Plan`، `dashboard.Edit_Hosting_Plan`، `dashboard.Update_Plan`
  - `dashboard.Plan_Translations`، `dashboard.Pricing`، `dashboard.Normal`
  - `dashboard.Auto_Generated`، `dashboard.Error_Loading`، `dashboard.Loading`
  - `dashboard.Feature_Placeholder`، `dashboard.Featured_Badge_Hint`، `dashboard.Plan_Name`
  - `dashboard.Help_Features`، `dashboard.Help_Features_Desc`
  - `dashboard.Server`، `dashboard.Token_Saved`، `dashboard.Token_Saved_Hint`

### ملاحظة: listpkgs للرسيلر
`listpkgs` مع credentials الرسيلر يُعيد فقط الباقات التي أنشأها الرسيلر نفسه (من WHM كـ reseller).
باقات الـ root **غير مرئية** للرسيلر عبر هذا الـ endpoint.
الحل: سجّل الدخول لـ WHM كـ reseller → Packages → Add a Package.

### ملاحظة: WHM API Reseller Privileges
عند استخدام WHM API Token لرسيلر، يجب تفعيل الصلاحيات من WHM (root) → Resellers → Edit Reseller Nameservers and Privileges:
- `view-privs` (Account Summary) ← ضروري لتشغيل `myprivs` — بدونه: "Permission denied"
- `create-user-session` ← للـ SSO
- `list-accts` ← قراءة الحسابات
- `add-pkg` / `edit-pkg` ← إدارة الباقات
- `create-acct` / `kill-acct` / `suspend-acct` / `unsuspend-acct` ← إدارة الحسابات

### Session: Admin Section Definitions Index (لوحة الإدارة)
- `resources/views/dashboard/section_definitions/index.blade.php` — إعادة كتابة كاملة:
  - إصلاح flash: `session('success')` → `session('ok')` + إضافة `session('error')`
  - كل النصوص من `__()` → `t('dashboard.*')`
  - إضافة بحث server-side (label + section_key + category) + per_page (10/25/50) + clear button
  - أزرار الإجراءات: من نصوص → icon buttons (تعديل / إدارة الحقول / حذف) مع hover colors
  - badges ملونة للحالة (نشط/معطل) وللمكتبة (ظاهر/مخفي)
  - empty state احترافي مع SVG + dual-state (فارغ / لا نتائج بحث)
  - select-all checkbox يشغّل جميع checkboxes في نموذج تصدير المحدد
  - أزرار toolbar: Export All + Export Selected + Import JSON + Add Definition
- `app/Http/Controllers/Admin/SectionDefinitionController.php` — تحديث `index()`:
  - إضافة `$search` + `$perPage` parameters
  - `withQueryString()` على الـ paginator
  - تمرير `$search` و`$perPage` للـ view
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 21 ترجمة:
  - `dashboard.Section_Definitions`، `dashboard.Search_Sections`، `dashboard.Add_Definition`
  - `dashboard.Export_All`، `dashboard.Export_Selected`، `dashboard.Import_JSON`
  - `dashboard.Section_Label`، `dashboard.Section_Key`، `dashboard.Template`، `dashboard.Fields`
  - `dashboard.Library`، `dashboard.Sort_Order`، `dashboard.Manage_Fields`
  - `dashboard.Visible`، `dashboard.Hidden`، `dashboard.Sections`
  - `dashboard.Confirm_Delete_Section`، `dashboard.No_Section_Definitions`، `dashboard.No_Section_Definitions_Desc`
  - `dashboard.Select_All`

### Session: Admin Section Definition Fields (لوحة الإدارة)
- `resources/views/dashboard/section_definitions/fields/index.blade.php` — إعادة كتابة كاملة:
  - إصلاح flash: `session('success')` → `session('ok')` + إضافة `session('error')`
  - كل النصوص من `__()` → `t('dashboard.*')`
  - حذف النصوص الوصفية الطويلة للمطورين → استبدال بـ `t('dashboard.Field_Definitions_Desc', ...)`
  - badge status/library: `rounded` → `rounded-full` موحّد مع باقي المشروع
  - زر Edit النصي (`btn btn-sm btn-secondary`) → icon button مع hover
  - **إضافة زر Delete** بأيقونة `ti-trash` (route موجود لكن لم يكن في الـ view)
  - empty state: SVG icon احترافي + `t('dashboard.No_Fields_Yet', ...)`
  - `trans_choice()` → عدد رقمي + `t('dashboard.Fields', ...)`
  - `card table-card` + `p-0` على card-body + `table-hover mb-0`
  - زر "Save Field Order" → `btn btn-primary` مع أيقونة `ti-device-floppy`
- `app/Http/Controllers/Admin/SectionDefinitionController.php`:
  - `store()`: `with('error', __(...))` → `with('error', t('dashboard.Section_Def_Create_Error', ...))`
  - `destroy()`: خطأ → `with('error', t(...))` + نجاح → `with('ok', t(...))`
  - `redirectAfterSave()`: `with('success', __(...))` × 2 → `with('ok', t(...))`
  - `formViewData()`: `editorModeOptions` → `t('dashboard.Dynamic', ...)`
  - `templateOptions()`: `__('Unknown Template')` → `t('dashboard.Unknown_Template', ...)`
- `app/Http/Controllers/Admin/SectionDefinitionFieldController.php`:
  - `index()`: `__('General')` في groupBy → `t('dashboard.General', 'عام')`
  - `store()`: `with('success', __(...))` → `with('ok', t('dashboard.Field_Created', ...))`
  - `update()`: نفس الإصلاح → `t('dashboard.Field_Updated', ...)`
  - `reorder()`: نفس الإصلاح → `t('dashboard.Field_Reordered', ...)`
  - `destroy()`: نفس الإصلاح → `t('dashboard.Field_Deleted', ...)`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 38 ترجمة جديدة:
  - `dashboard.Field_Definitions`، `dashboard.Field_Definitions_Desc`، `dashboard.Back_To_Definition`
  - `dashboard.Add_Field`، `dashboard.Create_First_Field`، `dashboard.No_Fields_Yet`، `dashboard.No_Fields_Desc`
  - `dashboard.Save_Field_Order`، `dashboard.Fields_Reorder_Hint`
  - `dashboard.Field_Sort`، `dashboard.Field_Label`، `dashboard.Field_Key`، `dashboard.Field_Type`، `dashboard.Field_Scope`، `dashboard.Field_Required`
  - `dashboard.Translatable`، `dashboard.Shared`، `dashboard.Required`، `dashboard.Optional`، `dashboard.Validation`
  - `dashboard.Dynamic`، `dashboard.Custom_Preset`، `dashboard.Visible_In_Library`، `dashboard.Hidden_From_Library`
  - `dashboard.No_Template_Selected`، `dashboard.General`، `dashboard.Unknown_Template`
  - `dashboard.Confirm_Delete_Field`، `dashboard.Field_Created`، `dashboard.Field_Updated`، `dashboard.Field_Reordered`، `dashboard.Field_Deleted`
  - `dashboard.Section_Def_Save_Fields`، `dashboard.Section_Def_Updated`
  - `dashboard.Section_Def_Create_Error`، `dashboard.Section_Def_Delete_Error`، `dashboard.Section_Def_Deleted`

### Session: Admin Section Definition Fields — Create/Edit (لوحة الإدارة)
- `resources/views/dashboard/section_definitions/fields/create.blade.php` — إعادة كتابة:
  - breadcrumb: كل `__()` → `t('dashboard.*')`
  - `__('Create Field')` → `t('dashboard.Create_Field', ...)`
  - `__('Create Field Definition')` → `t('dashboard.Create_Field_Definition', ...)`
- `resources/views/dashboard/section_definitions/fields/edit.blade.php` — إعادة كتابة:
  - breadcrumb: كل `__()` → `t('dashboard.*')`
  - confirm delete: `__('Delete this field definition?')` → `t('dashboard.Confirm_Delete_Field', ...)`
- `resources/views/dashboard/section_definitions/fields/form.blade.php` — إعادة كتابة كاملة:
  - كل `__()` → `t('dashboard.*')` (30+ استخدام)
  - إضافة `dir="ltr" font-mono` على حقل key والـ textareas التقنية (validation_rules, options, settings)
  - إضافة `cursor-pointer` على toggle labels
  - زر "Delete Field" + "Save/Update" أُضيفت لهما أيقونات Tabler
  - sidebar: `sticky top-6` لتثبيت في الشاشات الطويلة
  - JS: placeholder label → `t('dashboard.Field_Label', ...)` + remove button → Tabler icon بدلاً من `×`
- `resources/views/dashboard/section_definitions/fields/partials/repeater-item-schema-editor.blade.php` — إعادة كتابة:
  - `__('Key')` / `__('Label')` / `__('Type')` / `__('Options')` / `__('Required')` / `__('Translatable')` → `t('dashboard.*')`
  - `__('Remove sub-field')` → icon button `ti-trash`
  - `__('Add Sub-field')` → `t('dashboard.Add_Sub_field', ...)` مع أيقونة `ti-plus`
  - `dir="ltr" font-mono` على حقول key والـ options textarea
  - `__('Repeater sub-fields are invalid...')` → `t('dashboard.Repeater_Schema_Error', ...)`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 33 ترجمة جديدة:
  - `dashboard.Create_Field`، `dashboard.Create_Field_Definition`، `dashboard.Edit_Field`، `dashboard.Edit_Field_Definition`
  - `dashboard.Delete_Field`، `dashboard.Update_Field`
  - `dashboard.Field_Metadata`، `dashboard.Field_Metadata_Desc`
  - `dashboard.Field_Key_Hint`، `dashboard.Field_Key_Lowercase_Hint`، `dashboard.Field_Label_Placeholder`
  - `dashboard.Field_Group`، `dashboard.Field_Group_Hint`
  - `dashboard.Translatable_Hint`، `dashboard.Required_Hint`
  - `dashboard.Repeater_Sub_fields`، `dashboard.Repeater_Sub_fields_Desc`
  - `dashboard.Default_Value`، `dashboard.Default_Value_Desc`، `dashboard.Default_Value_Hint`
  - `dashboard.Shared_Default_Value`، `dashboard.Default_Value_Placeholder`، `dashboard.Default_Value_For`
  - `dashboard.Validation_And_Options`، `dashboard.Validation_Desc`، `dashboard.Validation_Rules_Hint`
  - `dashboard.Options`، `dashboard.Options_Hint`، `dashboard.Settings_Hint`
  - `dashboard.Remove_Sub_field`، `dashboard.Add_Sub_field`، `dashboard.Repeater_Schema_Error`

### Session: Admin Section Definitions Create/Edit (لوحة الإدارة)
- `resources/views/dashboard/section_definitions/edit.blade.php` — إعادة كتابة كاملة:
  - كل `__()` → `t('dashboard.*')`
  - `session('success')` → `session('ok')` + إضافة `session('error')`
  - تخطيط `col-span-8` (فورم داخل card) + `col-span-4` (help sidebar) — النمط المعياري للمشروع
  - إزالة زر `btn btn-light-primary` المنفصل → أزرار في `card-footer` للـ sidebar
  - الـ sidebar يحتوي: حفظ التعديلات + حفظ وإدارة الحقول + إدارة الحقول + إلغاء
  - نقل `<form>` ليلف الـ `grid` بأكمله (الفورم + الـ sidebar داخل نفس الـ form)
- `resources/views/dashboard/section_definitions/create.blade.php` — إعادة كتابة كاملة:
  - نفس التخطيط `col-span-8` + `col-span-4`
  - الـ sidebar: إنشاء التعريف ومتابعة + إلغاء
  - كل `__()` → `t()`
- `resources/views/dashboard/section_definitions/form.blade.php` — إعادة كتابة كاملة:
  - إزالة قسم أزرار الإجراءات (نُقلت للـ sidebar في edit/create)
  - كل `__()` → `t('dashboard.*')` (15+ استخدام في الـ Blade + 6 في JS)
  - `dir="ltr" font-mono` على حقلَي `key` و `template_key`
  - `cursor-pointer` على labels الـ checkboxes
  - إصلاح `__('Renderer candidate: :view', ['view' => ...])` → Blade: `t('dashboard.Def_Renderer_Candidate_Label', ...) . ' ' . $view`
  - JS: متغيرات النصوص مُعرَّفة كـ `const` في أعلى الـ IIFE بدلاً من `{{ __() }}` مبعثرة في الكود
  - إزالة `btn btn-light-primary` → الأزرار في sidebar
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 35 ترجمة جديدة:
  - `dashboard.Name`، `dashboard.Category`
  - `dashboard.Edit_Definition`، `dashboard.Edit_Section_Definition`
  - `dashboard.Create_Definition`، `dashboard.Create_Section_Definition`
  - `dashboard.Definition_Information`، `dashboard.Def_Workflow_Title`، `dashboard.Def_Workflow_Desc`
  - `dashboard.Def_Name_Placeholder`، `dashboard.Def_Key_Hint`، `dashboard.Def_Description_Placeholder`، `dashboard.Def_Category_Placeholder`
  - `dashboard.Preview_Image`، `dashboard.Def_Preview_Image_Hint`
  - `dashboard.Def_Template_Key`، `dashboard.Def_Template_Key_Hint`
  - `dashboard.Def_Code_Override`، `dashboard.Def_Convention_Key`، `dashboard.Def_No_Template`، `dashboard.Def_No_Template_Desc`
  - `dashboard.Def_View_Resolution`، `dashboard.Def_Renderer_Candidate_Label`
  - `dashboard.Editor_Mode`، `dashboard.Def_Editor_Mode_Hint`
  - `dashboard.Def_Dynamic_Workflow_Title`، `dashboard.Def_Dynamic_Workflow_Desc`
  - `dashboard.Def_Active_Hint`، `dashboard.Def_Visible_Hint`
  - `dashboard.Update_Definition`، `dashboard.Update_And_Manage_Fields`، `dashboard.Create_Definition_Continue`
  - `dashboard.Def_Sidebar_Hint`، `dashboard.Def_Create_Sidebar_Hint`

### Session: Blade Editor Fix — POST→GET Redirect & Truncated File

#### المشاكل التي تم إصلاحها:

**١. الملف `edit.blade.php` كان مبتوراً عند السطر 635**
- السبب: حفظ غير مكتمل في جلسة سابقة
- الأعراض: Monaco لا يُهيأ، أزرار الكتابة لا تعمل (لا event listeners)
- الإصلاح: إعادة بناء الملف كاملاً بـ `head -611 + append` من bash

**٢. زر "كتابة الملف" يُعيد 405 Method Not Allowed**
- السبب الجذري: document root في Apache هو `public_html/` وليس `public_html/public/`
  - طلب POST إلى `/admin/section-definitions/{id}/write-blade`
  - Apache يُعيد توجيهه (301) إلى `/public/admin/...`
  - الـ 301 redirect يُحوّل POST → GET
  - GET على route يقبل POST فقط → 405 MethodNotAllowed
- الإثبات:
  ```javascript
  // fetch إلى /public/ URL يُرجع 200 OK:
  fetch('https://palgoals.wpgoals.com/public/admin/section-definitions/15/write-blade', { method: 'POST', ... })
  // → {"ok":true, "message":"تم كتابة ملف Blade على الـ disk بنجاح."}
  ```
- الإصلاح في `doWrite()`:
  ```javascript
  // ❌ خطأ (كان موجوداً): يُزيل /public/ بدلاً من إضافتها
  var url = writeForm.action.replace(/\/public\//g, '/');
  
  // ✅ صحيح: يُضيف /public/ إذا لم تكن موجودة
  var url = writeForm.action;
  if (!/\/public\//.test(url)) {
      url = url.replace(/(https?:\/\/[^\/]+)\//, '$1/public/');
  }
  ```
- استخدام `redirect: 'manual'` في fetch لكشف `opaqueredirect` (علامة على أن redirect حدث)

**٣. AMD conflict: feather/Swal/Sortable undefined بعد تحميل Monaco**
- السبب: Monaco `loader.js` يضبط `window.define.amd = true`؛ UMD libraries تكتشف AMD وتُسجّل كـ AMD modules بدلاً من window globals
- الإصلاح بمرحلتين:
  ```javascript
  // قبل loader.js: احفظ AMD loader الأصلي وأزله
  window.__amd_define_backup  = window.define;
  window.__amd_require_backup = window.require;
  window.define = undefined; window.require = undefined;
  
  // بعد loader.js: أخفِ define.amd حتى تتجاهله UMD scripts
  window.__monacoRequire = window.require;
  try { window.define.amd = false; } catch (e) {}
  
  // قبل __monacoRequire.config(): أعد تفعيل AMD لـ Monaco modules
  try { window.define.amd = {}; } catch (e) {}
  ```

#### الملفات المُعدَّلة:
- `resources/views/dashboard/section_definitions/edit.blade.php` — إعادة بناء كامل (863 سطر):
  - إصلاح URL bug في `doWrite()`
  - إكمال Monaco init + event listeners + scaffold + insert-at-cursor + fullscreen + zoom
  - AMD isolation صحيح (ثلاث مراحل: قبل loader / بعد loader / داخل require callback)

#### ملاحظة مهمة — Apache Redirect على السيرفر:
```
السيرفر: palgoals.wpgoals.com
Document root: /home/palgoalswpgoals/public_html/    (وليس .../public_html/public/)
Laravel public: /home/palgoalswpgoals/public_html/public/

أي fetch/form POST إلى https://palgoals.wpgoals.com/admin/...
سيُعاد توجيهه (301) إلى https://palgoals.wpgoals.com/public/admin/...
→ الـ 301 يُحوّل POST إلى GET → 405 على routes التي تقبل POST فقط

الحل: استخدم /public/ prefix في fetch URLs دائماً:
url.replace(/(https?:\/\/[^\/]+)\//, '$1/public/')
```

### Session: Admin Pages Create/Edit (لوحة الإدارة)
- `resources/views/dashboard/pages/create.blade.php` — إعادة كتابة:
  - Flash: `session('success')` → `session('ok')` + إضافة `session('error')`
  - Errors: custom red div → `alert alert-danger`
  - الـ grid ينتقل من الـ `<form>` نفسه إلى `<div>` داخله (نمط صحيح)
- `resources/views/dashboard/pages/edit.blade.php` — إعادة كتابة:
  - نفس إصلاحات create
  - breadcrumb موسّع يُظهر اسم الصفحة في العنوان
- `resources/views/dashboard/pages/partials/form.blade.php` — إعادة كتابة كاملة:
  - **تقسيم إلى قسمين مرقمين**:
    - **القسم ١** — محتوى الصفحة: تبويبات `nav nav-tabs` لكل لغة
    - **القسم ٢** — SEO والمشاركة: تبويبات مستقلة لكل لغة (meta_title, meta_description, meta_keywords, og_image)
  - كل الحقول: `w-full border p-2 rounded` → `form-control` + `form-label`
  - Slug inputs: `dir="ltr" font-mono` + auto-generate من العنوان
  - `is_active`: radio buttons مع `form-check` (بدلاً من custom radio styling)
  - Sidebar: `sticky top-6` + `form-check` للـ checkbox + `btn btn-primary` للحفظ + زر إلغاء
  - بطاقة تلميحات خفيفة في أسفل الـ sidebar
  - `<script>` يبقى في نهاية الـ partial (CKEditor + tabs + slug normalizer) — لا يحتاج `@push`
- `resources/views/dashboard/pages/index.blade.php` — إصلاح:
  - Flash key: `session('success')` → `session('ok')` + إضافة `session('error')`
  - `<script>` خارج `</x-dashboard-layout>` → نُقل داخل `@push('scripts')` / `@endpush`
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 35 ترجمة جديدة:
  - `dashboard.All_Pages`، `dashboard.Add_Page`، `dashboard.Edit_Page`، `dashboard.No_Pages_Yet`
  - `dashboard.Page_Title`، `dashboard.Page_Content`، `dashboard.Slug`، `dashboard.Slug_Hint`، `dashboard.Content_Hint`
  - `dashboard.SEO_Meta`، `dashboard.Meta_Title`، `dashboard.Meta_Description`، `dashboard.Meta_Keywords`، `dashboard.Open_Graph_Image_URL`
  - `dashboard.Short_description_for_search_engines`، `dashboard.Aim_for_50_160_characters_*`، `dashboard.Separate_keywords_*`
  - `dashboard.Publishing_Options`، `dashboard.Builder_Type`، `dashboard.Sections_Builder`، `dashboard.Visual_Builder_Archived_Hint`
  - `dashboard.Published`، `dashboard.Draft`، `dashboard.Publish`، `dashboard.Update`، `dashboard.Publish_Date`
  - `dashboard.Homepage`، `dashboard.Make_Homepage`، `dashboard.Homepage_Hint`، `dashboard.Current_Homepage`
  - `dashboard.Page_Help_Title`، `dashboard.Page_Help_1/2/3`
  - `dashboard.Confirm_Delete_Page_Title/Text`، `dashboard.Yes_Delete_Page`، `dashboard.Action_Cannot_Be_Undone`

### Session: Write-Blade Toast UI (لوحة الإدارة)
- `resources/views/dashboard/section_definitions/edit.blade.php` — إضافة نظام toast احترافي:
  - `showWriteToast(type, title, detail)`: نافذة إشعار fixed-position أعلى اليمين
  - أخضر مع ✓ للنجاح (تختفي بعد 3.5 ثانية) / أحمر مع ⚠ للخطأ (تبقى 6 ثوانٍ)
  - شريط تقدم `scaleX(0)` بـ CSS linear transition يتقلص مع الوقت
  - `mouseenter` يوقف الـ timer، `mouseleave` يستأنفه بالوقت المتبقي
  - Close button بـ `addEventListener('click', ...)` بعد إضافة الـ DOM (تجنب quote-nesting bug)
  - `dismissToast()`: `translateX(120%)` + `opacity:0` ثم `removeChild` بعد 400ms
  - استبدال كل استدعاءات `Swal.fire()` في `doWrite()` بـ `showWriteToast()`

### ملاحظة: توليد Slug تلقائي في صفحات متعددة اللغة
```blade
{{-- slug-source: يُطلق auto-generate --}}
<input data-slug-source="{{ $langCode }}" ...>

{{-- slug-input: يستقبل القيمة المُولَّدة --}}
<input data-slug-input data-lang="{{ $langCode }}" dir="ltr" class="form-control font-mono" ...>
```
```javascript
// إذا عدَّل المستخدم الـ slug يدوياً → data-touched="1" → يوقف التوليد التلقائي
input.addEventListener('change', () => { if (input.value !== '') input.dataset.touched = '1'; });
titleInput.addEventListener('input', () => {
    if (slugInput.dataset.touched === '1') return;
    slugInput.value = normalizeSlug(titleInput.value);
});
```

### Session: Section Definitions Developer Docs (توثيق النظام)
- `docs/section-definitions.md` — **إنشاء** ملف توثيق شامل (507 سطر):
  - شرح المشكلة التي يحلّها النظام (أقسام ثابتة vs ديناميكية)
  - المعمارية: طبقتان — Definition Layer (SectionDefinition + Field) / Content Layer (Section + Translation)
  - جدول حقول SectionDefinition: section_key, label, category, blade_source, editor_mode, blade_written_at
  - أنواع الحقول: text/textarea/richtext/url/media/number/boolean/select/repeater
  - نطاق الحقل: `shared` (قيمة واحدة لجميع اللغات) / `translatable` (قيمة لكل لغة)
  - رحلة Render الكاملة: `SectionRenderer::render()` → `renderDefinitionDriven()` → `SectionDefinitionRuntimeResolver` → `SectionDefinitionFrontendViewDataFactory` → Blade view
  - Convention-based view resolution: `template_key: hero_main` + `category: hero` → `front.sections.hero.hero_main`
  - تسلسل كتابة Blade من Monaco: base64 encode → POST → decode PHP → `SectionTemplateFileWriter::write()` → disk
  - خطوات إنشاء تعريف جديد خطوة بخطوة (7 خطوات كاملة مع أمثلة كود)
  - بنية الملفات الكاملة للنظام
  - أنماط استخدام `$fields` داخل Blade view (text/repeater/boolean/media)
  - FAQ: base64 encoding، فرق section_key و template_key، متى يُستخدم legacy render
  - جدول مراجع سريع لجميع الكلاسات والمسارات

### Session: ADR-005 Wave 1 — Media FK Columns (توحيد تخزين الوسائط)

#### الملفات الجديدة:
- `app/Support/Media/MediaPathNormalizer.php` — **مُنشأ جديد**: normalizer موحّد يستبدل `normalizeMediaPath()` الخاصة في HomeController وAppearanceController
- `database/migrations/2026_06_16_200001_add_media_id_columns_wave1.php` — **migration جديد**: 9 أعمدة FK nullable على 3 جداول
- `docs/ADR_005_WAVE1_IMPLEMENTATION_REPORT.md` — تقرير التنفيذ الكامل
- `docs/ADR_005_PHASE05_WAVE1_BACKFILL_AUDIT.md` — نتائج Audit ما قبل التنفيذ

#### الملفات المعدّلة:
- `app/Models/Section.php` — حذف Ghost Relation `image()` (الحقل `image_id` غير موجود في DB)
- `app/Models/Client.php` — إضافة `avatar_media_id` لـ fillable + علاقة `avatarMedia()` + helper `resolvedAvatarPath()`
- `app/Models/Portfolio.php` — إضافة `default_image_media_id` + علاقة `defaultImageMedia()` + helper `resolvedDefaultImagePath()`
- `app/Models/GeneralSetting.php` — إضافة 7 أعمدة `*_media_id` + 7 علاقات + 7 helpers `resolved*Path()`
- `app/Http/Controllers/Admin/ClientController.php` — Dual-write في `buildClientPayload()`
- `app/Http/Controllers/Admin/PortfolioController.php` — Dual-write في `store()` + `update()`
- `app/Http/Controllers/Admin/HomeController.php` — Dual-write في 3 مواضع: `importGeneralSettings()`, `updateGeneralSettings()`, `autoSaveGeneralSettings()`
- `routes/console.php` — أمر Backfill: `php artisan adr005:backfill-wave1 [--dry-run]`
- `app/Providers/AppServiceProvider.php` — Eager-load 7 media relations في `GeneralSetting::with([...])->first()`
- `resources/views/dashboard/portfolios/index.blade.php` — تحديث لاستخدام `resolvedDefaultImagePath()`
- `resources/views/dashboard/clients/show.blade.php` — تحديث لاستخدام `resolvedAvatarPath()`

#### قواعد ADR-005 المستقاة:
- **الأعمدة القديمة لا تُحذف في Wave 1** — فقط تُضاف الأعمدة الجديدة (no-drop policy)
- **Dual-write**: كل write يكتب في العمود القديم (path) والجديد (media_id) معاً
- **Backfill** يجب تشغيله بعد `migrate`: `php artisan adr005:backfill-wave1`
- **`services.icon`** مُستثنى من Wave 1 — يحتوي على مسارات assets ثابتة
- **helpers `resolved*Path()`** تُعطي الأولوية للـ FK relation ثم تسقط للعمود القديم

#### نمط Read Switch المعتمد:
```php
// في الـ Model:
public function resolvedDefaultImagePath(): ?string
{
    return $this->defaultImageMedia?->file_path ?? $this->getRawOriginal('default_image') ?? null;
}
```
```blade
{{-- في الـ View: --}}
@php $imagePath = $portfolio->resolvedDefaultImagePath(); @endphp
@if ($imagePath)
    <img src="{{ asset('storage/' . $imagePath) }}" ...>
@endif
```

#### ملاحظة: AppServiceProvider Cache
`GeneralSetting` مُحمَّل مع all 7 media relations ليعمل `resolved*Path()` بدون N+1 queries:
```php
GeneralSetting::with(['logoMedia', 'darkLogoMedia', 'stickyLogoMedia',
    'darkStickyLogoMedia', 'adminLogoMedia', 'adminDarkLogoMedia', 'faviconMedia'])->first()
```
عند تعديل أي setting يجب `php artisan cache:clear` لتحديث الـ cached object.

---

### Session: ADR-005 Wave 3 — JSON Media Fields (portfolios + appearance settings)

#### الملفات المُعدَّلة:

**`app/Models/Portfolio.php`**:
- إضافة `resolvedGalleryImages(): array` — يُرجع URLs كاملة للصور
  - يكتشف التنسيق تلقائياً: IDs (جديد) أو paths (قديم)
  - New format: `[7, 12]` → `Media::whereIn('id', $ids)` → URLs
  - Old format: `["media/img.jpg"]` → `asset('storage/' . $path)` مباشرة

**`app/Http/Controllers/Admin/PortfolioController.php`**:
- إضافة `resolveImagesToIds(mixed $input): ?string` — يحفظ IDs مباشرة كـ JSON array
- تحديث `store()` و `update()`: `resolveMediaIdsToPaths($validated['images'])` → `resolveImagesToIds(...)` (ADR-005 Wave 3)
- `resolveMediaIdsToPaths()` تبقى لـ `default_image` (Wave 1 dual-write)

**`app/Http/Controllers/Admin/AppearanceController.php`**:
- إضافة `normalizeMediaPathAsObject($value): ?array` — يُرجع `['id' => X, 'path' => '...']`
- إضافة `normalizeMediaPathListAsObject($value): array` — يُرجع `['ids' => [...], 'paths' => [...]]`
- `updateHeaderSettings()`: `logo_override` → `normalizeMediaPathAsObject()`
- `updateFooterSettings()`: `logo_override` + `payment_logos` → نفس الطريقة

**Front-end Views** — Compatibility Readers:
- `front/layouts/headers/purple_topbar.blade.php`: `$logoOverrideRaw = ...; $logoPath = is_array(...) ? $raw['path'] : $raw`
- `front/layouts/footers/palgoals_marketing.blade.php`: نفس النمط لـ `logo_override` + `payment_logos`
- `front/pages/portfolio.blade.php`: استبدال 20 سطراً يدوياً بـ `$portfolio->resolvedGalleryImages()`

**Admin Views** — Pre-fill Compatibility:
- `dashboard/appearance/header.blade.php`: `$purpleLogoPath` يستخرج `path` من الكائن الجديد
- `dashboard/appearance/footer.blade.php`: نفسه + `$palgoalsPaymentLogoPaths` من `paths` key

**Artisan**:
- `routes/console.php`: إضافة `adr005:backfill-wave3 {--dry-run}` يغطي 3 targets

**Docs**:
- `docs/ADR_005_WAVE3_IMPLEMENTATION_REPORT.md` — تقرير التنفيذ الكامل
- `public/__adr005_wave3_validate.php` — سكريبت التحقق (**احذفه بعد الاستخدام**)

#### أوامر يجب تشغيلها بعد النشر:
```bash
php artisan adr005:backfill-wave3 --dry-run   # معاينة
php artisan adr005:backfill-wave3             # تطبيق
php artisan cache:clear                        # تحديث الـ cache
# ثم افتح: https://palgoals.wpgoals.com/public/__adr005_wave3_validate.php
# واحذف الملف بعد التحقق
```

#### استراتيجية التخزين الثلاثية:
| الحقل | التنسيق الجديد | السبب |
|-------|---------------|--------|
| `portfolios.images` | `[7, 12, 15]` (IDs فقط) | صفحة تفصيلية — query واحد مقبول |
| `*.logo_override` | `{id: 5, path: "..."}` | header/footer — كل طلب → zero DB lookup |
| `*.payment_logos` | `{ids: [...], paths: [...]}` | نفس السبب |

#### نمط Compatibility Reader (للاستخدام في أي view جديد):
```php
// قراءة logo_override آمنة من كلا التنسيقين:
$logoOverrideRaw = $variantSettings['logo_override'] ?? null;
$logoPath = is_array($logoOverrideRaw) ? ($logoOverrideRaw['path'] ?? null) : $logoOverrideRaw;

// قراءة payment_logos آمنة من كلا التنسيقين:
$raw = $variantSettings['payment_logos'] ?? [];
$paths = (is_array($raw) && isset($raw['paths'])) ? $raw['paths'] : (is_array($raw) ? $raw : []);
```

### ملاحظة: زر اللغات (Lang Switcher) — النمط الاحترافي
CSS `.lang-switcher` + `.lang-tab-btn` يُستخدم في `pages/partials/form.blade.php`:
```css
.lang-switcher { display: inline-flex; gap: 6px; padding: 4px; background: #f1f5f9; border-radius: 12px; }
.lang-tab-btn  { padding: 6px 14px; border-radius: 9px; background: transparent; transition: all .18s; }
.lang-tab-btn.active { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.10); font-weight: 600; }
.lang-tab-btn.has-error { color: #dc2626 !important; }
```
كل زر يعرض: flag emoji + lang name + lang code uppercase
يُفعَّل بـ JS `makeSwitcher('data-lang-tab', 'data-lang-panel')` — helper قابل لإعادة الاستخدام

### Session: Section Templates Library (أتمتة إنشاء الأقسام)

- `app/Support/Sections/SectionTemplateLibrary.php` — **إنشاء**: static library بـ 6 templates جاهزة
  - Hero / Features Grid / Content Showcase / CTA Banner / FAQ / Testimonials
  - كل template: `definition` + `fields[]` + `blade_stub`
  - Field scopes: مُصنَّفة وفق قاعدة Multi-Tenant Field Scope Architecture
- `app/Http/Controllers/Admin/SectionDefinitionController.php` — إضافة:
  - `createFromTemplate()`: عرض صفحة الاختيار مع duplicate detection
  - `storeFromTemplate()`: إنشاء SectionDefinition + جميع الحقول في Transaction واحدة + حفظ `blade_stub` في `blade_source`
- `routes/dashboard.php` — إضافة:
  - `GET /section-definitions/from-template` → `from_template`
  - `POST /section-definitions/from-template` → `store_from_template`
- `resources/views/dashboard/section_definitions/from-template.blade.php` — **إنشاء**: صفحة template picker
  - Grid of colored cards (indigo/violet/emerald/rose/amber/cyan)
  - Badge عند وجود section_key مسبقاً (تمنع إعادة الإنشاء)
  - Field badges + repeater sub-fields preview
  - Confirm dialog قبل الإرسال
- `resources/views/dashboard/section_definitions/index.blade.php` — إضافة زر "⚡ من قالب" في toolbar + empty state
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 17 ترجمة (`dashboard.Section_Tpl_*`, `dashboard.Field_Type_*`)
- `docs/SECTION_TEMPLATE_LIBRARY_REPORT.md` — **إنشاء**: تقرير التنفيذ الكامل

#### الهدف المحقق:
وقت إنشاء سكشن جديد: **15–23 دقيقة → أقل من 30 ثانية**

#### إضافة Template جديد:
فقط أضف entry لـ `ALL_TEMPLATES` في `SectionTemplateLibrary` — لا ملف آخر يحتاج تغيير.

#### Phase 2 (مستقبلاً): كتابة ملف Blade إلى disk تلقائياً عبر `SectionTemplateFileWriter`.

### Session: Component Library System (لوحة الإدارة)

- `app/Support/Sections/ComponentLibrary.php` — **إنشاء**: 8 components قابلة لإعادة الاستخدام
  - `intro` (eyebrow, title, subtitle), `description`, `cta` (button_label, button_url, button_target)
  - `image` (image, image_alt, image_position), `features` (repeater), `highlight` (highlight_text)
  - `faq` (faqs repeater), `testimonials` (testimonials repeater), `seo` (meta_title, meta_description)
  - Methods: `all()`, `get(key)`, `keys()`, `resolveFields(componentKeys[], extraFields[])`
  - `resolveFields()`: يدمج الحقول بالترتيب، يُزيل التكرار بـ first-occurrence-wins، يُعيّن sort_order تسلسلياً

- `app/Support/Sections/SectionTemplateLibrary.php` — **إعادة هيكلة v2** (Component Architecture):
  - Templates تستخدم `components[]` + `extra_fields[]` بدلاً من `fields[]` المتكرر
  - إضافة `resolveTemplateFields(key)`: يتحقق من `components` أولاً (v2)، يسقط لـ `fields` (v1 backward-compat)
  - `cta-banner` مثال: `components: ['intro','cta']` + `extra_fields: [background_image]`
  - لا تغيير في `blade_stubs` — محفوظة كما هي

- `app/Http/Controllers/Admin/SectionDefinitionController.php` — تحديث `storeFromTemplate()`:
  - إضافة `use App\Support\Sections\ComponentLibrary`
  - استبدال `foreach $template['fields']` بـ `SectionTemplateLibrary::resolveTemplateFields($key)`
  - التوافق الكامل: القديم (v1 inline fields) والجديد (v2 components) يعملان بنفس المنطق

- `resources/views/dashboard/section_definitions/from-template.blade.php` — **إعادة كتابة**:
  - عرض **component chips** ملونة بدلاً من raw field badges
  - كل chip: أيقونة + اسم الـ component + عدد حقوله
  - `Extra Fields` indicator عند وجود `extra_fields` خاصة بالـ template
  - عدد الحقول الإجمالي مع نص "مُدمجة تلقائياً، بدون تكرار"
  - Repeater sub-fields preview محفوظ كما هو

- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 14 ترجمة جديدة:
  - `dashboard.Components`، `dashboard.Extra_Fields`، `dashboard.Extra_Fields_Short`
  - `dashboard.Auto_Merged`، `dashboard.Total_Fields`
  - `dashboard.Comp_Intro/Description/Cta/Image/Features/Highlight/Faq/Testimonials/Seo`

- `docs/COMPONENT_LIBRARY_ARCHITECTURE.md` — **إنشاء**: توثيق شامل:
  - الفرق الجوهري بين الطبقات الثلاث: Field Presets / Components / Section Templates
  - منطق `resolveFields()` مع مثال كامل (intro+cta+image → 9 حقول مُرقَّمة)
  - جدول الـ components + الـ templates مع عدد الحقول
  - Field Scope compliance لكل حقل مع السبب
  - كيفية إضافة Component جديد (خطوة واحدة فقط)
  - كيفية إضافة Template جديد مع مثال كامل
  - Backward compatibility flow

#### قاعدة جديدة: Component Architecture
عند إضافة template جديد → استخدم `components[]` دائماً. لا تُعيد تعريف `eyebrow/title/subtitle/button_*` يدوياً.
```php
'my-section' => [
    'components'   => ['intro', 'cta'],      // ← يجلب 6 حقول تلقائياً
    'extra_fields' => [/* حقول خاصة فقط */],
    'definition'   => [...],
    'blade_stub'   => '...',
]
```

### Session: UI/UX from-template + Task #130 Repeater Partials

#### from-template.blade.php — تحسينات UX
- **إصلاح حساب `$existingCount`**: كان يحسب جميع SectionDefinitions في DB → أصبح يحسب فقط القوالب التي section_key-ها موجود (مطابقة فعلية)
- **إصلاح البانر الإحصائي**: استبدال `d-flex gap-3` بـ `inline-flex` حاوية واحدة مع `flex-direction:row` و `align-self:stretch` للفواصل — يمنع التكديس العمودي في RTL
- **تحسين Card Header**: استبدال الـ flex المنفصل بـ colored header zone (خلفية ملونة خفيفة حسب لون القالب) مع الأيقونة داخل صندوق أبيض + العنوان والوصف بجانبها
- الإصلاح يعني: القوالب التي تم إنشاؤها بالفعل تُظهر العدد الصحيح (2 مُنشأ / 4 متاح / 6 إجمالي)

#### Task #130 — QW3 Warning + __() → t() في Repeater Partials

**`app/Models/Sections/SectionDefinitionField.php`**:
- تعليق FIELD_TYPE_REPEATER: `"deferred to Phase 5B"` → `"implemented in Phase 5C (dynamic-editor/fields/repeater.blade.php + repeater-item.blade.php)"`

**`resources/views/dashboard/section_definitions/edit.blade.php`** — QW3 تحديث:
- تحويل من `amber` (تحذير) → `blue` (معلومات)
- النص: "غير متاح بعد" → "محرر حقول Repeater متاح عند تحرير محتوى الصفحة (الصفحات ← الأقسام)."
- المفتاح: `dashboard.Repeater_Editor_Available`

**`resources/views/dashboard/pages/sections/partials/dynamic-editor/fields/repeater.blade.php`** — 5 استبدالات:
- `__('Shared')` → `t('dashboard.Shared', ...)`
- `__('This repeater is edited once...')` → `t('dashboard.Repeater_Shared_Note', ...)`
- `__('Add Item')` × 2 → `t('dashboard.Repeater_Add_Item', ...)`
- `__('No sub-fields are defined yet...')` → `t('dashboard.Repeater_No_Schema', ...)`
- `__('No items yet...')` → `t('dashboard.Repeater_Empty', ...)`

**`resources/views/dashboard/pages/sections/partials/dynamic-editor/fields/repeater-item.blade.php`** — 15 استبدالاً:
- Item / New Item / Duplicate item / Remove item
- Choose From Media Library / Choose a file from the media library...
- Tabler Icon / SVG From Media / Inline SVG
- Select an option / Choose From Icon Library / Use the icon library...
- Icon Library / Upload SVG / Clear

**`database/seeders/DashboardTranslationsSeeder.php`** — إضافة 21 ترجمة جديدة:
- `dashboard.Repeater_Editor_Available/Not_Available`
- `dashboard.Repeater_Shared_Note/Add_Item/No_Schema/Empty`
- `dashboard.Repeater_Item/New_Item/Duplicate_Item/Remove_Item`
- `dashboard.Choose_From_Media/Repeater_Media_Hint`
- `dashboard.Repeater_Icon_Tabler/SVG_Media/Inline_SVG/Select_Option/Choose_Icon/Icon_Hint/Icon_Library/Upload_SVG`

### Session: Auto Blade Generator — Phase 6 (لوحة الإدارة)

#### الملفات الجديدة:
- `app/Support/Sections/BladeGenerator.php` — **إنشاء**: كلاس PHP للتوليد الذكي
  - `generate(SectionDefinition $def): string` — يُنتج @php block + HTML section
  - `stats(SectionDefinition $def): array` — يُرجع `{fields, repeaters, components, component_names}`
  - `COMPONENT_FIELD_GROUPS` — maps field_key → component (intro/cta/image/description/highlight/seo)
  - `detectComponentGroups()` — يُجمّع الحقول بترتيب canonical حسب Component
  - Repeater يقرأ `item_schema` عبر `repeaterItemSchema()` ويُولّد @foreach مع sub-fields
  - `TAG_BY_KEY` + `CLASS_BY_KEY` — HTML دلالي لـ eyebrow/title/subtitle/description
- `docs/AUTO_BLADE_GENERATOR_ARCHITECTURE.md` — توثيق كامل (Roadmap + Field Types + Component Awareness)

#### الملفات المُعدَّلة:
- `routes/dashboard.php` — إضافة `GET /{sectionDefinition}/blade-scaffold` → `blade_scaffold`
- `app/Http/Controllers/Admin/SectionDefinitionController.php`:
  - إضافة `use BladeGenerator`
  - إضافة `bladeScaffold()` → يرجع JSON: `{scaffold, stats}`
- `resources/views/dashboard/section_definitions/edit.blade.php`:
  - إضافة `scaffoldUrl` لـ `window.__sdEditorData`
  - استبدال scaffold button handler بـ `openScaffoldPreview()` يُنادي السيرفر
  - إضافة Preview Modal: header + loader + stats bar + code `<pre>` + footer buttons
  - Modal controls: Insert into Editor / Copy / Close / Escape / Backdrop click
- `database/seeders/DashboardTranslationsSeeder.php` — إضافة 6 ترجمات:
  - `dashboard.Blade_Generator_Title/Subtitle/Loading/Insert/Copy`
  - `dashboard.Close`

#### معمارية الـ Scaffold المُولَّد:
```
@php block: كل حقل → سطر واحد مُناسب لنوعه
  - media    → SectionFrontendMediaResolver::resolve(...)
  - boolean  → !empty($data['key'])
  - repeater → is_array(...) ? ... : []
  - text/url → trim((string) ($data['key'] ?? ''))

HTML section: حقول مُجمَّعة بـ component sections
  {{-- Intro (eyebrow / title / subtitle) --}}
  {{-- CTA (button) --}}
  {{-- Image --}}
  {{-- ungrouped: repeaters + unrecognized keys --}}
```

#### UI Flow:
1. المطور يفتح صفحة edit لـ SectionDefinition (تبويب Blade)
2. يضغط "⚡ Scaffold من الحقول"
3. JS يُنادي `GET /admin/section-definitions/{id}/blade-scaffold`
4. Modal يظهر مع: Stats Bar (X حقل · Y repeater · Z component) + Code Preview
5. زر "إدراج في المحرر" يضع الـ scaffold في Monaco
6. زر "نسخ الكود" للنسخ المباشر

#### Roadmap:
- **Phase 1** ✅ Preview Only (هذه الجلسة)
- **Phase 2** (مستقبلاً) كتابة الملف مباشرة إلى disk
- **Phase 3** توليد Snippets منفصلة
- **Phase 4** توليد Section Package كامل

### Session: Section Package Generator — إصلاح Template Binding Bug

**المشكلة**: بعد إنشاء Section Package (مثل `features_grid`) ظهر `template_key` فارغاً في صفحة edit. والأهم: `SectionDefinitionRuntimeResolver::resolveRenderableDefinition()` تُعيد `null` لأن `hasPrimaryTemplate()` تُعيد `false`، مما يجعل الـ section **غير قابل للرندر** حتى لو كان ملف Blade موجوداً على disk.

**السبب الجذري**: `SectionPackageGenerator` (و `storeFromTemplate()` في الـ controller) كانا يُنشئان `SectionDefinition` + `SectionDefinitionField` فقط — بدون إنشاء record في `section_templates` أو ربطه بالـ pivot `section_definition_template`.

`SectionDefinition::primaryTemplateKey()` ← `primaryTemplate()` ← pivot ← `section_templates`. بدون الربط = `null`.

**الإصلاح**:
- `app/Support/Sections/SectionPackageGenerator.php` — إضافة Steps 4c+4d داخل `DB::transaction()` بعد حلقة الحقول:
  - `SectionTemplate::firstOrCreate(['template_key' => $sectionKey], [...])`
  - `$definition->templates()->sync([$sectionTemplate->id => ['sort_order' => 0]])`
- `app/Http/Controllers/Admin/SectionDefinitionController.php` — نفس الإصلاح في `storeFromTemplate()`
- `docs/SECTION_PACKAGE_GENERATOR_REPORT.md` + `docs/SECTION_PACKAGE_GENERATOR_ARCHITECTURE.md` — توثيق الإصلاح + Step 4

**القاعدة**: `template_key = section_key` للـ library-generated definitions بالاتفاقية. يضمن:
1. `primaryTemplateKey()` تُعيد `'features_grid'` (ليس null)
2. `SectionTemplateRegistry::resolve('features_grid', 'features')` → `front.sections.features.features_grid`
3. `FileStatusResolver::conventionViewName()` → نفس الـ view (من `section_key`)

**ملاحظة معمارية مهمة**: `section_templates` (جدول) هو قاموس مفاتيح الـ templates المُسجَّلة للرندر — منفصل تماماً عن `templates` (كتالوج قوالب الموقع للعملاء). الـ Model هو `App\Models\Sections\Template` مع `$table = 'section_templates'`.

### Session: BladeGenerator — إصلاح Bug في شرط Repeater

**المشكلة**: الـ scaffold المُولَّد يحتوي على:
```blade
@if (!$empty($features))
```
وهذا كود PHP غير صالح — `$empty` ليست دالة.

**السبب**: في `renderRepeater()` سطر 419، كان الكود:
```php
$lines[] = "{$indent}@if (!\$" . "empty(\${$key}))";
// النتيجة: @if (!$empty($features))  ❌
```
التسلسل `"\$"` + `"empty(..."` يُنتج `$empty(...)` وليس `empty(...)`.

**الإصلاح** — `app/Support/Sections/BladeGenerator.php`:
```php
$lines[] = "{$indent}@if (!empty(\${$key}))";
// النتيجة: @if (!empty($features))  ✓
```

**ملاحظة Technical Debt**: `renderRepeaterSubField()` لحقول media داخل repeater يُولّد:
```blade
<img src="{{ $feature['icon_media'] ?? '' }}" alt="">
```
إذا كانت `icon_media` تخزّن Media ID (رقم) فالـ `src` سيكون خاطئاً. يجب مستقبلاً استخدام `SectionFrontendMediaResolver::resolve()` لحقول media داخل الـ repeaters.
