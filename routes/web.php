<?php

use App\Http\Controllers\Admin\Management\DomainSearchController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\TemplateController as FrontTemplateController;
use App\Http\Controllers\Front\TemplateReviewController;
use App\Http\Controllers\Front\TestimonialSubmissionController;
use App\Models\Language;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Portfolio;
use App\Models\Tenancy\Subscription;
use App\Models\Tenancy\SubscriptionPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::middleware(['setLocale'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Home Page
    |--------------------------------------------------------------------------
    */
    Route::get('/', function () {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('is_home', true)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            abort(404, 'لم يتم تحديد الصفحة الرئيسية بعد.');
        }

        view()->share('currentPage', $page);

        return view('front.pages.page', ['page' => $page]);
    })->name('frontend.home');

    /*
    |--------------------------------------------------------------------------
    | Cart (frontend alias → checkout.cart)
    |--------------------------------------------------------------------------
    */
    Route::get('/cart', function () {
        return redirect()->route('checkout.cart');
    })->name('cart');

    /*
    |--------------------------------------------------------------------------
    | Testimonials
    |--------------------------------------------------------------------------
    */
    Route::get('/testimonials/submit', [TestimonialSubmissionController::class, 'create'])
        ->name('testimonials.submit');

    Route::post('/testimonials/submit', [TestimonialSubmissionController::class, 'store'])
        ->name('testimonials.submit.store');

    /*
    |--------------------------------------------------------------------------
    | Portfolio
    |--------------------------------------------------------------------------
    */
    Route::get('portfolio/{slug}', function ($slug) {
        $portfolio = Portfolio::with(['translations'])
            ->where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();

        return view('front.pages.portfolio', ['portfolio' => $portfolio]);
    })->name('portfolio.show');

    /*
    |--------------------------------------------------------------------------
    | Checkout + Cart (server-side)
    |--------------------------------------------------------------------------
    */
    Route::get('/checkout/client/{template_id}', [CheckoutController::class, 'index'])
        ->name('checkout');

    // Checkout entrypoint for domain-only cart (from /cart)
    Route::get('/checkout/cart', [CheckoutController::class, 'cart'])
        ->name('checkout.cart');

    Route::post('/checkout/cart/process', [CheckoutController::class, 'processCart'])
        ->name('checkout.cart.process');

    // Allow processing without a template by making template_id optional
    Route::post('/checkout/client/{template_id?}/process/{plan_id?}', [CheckoutController::class, 'process'])
        ->name('checkout.process');

    // Store client-side cart into server session (AJAX)
    Route::post('/cart/store', [CartController::class, 'store'])
        ->name('cart.store');

    // Clear domain-only cart from session (AJAX)
    Route::post('/cart/clear', [CartController::class, 'clear'])
        ->name('cart.clear');

    // Domain-only checkout (no template binding)
    Route::get('/checkout/domains', function () {
        return view('front.pages.checkout-domains');
    })->name('checkout.domains');

    Route::post(
        '/checkout/domains/process',
        [CartController::class, 'processDomains']
    )->name('checkout.domains.process');

    Route::get('/checkout/domains/success', function () {
        return view('front.pages.checkout-domains-success');
    })->name('checkout.domains.success');

    /*
    |--------------------------------------------------------------------------
    | Templates (blueprints / legacy)
    |--------------------------------------------------------------------------
    */
    Route::get('/templates/{slug}', [FrontTemplateController::class, 'show'])
        ->name('template.show');

    Route::get('/templates/{slug}/preview', [FrontTemplateController::class, 'preview'])
        ->name('template.preview');

    Route::post('templates/{template_id}/reviews', [TemplateReviewController::class, 'store'])
        ->name('frontend.templates.reviews.store')
        ->whereNumber('template_id');

    /*
    |--------------------------------------------------------------------------
    | Domains search (frontend)
    |--------------------------------------------------------------------------
    */
    Route::get('/domains', [DomainSearchController::class, 'page'])
        ->name('domains.page');

    // API لفحص التوافر (AJAX)
    Route::get('/api/domains/check', [DomainSearchController::class, 'check'])
        ->middleware(['throttle:30,1'])
        ->name('domains.check');

    /*
    |--------------------------------------------------------------------------
    | Dynamic CMS Pages by slug (must stay after specific routes)
    |--------------------------------------------------------------------------
    |
    | نستخدم هذا الراوت لكل صفحات الـ CMS مثل /about, /templates, /services...
    | ويُستثنى منه المسارات الخاصة مثل client, admin, dashboard, templates, ...
    */
    Route::get('/{slug}', function ($slug) {
        $page = Page::with(['translations', 'sections.translations'])
            ->where('is_active', true)
            ->whereSlug($slug)
            ->firstOrFail();

        view()->share('currentPage', $page);

        return view('front.pages.page', ['page' => $page]);
    })
    ->where('slug', '^(?!client|admin|dashboard|api|storage|change-locale|checkout|portfolio|invoices|bulk|tenant-preview).*$')
    ->name('frontend.page.show');

    if (app()->environment('local')) {
        Route::get('/tenant-preview/{subscription}', function (Subscription $subscription) {
            $subscription->loadMissing('plan');

            if (
                ! $subscription->plan
                || $subscription->plan->plan_type !== Plan::TYPE_MULTI_TENANT
                || $subscription->provisioning_status !== Subscription::PROVISIONING_ACTIVE
            ) {
                abort(404, 'Subscription is not an active multi-tenant.');
            }

            $page = SubscriptionPage::with(['translations', 'sections.translations'])
                ->where('subscription_id', $subscription->id)
                ->where('is_home', true)
                ->firstOrFail();

            View::share('tenantSubscription', $subscription);

            return view('tenant.site', [
                'subscription' => $subscription,
                'page' => $page,
            ]);
        })->name('tenant.preview');

        Route::get('/tenant-preview/{subscription}/{slug}', function (Subscription $subscription, string $slug) {
            $subscription->loadMissing('plan');

            if (
                ! $subscription->plan
                || $subscription->plan->plan_type !== Plan::TYPE_MULTI_TENANT
                || $subscription->provisioning_status !== Subscription::PROVISIONING_ACTIVE
            ) {
                abort(404, 'Subscription is not an active multi-tenant.');
            }

            $page = SubscriptionPage::with(['translations', 'sections.translations'])
                ->where('subscription_id', $subscription->id)
                ->where(function ($query) use ($slug) {
                    $query->where('slug', $slug)
                        ->orWhereHas('translations', function ($translationQuery) use ($slug) {
                            $translationQuery->where('slug', $slug);
                        });
                })
                ->firstOrFail();

            View::share('tenantSubscription', $subscription);

            return view('tenant.site', [
                'subscription' => $subscription,
                'page' => $page,
            ]);
        })->name('tenant.preview.page');
    }

    /*
    |--------------------------------------------------------------------------
    | Locale Switching
    |--------------------------------------------------------------------------
    */
    Route::get('change-locale/{locale}', function ($locale) {
        $language = Language::where('code', $locale)
            ->where('is_active', true)
            ->first();

        if ($language) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    })->name('change_locale');

    /*
    |--------------------------------------------------------------------------
    | Dashboard / Client routes
    |--------------------------------------------------------------------------
    */
    require __DIR__ . '/dashboard.php';
    require __DIR__ . '/client.php';
});

require __DIR__ . '/lang.php';
