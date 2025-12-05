<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

// Front Controllers
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\TemplateController as FrontTemplateController;
use App\Http\Controllers\Front\TemplateReviewController;
use App\Http\Controllers\Front\TestimonialSubmissionController;
use App\Http\Controllers\Front\PageController as FrontPageController;

// Admin / Management Controllers (used in frontend routes)
use App\Http\Controllers\Admin\Management\DomainSearchController;

// Models
use App\Models\Language;
use App\Models\Plan;
use App\Models\Portfolio;
use App\Models\Tenancy\Subscription;
use App\Models\Tenancy\SubscriptionPage;

Route::middleware(['setLocale'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Marketing Home Page
    |--------------------------------------------------------------------------
    | Uses Front\PageController@home which:
    | - Finds the marketing homepage (is_home=1, is_active=1, context='marketing')
    | - Falls back to the first active marketing page.
    | - Renders resources/views/front/pages/page.blade.php
    */
    Route::get('/', [FrontPageController::class, 'home'])
        ->name('frontend.home');

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
    | Dedicated route for portfolio items (not handled by CMS pages).
    */
    Route::get('/portfolio/{slug}', function (string $slug) {
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

    Route::post('/checkout/domains/process', [CartController::class, 'processDomains'])
        ->name('checkout.domains.process');

    Route::get('/checkout/domains/success', function () {
        return view('front.pages.checkout-domains-success');
    })->name('checkout.domains.success');

    /*
    |--------------------------------------------------------------------------
    | Templates (blueprints / legacy frontend)
    |--------------------------------------------------------------------------
    */
    Route::get('/templates/{slug}', [FrontTemplateController::class, 'show'])
        ->name('template.show');

    Route::get('/templates/{slug}/preview', [FrontTemplateController::class, 'preview'])
        ->name('template.preview');

    Route::post('/templates/{template_id}/reviews', [TemplateReviewController::class, 'store'])
        ->name('frontend.templates.reviews.store')
        ->whereNumber('template_id');

    /*
    |--------------------------------------------------------------------------
    | Domains search (frontend)
    |--------------------------------------------------------------------------
    */
    Route::get('/domains', [DomainSearchController::class, 'page'])
        ->name('domains.page');

    // API for availability check (AJAX)
    Route::get('/api/domains/check', [DomainSearchController::class, 'check'])
        ->middleware(['throttle:30,1'])
        ->name('domains.check');

    /*
    |--------------------------------------------------------------------------
    | Dynamic CMS Pages by slug (must stay after specific routes)
    |--------------------------------------------------------------------------
    |
    | This route is responsible for all marketing CMS pages like:
    | - /about
    | - /services
    | - /contact
    |
    | It explicitly EXCLUDES:
    | client, admin, dashboard, api, storage, change-locale, checkout,
    | portfolio, invoices, bulk, tenant-preview
    |
    | NOTE:
    | - Keep more specific routes (like /templates, /domains, /checkout/...)
    |   ABOVE this route so they are matched first.
    */
    Route::get('/{slug}', [FrontPageController::class, 'show'])
        ->where(
            'slug',
            '^(?!(client|admin|dashboard|api|storage|change-locale|checkout|portfolio|invoices|bulk|tenant-preview)(/|$)).*$'
        )
        ->name('frontend.page.show');

    /*
    |--------------------------------------------------------------------------
    | Tenant Preview (LOCAL env only)
    |--------------------------------------------------------------------------
    | Used for previewing tenant front pages during development.
    */
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
                'page'         => $page,
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
                'page'         => $page,
            ]);
        })->name('tenant.preview.page');
    }

    /*
    |--------------------------------------------------------------------------
    | Locale Switching
    |--------------------------------------------------------------------------
    */
    Route::get('/change-locale/{locale}', function (string $locale) {
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

/*
|--------------------------------------------------------------------------
| Language file routes (e.g. /lang.js ...)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/lang.php';
