# دليل التثبيت — PalGoals Locale Package

## خطوات التثبيت في مشروع Laravel جديد

### 1. نسخ الملفات

انسخ محتوى الباكج إلى مشروعك بهذا التوزيع:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── LocaleController.php
│   │   ├── LanguageController.php
│   │   └── TranslationValueController.php
│   └── Middleware/
│       └── SetLocale.php
├── Models/
│   ├── Language.php
│   └── TranslationValue.php
├── View/Components/lang/
│   ├── LanguageSwitcher.php
│   └── LanguageSwitcherDashboard.php
└── helpers_locale.php

database/
├── migrations/
│   ├── xxxx_create_languages_table.php
│   └── xxxx_create_translation_values_table.php
└── seeders/
    └── LanguageSeeder.php

resources/views/
├── components/lang/
│   ├── language-switcher.blade.php
│   └── language-switcher-dashboard.blade.php
└── dashboard/lang/
    ├── index.blade.php
    ├── create.blade.php
    ├── edit.blade.php
    └── translation-values/
        ├── index.blade.php
        ├── create.blade.php
        └── edit.blade.php

routes/
├── lang.php
└── lang_dashboard.php
```

---

### 2. تسجيل الـ helpers في composer.json

```json
"autoload": {
    "files": [
        "app/helpers_locale.php"
    ]
}
```

ثم شغّل:
```bash
composer dump-autoload
```

---

### 3. تسجيل الـ Middleware

في `app/Http/Kernel.php` (Laravel 10 وما قبل) أضف:
```php
protected $middlewareAliases = [
    // ...
    'setLocale' => \App\Http\Middleware\SetLocale::class,
];
```

أو في `bootstrap/app.php` (Laravel 11+):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'setLocale' => \App\Http\Middleware\SetLocale::class,
    ]);
})
```

---

### 4. إضافة الـ Routes

في `routes/web.php` أضف:
```php
Route::middleware(['setLocale'])->group(function () {
    require __DIR__ . '/lang.php';
    // ... باقي routes مشروعك
});
```

في `routes/dashboard.php` (داخل مجموعة admin/auth):
```php
require __DIR__ . '/lang_dashboard.php';
```

---

### 5. تشغيل الـ Migrations

```bash
php artisan migrate
```

---

### 6. تشغيل الـ Seeder (اختياري)

```bash
php artisan db:seed --class=LanguageSeeder
```

---

### 7. إضافة متغير الـ config (اختياري)

في `config/app.php`:
```php
'translation_auto_create' => env('TRANSLATION_AUTO_CREATE', true),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
```

في `.env`:
```
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
TRANSLATION_AUTO_CREATE=true
```

---

### 8. تسجيل الـ View Components (إذا لم يكن auto-discovery مفعّلاً)

في `app/Providers/AppServiceProvider.php`:
```php
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Blade::component('lang.language-switcher', \App\View\Components\lang\LanguageSwitcher::class);
    Blade::component('lang.language-switcher-dashboard', \App\View\Components\lang\LanguageSwitcherDashboard::class);
}
```

---

### 9. مشاركة المتغيرات مع جميع الـ Views

في `app/Providers/AppServiceProvider.php` داخل `boot()`:
```php
view()->composer('*', function ($view) {
    $currentLocale   = app()->getLocale();
    $currentLanguage = \App\Models\Language::where('code', $currentLocale)->first();
    $languages       = \App\Models\Language::where('is_active', true)->get();

    $view->with([
        'currentLocale'   => $currentLocale,
        'currentLanguage' => $currentLanguage,
        'languages'       => $languages,
    ]);
});
```

---

## الاستخدام السريع

```blade
{{-- ترجمة نص --}}
{{ t('frontend.nav.home') }}
{{ t('frontend.hero.title', 'مرحباً') }}

{{-- اتجاه الصفحة --}}
<html dir="{{ current_dir() }}" lang="{{ app()->getLocale() }}">

{{-- محوّل اللغة في الواجهة --}}
<x-lang.language-switcher />
<x-lang.language-switcher variant="topbar" />

{{-- محوّل اللغة في لوحة التحكم --}}
<x-lang.language-switcher-dashboard />

{{-- سلاق صفحة مترجم --}}
<a href="/{{ page_slug('about') }}">{{ t('frontend.nav.about') }}</a>
```

---

## ملاحظة مهمة

ملفات الـ Views (`dashboard/lang/`) تعتمد على:
- `<x-dashboard-layout>` — Layout مشروعك الخاص
- `<x-form.input>` — مكوّن حقل الإدخال الخاص بمشروعك

إذا كانت أسماء مختلفة في مشروعك، عدّل الـ Views لتتناسب مع Layout الخاص بك.
