<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));

        // اجلب قائمة اللغات المدعومة من الجدول
        $supportedLocales = Language::where('is_active', true)->pluck('code')->toArray();

        if (in_array($locale, $supportedLocales)) {
            app()->setLocale($locale);
        } else {
            app()->setLocale(config('app.locale'));
        }

        return $next($request);
    }
}
