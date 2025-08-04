<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Portfolio;
use App\Models\Template;
use App\Models\TranslationValue;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    //  Change language
    public function change($locale)
    {
        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if ($language) {
            session(['locale' => $locale]);
        }

        $previousUrl = url()->previous();
        $parsed = parse_url($previousUrl);
        $path = $parsed['path'] ?? '/';

        // التحقق من صفحة قالب Template
        if (preg_match('#/template/([^/]+)#', $path, $matches)) {
            $currentSlug = $matches[1];

            $template = Template::whereHas('translations', function ($q) use ($currentSlug) {
                $q->where('slug', $currentSlug);
            })->first();

            if ($template) {
                $translated = $template->getTranslation($locale);
                if ($translated) {
                    return redirect()->route('template.show', ['slug' => $translated->slug]);
                }
            }
        }

        // التحقق من صفحة Portfolio
        if (preg_match('#/portfolio/([^/]+)#', $path, $matches)) {
            $currentSlug = $matches[1];

            $portfolio = Portfolio::whereHas('translations', function ($q) use ($currentSlug) {
                $q->where('slug', $currentSlug);
            })->first();

            if ($portfolio) {
                $translated = $portfolio->getTranslation($locale);
                if ($translated) {
                    return redirect()->route('portfolio.show', ['slug' => $translated->slug]);
                }
            }
        }

        return redirect()->back();
    }

    // Return all translations as JSON (for Frontend, SPA or JS)
    public function translateJson($locale)
    {
        $translations = TranslationValue::where('locale', $locale)->pluck('value', 'key');

        return response()->json($translations);
    }
}
