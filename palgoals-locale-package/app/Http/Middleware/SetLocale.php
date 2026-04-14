<?php

namespace App\Http\Middleware;

use App\Models\GeneralSetting;
use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * Priority order:
     * 1. ?change-locale=xx  query param  → saves to session, redirects clean
     * 2. session('locale')               → previously selected language
     * 3. GeneralSetting->default_language → admin-configured default
     * 4. config('app.locale')             → .env fallback
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ── Resolve the configured default language ────────────────────────────
        $generalSetting = GeneralSetting::first();
        if ($generalSetting && $generalSetting->default_language) {
            $default_language = Language::where('id', $generalSetting->default_language)->first()?->code
                ?? config('app.locale');
        } else {
            $default_language = config('app.locale');
        }

        // ── Handle ?change-locale=xx on any URL ────────────────────────────────
        if ($request->has('change-locale')) {
            $newLocale = Str::lower($request->query('change-locale'));
            $supportedLocales = array_map('strtolower', Language::where('is_active', true)->pluck('code')->toArray());

            if (in_array($newLocale, $supportedLocales)) {
                session(['locale' => $newLocale]);
                app()->setLocale($newLocale);
            }

            // Redirect to the same URL without the query param (clean URL)
            return redirect()->to($request->url());
        }

        // ── Apply locale from session or default ───────────────────────────────
        $locale = Str::lower(session('locale', $default_language));
        $supportedLocales = array_map('strtolower', Language::where('is_active', true)->pluck('code')->toArray());

        if (in_array($locale, $supportedLocales)) {
            app()->setLocale($locale);
        } else {
            app()->setLocale($default_language);
        }

        return $next($request);
    }
}
