<?php

use App\Http\Controllers\Dashboard\Management\DomainSearchController;
use App\Http\Controllers\Dashboard\TemplateController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Livewire\Dashboard\Template\TemplateShowPage;
use App\Models\Language;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Template;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\TemplateController as FrontTemplateController;
use App\Http\Controllers\Frontend\TemplateReviewController;
// use App\Http\Controllers\Dashboard\Management\DomainTldController; // نقل الراوت إلى dashboard.php

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
    })->where('slug', '^(?!client|admin|dashboard|api|storage|templates|change-locale|checkout).*$');


    Route::get('portfolio/{slug}', function ($slug) {
        $portfolio = Portfolio::with(['translations'])
            ->where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();
        return view('tamplate.portfolio', ['portfolio' => $portfolio]);
    })->name('portfolio.show');

    Route::get('/checkout/client/{template_id}', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout/client/{template_id}/process', [CheckoutController::class, 'process'])->name('checkout.process');

    Route::get('/templates/{slug}', [FrontTemplateController::class, 'show'])->name('template.show');
    Route::get('/templates/{slug}/preview', [FrontTemplateController::class, 'preview'])->name('template.preview');
    Route::post('templates/{template}/reviews', [TemplateReviewController::class, 'store'])
        ->name('frontend.templates.reviews.store')
        ->whereNumber('template');

    Route::get('/domains', [DomainSearchController::class, 'page'])->name('domains.page');
    // API لفحص التوافر (AJAX)
    Route::get('/api/domains/check', [DomainSearchController::class, 'check'])
        ->middleware(['throttle:30,1']) // معدل بسيط
        ->name('domains.check');





    Route::get('change-locale/{locale}', function ($locale) {
        $language = Language::where('code', $locale)->where('is_active', true)->first();

        if ($language) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    })->name('change_locale');


    // باقي Routes
    require __DIR__ . '/dashboard.php';
    require __DIR__ . '/client.php';
});


require __DIR__ . '/lang.php';
