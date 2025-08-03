<?php

use App\Livewire\Dashboard\Template\TemplateShowPage;
use App\Models\Language;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Template;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('tamplate.home');
// });

Route::middleware(['setLocale'])->group(function () {

    Route::get('/', function () {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('is_home', true)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            abort(404, 'لم يتم تحديد الصفحة الرئيسية بعد.');
        }
        view()->share('currentPage', $page);
        return view('tamplate.page', ['page' => $page]);
    });

    // صفحات أخرى عبر slug
    Route::get('/{slug}', function ($slug) {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
            view()->share('currentPage', $page);
            return view('tamplate.page', ['page' => $page]);
    });


    Route::get('portfolio/{slug}', function ($slug)
    {
        $portfolio = Portfolio::with(['translations'])
            ->where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();
            return view('tamplate.portfolio', ['portfolio' => $portfolio]);
    })->name('portfolio.show');

    Route::get('template/{slug}', function ($slug) {
        $locale = app()->getLocale();
        $template = Template::with(['translations', 'categoryTemplate.translation'])
            ->whereHas('translations', function ($q) use ($slug, $locale) {
                $q->where('slug', $slug)->where('locale', $locale);
        })
            ->firstOrFail();
            return view('tamplate.template-show', [
                'template' => $template,
                'translation' => $template->getTranslation(),
        ]);
    })->name('template.show');

    Route::get('change-locale/{locale}', function ($locale) {
        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if ($language) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    })->name('change_locale');


    // باقي Routes
    require __DIR__.'/dashboard.php';
    require __DIR__.'/client.php';
});


require __DIR__.'/lang.php';
