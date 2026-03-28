<?php

use App\Models\Language;
use App\Models\TranslationValue;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
// 3) Tenant domain helpers
// =======================================================
if (! function_exists('tenant_domain')) {
    /**
     * Get the configured platform tenant base domain.
     */
    function tenant_domain(): string
    {
        $domain = ltrim((string) config('app.tenant_domain', config('tenancy.subdomain_root', '')), '.');

        return $domain !== '' ? strtolower($domain) : 'palgoals.wpgoals.com';
    }
}

if (! function_exists('tenant_platform_domains')) {
    /**
     * Get all platform-hosted tenant base domains recognized by the app.
     */
    function tenant_platform_domains(): array
    {
        $legacyDomains = config('tenancy.legacy_subdomain_roots', []);
        $legacyDomains = is_array($legacyDomains) ? $legacyDomains : [];

        return array_values(array_unique(array_filter(array_map(
            static fn ($value) => ltrim(strtolower(trim((string) $value)), '.'),
            array_merge([tenant_domain()], $legacyDomains)
        ))));
    }
}

if (! function_exists('tenant_fqdn')) {
    /**
     * Build a full tenant host from a subdomain.
     */
    function tenant_fqdn(string $subdomain): string
    {
        $subdomain = trim(strtolower($subdomain), ". \t\n\r\0\x0B");

        return $subdomain === '' ? tenant_domain() : $subdomain . '.' . tenant_domain();
    }
}

if (! function_exists('tenant_url')) {
    /**
     * Build a tenant URL from either a subdomain or a full host/domain.
     */
    function tenant_url(string $subdomainOrDomain, ?string $scheme = null): string
    {
        $value = trim($subdomainOrDomain);

        if ($value === '') {
            return '';
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        $host = str_contains($value, '.') ? ltrim($value, '/') : tenant_fqdn($value);

        if ($scheme === null || $scheme === '') {
            $request = app()->bound('request') ? app('request') : null;
            $requestScheme = $request instanceof Request ? $request->getScheme() : null;
            $scheme = $requestScheme ?: (parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'http');
        }

        return $scheme . '://' . ltrim($host, '/');
    }
}

if (! function_exists('is_platform_tenant_host')) {
    /**
     * Determine whether the given host/domain belongs to the platform tenant bases.
     */
    function is_platform_tenant_host(?string $domain): bool
    {
        $value = trim((string) $domain);

        if ($value === '') {
            return false;
        }

        $host = Str::startsWith($value, ['http://', 'https://'])
            ? (parse_url($value, PHP_URL_HOST) ?: '')
            : $value;

        $host = strtolower(trim((string) $host, ". \t\n\r\0\x0B"));

        if ($host === '') {
            return false;
        }

        foreach (tenant_platform_domains() as $platformDomain) {
            if ($host === $platformDomain || Str::endsWith($host, '.' . $platformDomain)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('tenant_subdomain_from_host')) {
    /**
     * Extract the platform-hosted tenant subdomain from a fully qualified host.
     */
    function tenant_subdomain_from_host(?string $host): ?string
    {
        $host = strtolower(trim((string) $host, ". \t\n\r\0\x0B"));

        if ($host === '') {
            return null;
        }

        foreach (tenant_platform_domains() as $platformDomain) {
            $suffix = '.' . $platformDomain;

            if (! Str::endsWith($host, $suffix)) {
                continue;
            }

            $subdomain = substr($host, 0, -strlen($suffix));
            $subdomain = trim((string) $subdomain, ". \t\n\r\0\x0B");

            if ($subdomain !== '') {
                return $subdomain;
            }
        }

        return null;
    }
}

// =======================================================
// 4) t_html – نفس t لكن مخصّص للاستخدام داخل {!! !!}
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
// 5) t – نظام الترجمة مع كاش + أوتو-إنشاء
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
// 6) page_slug – جلب الـ slug المناسب للصفحة حسب اللغة
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
