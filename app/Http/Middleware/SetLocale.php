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
        if($generalSetting && $generalSetting->default_language){
            $default_language = $generalSetting->default_language;
        }else{
            $default_language = config('app.locale');
        }
        $locale = Str::lower(session('locale', $default_language));

        // اجلب قائمة اللغات المدعومة من الجدول
        $supportedLocales = array_map('strtolower', Language::where('is_active', true)->pluck('code')->toArray());
        if (in_array(strtolower($locale), $supportedLocales)) {
            app()->setLocale($locale);
        } else {
            app()->setLocale($default_language);
        }

        return $next($request);
    }
}
