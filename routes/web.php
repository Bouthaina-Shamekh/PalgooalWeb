<?php

use App\Models\Language;
use App\Models\Page;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('tamplate.home');
// });

Route::middleware(['setLocale'])->group(function () {

    // Route::get('/', function () {
    //     return view('tamplate.home');
    // });
    // الصفحة الرئيسية
    Route::get('/', function () {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('is_home', true)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            abort(404, 'لم يتم تحديد الصفحة الرئيسية بعد.');
        }

        return view('tamplate.page', ['page' => $page]);
    });
    
    // صفحات أخرى عبر slug
    Route::get('/{slug}', function ($slug) {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('tamplate.page', ['page' => $page]);
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