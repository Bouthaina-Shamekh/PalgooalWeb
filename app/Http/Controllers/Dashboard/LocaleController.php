<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\TranslationValue;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    // ✅ تغيير اللغة
    public function change($locale)
    {
        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if ($language) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    }

    // ✅ إرجاع جميع التراجم كـ JSON (للـ Frontend أو SPA أو JS)
    public function translateJson($locale)
    {
        $translations = TranslationValue::where('locale', $locale)->pluck('value', 'key');

        return response()->json($translations);
    }
}
