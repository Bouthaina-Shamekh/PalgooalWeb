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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
{
    $generalSetting = GeneralSetting::first();
    if ($generalSetting && $generalSetting->default_language) {
        $default_language = Language::where('id', $generalSetting->default_language)->first()?->code ?? config('app.locale');
    } else {
        $default_language = config('app.locale');
    }

    // ✨ تغيير اللغة إذا وُجد باراميتر `change-locale`
    if ($request->has('change-locale')) {
        $newLocale = Str::lower($request->query('change-locale'));
        $supportedLocales = array_map('strtolower', Language::where('is_active', true)->pluck('code')->toArray());

        if (in_array($newLocale, $supportedLocales)) {
            session(['locale' => $newLocale]);
            app()->setLocale($newLocale);
        }

        // 🚀 إعادة التوجيه لنفس الرابط بدون باراميتر
        return redirect()->to($request->url());
    }

    // لغة الجلسة (أو الافتراضية)
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
