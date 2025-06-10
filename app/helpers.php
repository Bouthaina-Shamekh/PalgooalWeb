<?php

use App\Models\Language;
use App\Models\TranslationValue;

// if (!function_exists('t')) {
//     /**
//      * Get translation value for given key, with fallback.
//      *
//      * @param string $key
//      * @param string|null $default
//      * @return string
//      */
//     function t($key, $default = null)
//     {
//         $locale = app()->getLocale();
//         $fallbackLocale = config('app.fallback_locale', 'en');

//         // 1️⃣ محاولة إيجاد الترجمة في اللغة الحالية
//         $translation = TranslationValue::where('key', $key)
//             ->where('locale', $locale)
//             ->first();

//         if ($translation) {
//             return $translation->value;
//         }

//         // 2️⃣ محاولة إيجاد الترجمة في fallback locale
//         if ($locale !== $fallbackLocale) {
//             $fallbackTranslation = TranslationValue::where('key', $key)
//                 ->where('locale', $fallbackLocale)
//                 ->first();

//             if ($fallbackTranslation) {
//                 return $fallbackTranslation->value;
//             }
//         }

//         // 3️⃣ في حال عدم وجود أي ترجمة → default أو key نفسه
//         return $default ?? $key;
//     }
// }

if (!function_exists('current_dir')) {
    /**
     * Get current text direction based on active language (rtl or ltr).
     *
     * @return string
     */
    function current_dir()
    {
        $locale = app()->getLocale();

        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if ($language && $language->is_rtl) {
            return 'rtl';
        }

        return 'ltr';
    }
}
if (!function_exists('available_locales')) {
    /**
     * Get all active languages in the system.
     *
     * @return \Illuminate\Support\Collection|\App\Models\Language[]
     */
    function available_locales()
    {
        return \App\Models\Language::where('is_active', true)->get();
    }
}
if (!function_exists('t_html')) {
    /**
     * Get translation value for given key (HTML safe — use in {!! !!} blocks).
     *
     * @param string $key
     * @param string|null $default
     * @return string
     */
    function t_html($key, $default = null)
    {
        return t($key, $default);
    }
}

if (!function_exists('t')) {
    function t($key, $default = null)
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        $cacheKey = "translation.{$locale}.{$key}";

        $value = cache()->remember($cacheKey, 60, function () use ($key, $locale, $default) {
            $translation = TranslationValue::where('key', $key)
                ->where('locale', $locale)
                ->first();

            // Auto-create if missing
            if (!$translation && config('app.translation_auto_create', true)) {
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

        // fallback
        if ($locale !== $fallbackLocale) {
            $fallbackCacheKey = "translation.{$fallbackLocale}.{$key}";

            $fallbackValue = cache()->remember($fallbackCacheKey, 60, function () use ($key, $fallbackLocale, $default) {
                $translation = TranslationValue::where('key', $key)
                    ->where('locale', $fallbackLocale)
                    ->first();

                if (!$translation && config('app.translation_auto_create', true)) {
                    TranslationValue::create([
                        'key'    => $key,
                        'locale' => $fallbackLocale,
                        'value'  => $default ?? '',
                    ]);
                }

                return $translation?->value;
            });

            if ($fallbackValue !== null) {
                return $fallbackValue;
            }
        }

        return $default ?? $key;
    }
}
