<?php

use App\Models\Language;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('tamplate.home');
// });

Route::middleware(['setLocale'])->group(function () {

    Route::get('/', function () {
        return view('tamplate.home');
    });

    Route::get('change-locale/{locale}', function ($locale) {
        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if ($language) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    })->name('change_locale');


    // باقي Routes
    require __DIR__.'/dashboard.php';
});


require __DIR__.'/lang.php';