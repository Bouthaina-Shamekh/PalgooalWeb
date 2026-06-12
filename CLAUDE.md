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
