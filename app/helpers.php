<?php

use App\Models\Language;
use App\Models\TranslationValue;
use App\Models\Page;
use Illuminate\Support\Facades\Schema;

// =======================================================
// 1) اتجاه اللغة الحالي (rtl / ltr)
// =======================================================
if (! function_exists('current_dir')) {
    /**
     * Get current text direction based on active language (rtl or ltr).
     *
     * @return string
     */
    function current_dir(): string
    {
        $locale = app()->getLocale();

        $language = Language::where('code', $locale)
            ->where('is_active', true)
            ->first();

        return ($language && $language->is_rtl) ? 'rtl' : 'ltr';
    }
}

// =======================================================
// 2) جلب اللغات المتاحة
// =======================================================
if (! function_exists('available_locales')) {
    /**
     * Get all active languages in the system.
     *
     * @return \Illuminate\Support\Collection|\App\Models\Language[]
     */
    function available_locales()
    {
        return Language::where('is_active', true)->get();
    }
}

// =======================================================
// 3) t_html – نفس t لكن مخصّص للاستخدام داخل {!! !!}
// =======================================================
if (! function_exists('t_html')) {
    /**
     * Get translation value for given key (HTML safe — use in {!! !!} blocks).
     *
     * @param  string      $key
     * @param  string|null $default
     * @return string
     */
    function t_html(string $key, ?string $default = null): string
    {
        return t($key, $default);
    }
}

// =======================================================
// 4) t – نظام الترجمة مع كاش + أوتو-إنشاء
// =======================================================
if (! function_exists('t')) {
    /**
     * Get translation value for given key, with caching and auto-create.
     *
     * @param  string      $key
     * @param  string|null $default
     * @return string
     */
    function t(string $key, ?string $default = null): string
    {
        $locale         = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        $cacheKey = "translation.{$locale}.{$key}";

        $value = cache()->remember($cacheKey, 60, function () use ($key, $locale, $default) {
            $translation = TranslationValue::where('key', $key)
                ->where('locale', $locale)
                ->first();

            // Auto-create if missing (per locale)
            if (! $translation && config('app.translation_auto_create', true)) {
                TranslationValue::create([
                    'key'    => $key,
                    'locale' => $locale,
                    'value'  => $default ?? '',
                ]);
            }

            return $translation?->value;
        });

        if ($value !== null) {
            return $value;
        }

        // fallback to fallback_locale
        if ($locale !== $fallbackLocale) {
            $fallbackCacheKey = "translation.{$fallbackLocale}.{$key}";

            $fallbackValue = cache()->remember(
                $fallbackCacheKey,
                60,
                function () use ($key, $fallbackLocale, $default) {
                    $translation = TranslationValue::where('key', $key)
                        ->where('locale', $fallbackLocale)
                        ->first();

                    if (! $translation && config('app.translation_auto_create', true)) {
                        TranslationValue::create([
                            'key'    => $key,
                            'locale' => $fallbackLocale,
                            'value'  => $default ?? '',
                        ]);
                    }

                    return $translation?->value;
                }
            );

            if ($fallbackValue !== null) {
                return $fallbackValue;
            }
        }

        return $default ?? $key;
    }
}

// =======================================================
// 5) page_slug – جلب الـ slug المناسب للصفحة حسب اللغة
// =======================================================
if (! function_exists('page_slug')) {
    /**
     * إرجاع الـ slug المناسب لصفحة معيّنة حسب اللغة الحالية.
     *
     * $canonicalKey:
     *  - نعتبره الـ slug في اللغة الافتراضية (مثال: "templates" بالإنجليزي).
     */
    function page_slug(string $canonicalKey, ?string $locale = null): string
    {
        $locale         = $locale ?: app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        // 1️⃣ نبحث عن الصفحة عن طريق ترجمة الـ slug في لغة fallback أولاً
        $page = Page::query()
            ->whereHas('translations', function ($q) use ($canonicalKey, $fallbackLocale) {
                $q->where('slug', $canonicalKey)
                    ->where('locale', $fallbackLocale);
            })
            ->first();

        // 2️⃣ لو ما وجدناها، نحاول بأي لغة (احتياط)
        if (! $page) {
            $page = Page::query()
                ->whereHas('translations', function ($q) use ($canonicalKey) {
                    $q->where('slug', $canonicalKey);
                })
                ->first();

            if (! $page) {
                // لو ما وجدنا أي صفحة، نرجع القيمة كما هي بدون كراش
                return $canonicalKey;
            }
        }

        // 3️⃣ نحاول نجيب ترجمة حسب اللغة الحالية
        $trans = $page->translations()
            ->where('locale', $locale)
            ->first();

        // 4️⃣ لو ما في ترجمة للغة الحالية → fallback
        if (! $trans && $locale !== $fallbackLocale) {
            $trans = $page->translations()
                ->where('locale', $fallbackLocale)
                ->first();
        }

        // 5️⃣ الأولوية:
        // - slug من الترجمة (الحالية أو fallback)
        // - وإلا نرجع الـ canonicalKey الأصلي
        return $trans->slug ?? $canonicalKey;
    }
}
