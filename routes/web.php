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
// use App\Http\Controllers\Dashboard\Management\DomainTldController; // moved apply-pricing route to dashboard group

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

    // صفحات أخرى عبر slug (catch-all) — ضع المسارات الخاصة قبل هذا لتجنّب التقاطها
    // صفحة سلة بسيطة للزوار: تعرض الدومينات المخزنة في localStorage عبر JS
    Route::get('/cart', function () {
        return redirect()->route('checkout.cart');
    })->name('cart');

    // صفحات أخرى عبر slug
    Route::get('/{slug}', function ($slug) {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('is_active', true)
            ->whereSlug($slug)
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
    // Checkout entrypoint for domain-only cart (from /cart)
    Route::get('/checkout/cart', [CheckoutController::class, 'cart'])->name('checkout.cart');
    Route::post('/checkout/cart/process', [CheckoutController::class, 'processCart'])->name('checkout.cart.process');
    // Allow processing without a template by making template_id optional
    Route::post('/checkout/client/{template_id?}/process/{plan_id?}', [CheckoutController::class, 'process'])->name('checkout.process');
    // Store client-side cart into server session (AJAX)
    Route::post('/cart/store', [\App\Http\Controllers\Frontend\CartController::class, 'store'])->name('cart.store');
    // Clear domain-only cart from session (AJAX)
    Route::post('/cart/clear', [\App\Http\Controllers\Frontend\CartController::class, 'clear'])->name('cart.clear');
    // Domain-only checkout (no template binding)
    Route::get('/checkout/domains', function () {
        return view('tamplate.checkout-domains');
    })->name('checkout.domains');
    Route::post('/checkout/domains/process', [\App\Http\Controllers\Frontend\CartController::class, 'processDomains'])->name('checkout.domains.process');
    Route::get('/checkout/domains/success', function () {
        return view('tamplate.checkout-domains-success');
    })->name('checkout.domains.success');

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
