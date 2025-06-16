<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
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

        return redirect()->back();
    }

    // Return all translations as JSON (for Frontend, SPA or JS)
    public function translateJson($locale)
    {
        $translations = TranslationValue::where('locale', $locale)->pluck('value', 'key');

        return response()->json($translations);
    }
}
