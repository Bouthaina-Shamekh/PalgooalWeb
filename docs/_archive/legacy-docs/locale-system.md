# نظام تعدد اللغات (Locale System) — PalGoals

> **الهدف من هذا المستند:** توثيق شامل لنظام اللغات في المشروع، يُستخدم مرجعاً للمطورين والذكاء الاصطناعي لفهم كيفية عمل كل مكوّن وكيفية التعامل معه.

---

## فهرس المحتويات

1. [نظرة عامة على المعمارية](#نظرة-عامة-على-المعمارية)
2. [جدول قاعدة البيانات: `languages`](#جدول-قاعدة-البيانات-languages)
3. [جدول قاعدة البيانات: `translation_values`](#جدول-قاعدة-البيانات-translation_values)
4. [Middleware: SetLocale](#middleware-setlocale)
5. [دالة `t()` — نظام الترجمة الأساسي](#دالة-t--نظام-الترجمة-الأساسي)
6. [دالة `current_dir()` — اتجاه النص](#دالة-current_dir--اتجاه-النص)
7. [دالة `available_locales()` — اللغات المتاحة](#دالة-available_locales--اللغات-المتاحة)
8. [دالة `page_slug()` — ترجمة سلاق الصفحة](#دالة-page_slug--ترجمة-سلاق-الصفحة)
9. [LocaleController — تبديل اللغة](#localecontroller--تبديل-اللغة)
10. [LanguageController — إدارة اللغات (Admin)](#languagecontroller--إدارة-اللغات-admin)
11. [TranslationValueController — إدارة الترجمات (Admin)](#translationvaluecontroller--إدارة-الترجمات-admin)
12. [View Components: محوّل اللغة](#view-components-محوّل-اللغة)
13. [AppServiceProvider — المتغيرات العامة للـ Views](#appserviceprovider--المتغيرات-العامة-للـ-views)
14. [GeneralSetting — اللغة الافتراضية](#generalsetting--اللغة-الافتراضية)
15. [نموذج الترجمة في الـ Models (Translation Tables)](#نموذج-الترجمة-في-الـ-models-translation-tables)
16. [مسارات الـ Routes الخاصة باللغة](#مسارات-الـ-routes-الخاصة-باللغة)
17. [تسلسل عمل النظام (Flow)](#تسلسل-عمل-النظام-flow)
18. [أنماط الاستخدام في Blade](#أنماط-الاستخدام-في-blade)
19. [الكاش (Cache)](#الكاش-cache)
20. [إضافة لغة جديدة — خطوات عملية](#إضافة-لغة-جديدة--خطوات-عملية)

---

## نظرة عامة على المعمارية

النظام **مخصص بالكامل** ولا يعتمد على ملفات اللغة التقليدية الخاصة بـ Laravel (`resources/lang/`). بدلاً من ذلك يعمل على طبقتين:

**الطبقة الأولى — ترجمة النصوص الثابتة:**
تُخزَّن في جدول `translation_values` بقاعدة البيانات، وتُجلب عبر دالة `t('key')` مع كاش تلقائي.

**الطبقة الثانية — ترجمة محتوى الكيانات (Models):**
كل نموذج قابل للترجمة يحتوي على جدول `*_translations` مرتبط به، يُخزَّن فيه المحتوى بكل لغة بشكل مستقل (slug، title، meta...).

```
HTTP Request
    ↓
[Middleware: SetLocale]  ← يحدد لغة الجلسة
    ↓
app()->getLocale()       ← اللغة النشطة طوال دورة الطلب
    ↓
t('key')                 ← يجلب الترجمة من DB مع كاش
$model->translations()   ← يجلب ترجمة الكيان (Page, Template...)
```

---

## جدول قاعدة البيانات: `languages`

| العمود       | النوع      | الوصف                                      |
|--------------|------------|--------------------------------------------|
| `id`         | bigint PK  | المعرّف                                    |
| `name`       | string     | اسم اللغة بالإنجليزية (مثال: Arabic)       |
| `native`     | string     | الاسم المحلي (مثال: العربية)               |
| `code`       | string(10) | كود اللغة (مثال: `ar`, `en`) — يُحفظ lowercase |
| `flag`       | string     | رمز أو كلاس الراية (اختياري)              |
| `is_rtl`     | boolean    | هل اللغة RTL؟                             |
| `is_active`  | boolean    | هل اللغة مفعّلة ومتاحة للمستخدمين؟        |

**الموديل:** `App\Models\Language`

---

## جدول قاعدة البيانات: `translation_values`

| العمود   | النوع   | الوصف                                               |
|----------|---------|-----------------------------------------------------|
| `id`     | bigint  | المعرّف                                             |
| `key`    | string  | مفتاح الترجمة مثل `frontend.hero.title`            |
| `locale` | string  | كود اللغة مثل `ar`, `en`                           |
| `value`  | text    | قيمة الترجمة النصية                                |

**تصنيف المفاتيح (Key Convention):**

| البادئة       | الاستخدام                             |
|---------------|---------------------------------------|
| `frontend.*`  | نصوص الواجهة الأمامية للموقع         |
| `dashboard.*` | نصوص لوحة التحكم                     |
| بدون بادئة    | نصوص عامة مشتركة                     |

**مثال على مفاتيح:**
```
frontend.nav.home          → "الرئيسية" / "Home"
frontend.hero.title        → "نبني موقعك الاحترافي"
dashboard.plans.title      → "الخطط"
```

**الموديل:** `App\Models\TranslationValue`

```php
protected $fillable = ['key', 'locale', 'value'];
```

---

## Middleware: SetLocale

**الملف:** `app/Http/Middleware/SetLocale.php`

**مسجّل في:** `routes/web.php` كـ `Route::middleware(['setLocale'])->group(...)`

**ترتيب الأولوية عند تحديد اللغة:**

```
1. query parameter ?change-locale=ar  → يحفظ في Session ثم يعيد توجيه بدون البارامتر
2. session('locale')                  → اللغة المحفوظة في الجلسة
3. GeneralSetting->default_language   → اللغة الافتراضية من إعدادات الإدارة
4. config('app.locale')               → fallback من ملف .env
```

**الكود الأساسي:**
```php
public function handle(Request $request, Closure $next): Response
{
    // 1. جلب اللغة الافتراضية من الإعدادات
    $generalSetting = GeneralSetting::first();
    $default_language = $generalSetting?->default_language
        ? Language::find($generalSetting->default_language)?->code ?? config('app.locale')
        : config('app.locale');

    // 2. دعم تغيير اللغة عبر query parameter
    if ($request->has('change-locale')) {
        $newLocale = Str::lower($request->query('change-locale'));
        $supportedLocales = Language::where('is_active', true)->pluck('code')...;
        if (in_array($newLocale, $supportedLocales)) {
            session(['locale' => $newLocale]);
            app()->setLocale($newLocale);
        }
        return redirect()->to($request->url()); // إعادة توجيه بدون البارامتر
    }

    // 3. تطبيق اللغة من الجلسة أو الافتراضية
    $locale = Str::lower(session('locale', $default_language));
    app()->setLocale($locale);

    return $next($request);
}
```

> **ملاحظة أمنية:** لا تُقبَل اللغات غير المفعّلة في قاعدة البيانات — يتم التحقق من `is_active = true` قبل تطبيق أي لغة.

---

## دالة `t()` — نظام الترجمة الأساسي

**الملف:** `app/helpers.php`

هذه الدالة هي **المحور الرئيسي** لترجمة النصوص الثابتة في المشروع.

```php
function t(string $key, ?string $default = null): string
```

**آلية العمل:**

```
t('frontend.nav.home')
    ↓
يبحث في الكاش: "translation.ar.frontend.nav.home"
    ↓ إذا لم يجد
يجلب من قاعدة البيانات: TranslationValue WHERE key=... AND locale=ar
    ↓ إذا لم يجد وكان auto_create مفعلاً
يُنشئ سجلاً جديداً بقيمة فارغة (أو $default)
    ↓ إذا لا يزال لا توجد قيمة
يجرب لغة fallback (en)
    ↓ إذا لم يجد
يرجع $default أو الـ key نفسه
```

**أولوية الإرجاع:**
1. القيمة من اللغة الحالية
2. القيمة من لغة الـ fallback (`config('app.fallback_locale')`)
3. قيمة `$default` إن وُجدت
4. الـ `$key` نفسه

**الكاش:** مدة 60 ثانية، مفتاح الكاش: `translation.{locale}.{key}`

**الإنشاء التلقائي (Auto-Create):**
عندما تُستدعى `t('key')` لمفتاح غير موجود، ويكون `config('app.translation_auto_create', true)` مفعّلاً، يُنشئ النظام السجل تلقائياً بقيمة فارغة. هذا يُسهّل إضافة مفاتيح ترجمة جديدة دون الحاجة لإضافتها يدوياً لكل لغة.

**دالة مكافئة للـ HTML:** `t_html($key, $default)` — نفس `t()` لكن تُستخدم داخل `{!! !!}` في Blade.

**أمثلة الاستخدام:**
```blade
{{ t('frontend.nav.home') }}
{{ t('frontend.hero.title', 'مرحباً بك') }}
{!! t_html('frontend.about.description') !!}
```

---

## دالة `current_dir()` — اتجاه النص

**الملف:** `app/helpers.php`

```php
function current_dir(): string
```

ترجع `'rtl'` إذا كانت اللغة الحالية تدعم RTL، و`'ltr'` بخلاف ذلك.

**الاستخدام في Blade:**
```blade
<html dir="{{ current_dir() }}" lang="{{ app()->getLocale() }}">
```

تعتمد على حقل `is_rtl` في جدول `languages`.

---

## دالة `available_locales()` — اللغات المتاحة

**الملف:** `app/helpers.php`

```php
function available_locales(): Collection
```

ترجع كل اللغات التي `is_active = true` من جدول `languages`.

**الاستخدام:**
```php
$langs = available_locales(); // Collection من Language models
foreach ($langs as $lang) {
    // $lang->code, $lang->name, $lang->native, $lang->is_rtl
}
```

---

## دالة `page_slug()` — ترجمة سلاق الصفحة

**الملف:** `app/helpers.php`

```php
function page_slug(string $canonicalKey, ?string $locale = null): string
```

تُرجع الـ `slug` الصحيح لصفحة بناءً على اللغة الحالية.

**السيناريو:** الصفحة `/templates` بالإنجليزية قد تكون `/قوالب` بالعربية. هذه الدالة تجلب الـ slug الصحيح بدون الحاجة لمعرفة الـ ID.

**خطوات البحث:**
1. يبحث عن الصفحة بالـ `canonicalKey` في لغة الـ fallback
2. إذا لم يجد، يبحث في أي لغة
3. يجلب ترجمة الصفحة للغة الحالية
4. إذا لم تتوفر ترجمة للغة الحالية → يرجع للـ fallback
5. إذا لم يجد أي شيء → يرجع `$canonicalKey` كما هو

**الاستخدام:**
```blade
<a href="/{{ page_slug('templates') }}">القوالب</a>
```

---

## LocaleController — تبديل اللغة

**الملف:** `app/Http/Controllers/Admin/LocaleController.php`

### `GET /change-locale/{locale}?redirect=/path`

يغيّر اللغة النشطة ويُعيد التوجيه للصفحة السابقة مع ترجمة الـ slug إن أمكن.

**خوارزمية إعادة التوجيه:**
```
1. إذا كان هناك ?redirect= صريح وآمن → انتقل إليه
2. إذا كانت الصفحة السابقة صفحة Template → انتقل لنفس القالب بسلاق اللغة الجديدة
3. إذا كانت الصفحة السابقة صفحة Portfolio → انتقل للبورتفوليو بسلاق اللغة الجديدة
4. إذا كان المسار غير آمن (ملف CSS/JS/صورة) → انتقل للـ homepage
5. خلاف ذلك → انتقل للصفحة السابقة
```

**فحص الأمان للـ Redirect:**
- يُسمح فقط بمسارات تبدأ بـ `/` أو URLs تخص نفس النطاق
- يُرفض أي redirect لنطاق خارجي

### `GET /translate-json/{locale}`

يُرجع جميع ترجمات لغة معينة كـ JSON — مفيد للواجهات الأمامية ومكتبات JavaScript.

```json
{
  "frontend.nav.home": "الرئيسية",
  "frontend.nav.about": "من نحن",
  ...
}
```

---

## LanguageController — إدارة اللغات (Admin)

**الملف:** `app/Http/Controllers/Admin/LanguageController.php`

| الدالة          | الوصف                                             |
|-----------------|---------------------------------------------------|
| `index()`       | عرض قائمة اللغات مع pagination                   |
| `create()`      | نموذج إضافة لغة جديدة                            |
| `store()`       | حفظ لغة جديدة — يُحوّل الـ code لـ lowercase     |
| `edit()`        | نموذج تعديل لغة                                  |
| `update()`      | تحديث بيانات اللغة                               |
| `toggleRtl()`   | تبديل خاصية RTL عبر AJAX — `POST`               |
| `toggleStatus()`| تفعيل/إيقاف اللغة عبر AJAX — `POST`             |
| `destroy()`     | حذف اللغة مع **كل ترجماتها** وتنظيف الكاش       |

> **تحذير:** عند حذف لغة يتم حذف كل سجلات `translation_values` المرتبطة بها تلقائياً مع مسح الكاش لكل مفتاح على حدة.

---

## TranslationValueController — إدارة الترجمات (Admin)

**الملف:** `app/Http/Controllers/Admin/TranslationValueController.php`

| الدالة       | الوصف                                                        |
|--------------|--------------------------------------------------------------|
| `index()`    | عرض الترجمات مع فلترة بـ locale, search, type (dashboard/frontend/general) |
| `create()`   | نموذج إضافة مفتاح ترجمة جديد لجميع اللغات دفعة واحدة       |
| `store()`    | حفظ ترجمة — يستخدم `updateOrCreate` لتجنب التكرار          |
| `edit($key)` | تعديل مفتاح ترجمة بكل لغاته                                 |
| `update()`   | تحديث الترجمة + **مسح الكاش** فوراً                        |
| `destroy()`  | حذف مفتاح وكل ترجماته + مسح الكاش                         |
| `export()`   | تصدير كل الترجمات كملف CSV                                 |
| `import()`   | استيراد ترجمات من ملف CSV + تحديث الكاش                    |

**هيكل بيانات الـ Store/Update:**
```php
// الـ Request يصل بهذا الشكل:
$request->values = [
    'ar' => 'الرئيسية',
    'en' => 'Home',
    'fr' => 'Accueil',
];
$request->key = 'frontend.nav.home';
```

---

## View Components: محوّل اللغة

### `LanguageSwitcher`

**الملف:** `app/View/Components/lang/LanguageSwitcher.php`
**الـ View:** `resources/views/components/lang/language-switcher.blade.php`
**الاستخدام:** في الواجهة الأمامية (Frontend)

```blade
<x-lang.language-switcher />
```

**يُمرّر للـ View:**
- `$languages` — جميع اللغات المفعّلة
- `$currentLocale` — كود اللغة الحالية (`app()->getLocale()`)
- `$currentLanguage` — موديل اللغة الحالية (للحصول على الاسم والراية)

### `LanguageSwitcherDashboard`

**الملف:** `app/View/Components/lang/LanguageSwitcherDashboard.php`
**الـ View:** `resources/views/components/lang/language-switcher-dashboard.blade.php`
**الاستخدام:** في لوحة التحكم

```blade
<x-lang.language-switcher-dashboard />
```

نفس بيانات `LanguageSwitcher` لكن مُصمَّم بـ UI مختلف يناسب لوحة التحكم.

---

## AppServiceProvider — المتغيرات العامة للـ Views

**الملف:** `app/Providers/AppServiceProvider.php`

يُمرّر النظام تلقائياً للـ Views التالية المتغيرات في **كل صفحة**:

```php
view()->composer('*', function ($view) {
    $view->with([
        'currentLocale'   => app()->getLocale(),
        'currentLanguage' => Language::where('code', $currentLocale)->first(),
        'languages'       => Language::where('is_active', true)->get(),
        'settings'        => GeneralSetting::first(),
    ]);
});
```

هذا يعني أن أي Blade template يمكنه مباشرةً استخدام:
```blade
{{ $currentLocale }}       {{-- 'ar' أو 'en' --}}
{{ $currentLanguage->name }}
@foreach ($languages as $lang)
    {{ $lang->native }}
@endforeach
```

---

## GeneralSetting — اللغة الافتراضية

**الملف:** `app/Models/GeneralSetting.php`
**الجدول:** `general_settings`

يحتوي على `default_language` (foreign key → `languages.id`).

**للوصول للغة الافتراضية:**
```php
$setting = GeneralSetting::first();
$defaultLang = $setting->language; // belongs to Language
```

**`localized_content`** — حقل JSON يُخزّن فيه بعض حقول الإعدادات بطريقة متعددة اللغات مثل `site_title` و `site_discretion`:

```json
{
    "site_title":    { "ar": "بالجولز", "en": "PalGoals" },
    "site_discretion": { "ar": "وصف عربي", "en": "English description" }
}
```

**للحصول على القيمة المُترجَمة:**
```php
$setting->resolved_site_title       // يرجع العنوان بحسب اللغة الحالية
$setting->resolved_site_discretion  // يرجع الوصف بحسب اللغة الحالية
$setting->resolved_contact_info     // مصفوفة بيانات التواصل مع العنوان مُترجَماً
```

---

## نموذج الترجمة في الـ Models (Translation Tables)

كل نموذج قابل للترجمة يتبع هذا النمط:

**الجداول المُترجَمة:**

| الموديل الأصلي    | جدول الترجمات              | الحقول المُترجَمة                            |
|-------------------|---------------------------|----------------------------------------------|
| `Page`            | `page_translations`        | slug, title, content, meta_title, meta_description, meta_keywords, og_image |
| `Template`        | `template_translations`    | slug, name, description, ...                |
| `Portfolio`       | `portfolio_translations`   | slug, title, ...                            |
| `Plan`            | `plan_translations`        | name, description, ...                      |
| `PlanCategory`    | `plan_category_translations`| name, ...                                  |
| `Service`         | `service_translations`     | name, description, ...                      |
| `Testimonial`     | `testimonial_translations` | content, author_name, ...                   |
| `HeaderItem`      | `header_item_translations` | label, ...                                  |
| `Section`         | `section_translations`     | بيانات المقاطع                              |
| `CategoryTemplate`| `category_template_translations` | name, ...                            |

**نمط الـ Relationship:**
```php
// في الموديل الأصلي (مثال: Page)
public function translations()
{
    return $this->hasMany(PageTranslation::class);
}

// جلب ترجمة اللغة الحالية
$page->translations()->where('locale', app()->getLocale())->first();

// أو استخدام دالة getTranslation (إذا كانت موجودة في الموديل)
$page->getTranslation($locale);
```

**بنية جدول الترجمات (مثال: `page_translations`):**
```sql
id          bigint PK
page_id     bigint FK → pages.id
locale      varchar(10)   -- 'ar', 'en', etc.
slug        varchar       -- /عن-الشركة | /about-us
title       varchar
meta_title  varchar
meta_description text
...
```

---

## مسارات الـ Routes الخاصة باللغة

**الملف:** `routes/lang.php` — مُضمَّن في `routes/web.php`

```php
// تغيير اللغة (يحفظ في الجلسة)
GET /change-locale/{locale}
    → LocaleController@change
    → name: 'change_locale'

// جلب كل الترجمات كـ JSON
GET /translate-json/{locale}
    → LocaleController@translateJson
    → name: 'translate_json'
```

**مثال تبديل اللغة في Blade:**
```blade
<a href="{{ route('change_locale', 'ar') }}">العربية</a>
<a href="{{ route('change_locale', 'en') }}">English</a>

{{-- مع redirect صريح --}}
<a href="{{ route('change_locale', ['locale' => 'ar', 'redirect' => request()->path()]) }}">
    العربية
</a>
```

**أو عبر query parameter مباشرةً:**
```blade
<a href="{{ url()->current() }}?change-locale=ar">العربية</a>
```

---

## تسلسل عمل النظام (Flow)

### عند زيارة صفحة

```
المستخدم يزور /about
    ↓
[Middleware: SetLocale]
    → يقرأ session('locale') → 'ar'
    → يتحقق من is_active في DB
    → app()->setLocale('ar')
    ↓
[FrontPageController@show]
    → يبحث عن Page له PageTranslation حيث slug='about' AND locale='ar'
    ↓
[Blade View]
    → t('frontend.nav.home')    ← يجلب من DB مع كاش
    → current_dir()             ← يرجع 'rtl'
    → $currentLanguage->native  ← 'العربية'
```

### عند تبديل اللغة

```
المستخدم يضغط على "English"
    ↓
GET /change-locale/en
    ↓
[LocaleController@change]
    → يتحقق أن 'en' موجود وis_active=true
    → session(['locale' => 'en'])
    → يفحص الصفحة السابقة
    → إذا كانت /templates/قوالب-عربي → يجلب slug الإنجليزي → /templates/templates-english
    → redirect()
```

---

## أنماط الاستخدام في Blade

```blade
{{-- ترجمة نص عادي --}}
{{ t('frontend.nav.home') }}

{{-- ترجمة مع قيمة افتراضية --}}
{{ t('frontend.hero.title', 'مرحباً بك') }}

{{-- ترجمة HTML (بدون escape) --}}
{!! t_html('frontend.about.content') !!}

{{-- اتجاه الصفحة --}}
<html dir="{{ current_dir() }}" lang="{{ app()->getLocale() }}">

{{-- سلاق صفحة مُترجَم --}}
<a href="/{{ page_slug('about') }}">{{ t('frontend.nav.about') }}</a>

{{-- محوّل اللغة --}}
<x-lang.language-switcher />

{{-- التحقق من RTL --}}
@if(current_dir() === 'rtl')
    <style>body { direction: rtl; }</style>
@endif

{{-- اللغة الحالية من المتغيرات العامة --}}
{{ $currentLocale }}
{{ $currentLanguage?->native }}
```

---

## الكاش (Cache)

| مفتاح الكاش                        | المدة    | متى يُمسح؟                                    |
|------------------------------------|----------|-----------------------------------------------|
| `translation.{locale}.{key}`       | 60 ثانية | عند تحديث/حذف الترجمة عبر Admin              |
| —                                  | —        | عند حذف اللغة (يُمسح لكل مفتاح)             |
| —                                  | —        | عند استيراد CSV                               |

**مسح الكاش يدوياً:**
```php
cache()->forget("translation.ar.frontend.nav.home");

// أو لمسح كل الترجمات
Cache::flush(); // احذر: يمسح كل الكاش
```

---

## إضافة لغة جديدة — خطوات عملية

### 1. من لوحة التحكم (Admin)
انتقل إلى **Dashboard → Languages → Add Language** وأدخل:
- `name`: اسم اللغة بالإنجليزية
- `native`: الاسم المحلي
- `code`: الكود (مثال: `fr`, `de`)
- `is_rtl`: فعّل إذا كانت اللغة من اليمين لليسار
- `is_active`: فعّل لتصبح متاحة

### 2. إضافة الترجمات
انتقل إلى **Dashboard → Translation Values** وأضف الترجمات اليدوية، أو:
- استخدم **Import CSV** لاستيراد ترجمات دفعة واحدة
- اتركها فارغة وسيُنشئها النظام تلقائياً عند أول استدعاء لـ `t('key')`

### 3. ترجمة محتوى الكيانات
لكل Page/Template/Portfolio، أضف سجلاً في جدول الترجمة المقابل بـ `locale` الجديد.

### 4. تعيينها كلغة افتراضية (اختياري)
من **Dashboard → General Settings → Default Language**.

---

## ملاحظات مهمة للمطورين

> **كودات اللغة تُحفظ دائماً بـ lowercase.** لا تستخدم `AR` أو `EN` — استخدم `ar`, `en`.

> **لا تستخدم `__('key')` أو `trans('key')` الخاصة بـ Laravel** في هذا المشروع. استخدم دائماً `t('key')`.

> **الكاش TTL هو 60 ثانية.** تغييرات الترجمة من الـ Admin تُطبَّق فوراً لأن الكاش يُمسح عند الحفظ.

> **`translation_auto_create`** — إذا استدعيت `t('key.جديد')` وهو غير موجود، سيُنشأ سجل فارغ تلقائياً. هذا مفيد في التطوير لكن راقبه في Production.

> **RTL** — اتجاه الصفحة يُحدَّد عبر `current_dir()` بناءً على `is_rtl` في جدول `languages`، وليس بطريقة hardcoded.
