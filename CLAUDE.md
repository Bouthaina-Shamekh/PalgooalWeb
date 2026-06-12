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
