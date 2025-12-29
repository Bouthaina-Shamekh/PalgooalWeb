<?php

use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------------
// Dashboard Controllers (Core Admin Area)
// -----------------------------------------------------------------------------
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\TranslationValueController;
use App\Http\Controllers\Admin\ServicesController;
use App\Http\Controllers\Admin\TestimonialsController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\TemplateReviewController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\SectionController;

// -----------------------------------------------------------------------------
// Management Controllers (Hosting / Billing / Domains / Plans)
// -----------------------------------------------------------------------------
use App\Http\Controllers\Admin\Management\DomainController;
use App\Http\Controllers\Admin\Management\InvoiceController;
use App\Http\Controllers\Admin\Management\OrderController as ManagementOrderController;
use App\Http\Controllers\Admin\Management\PlanController;
use App\Http\Controllers\Admin\Management\ServerController;
use App\Http\Controllers\Admin\Management\SubscriptionController;
use App\Http\Controllers\Admin\Management\DomainProviderController;
use App\Http\Controllers\Admin\Management\DomainTldController;
use App\Http\Controllers\Admin\Management\PlanCategoryController;

// -----------------------------------------------------------------------------
// Redirect /admin → dashboard.home
// This keeps /admin as the main entry point for the dashboard.
// -----------------------------------------------------------------------------
Route::get('admin/', function () {
    return redirect()->route('dashboard.home');
});

// -----------------------------------------------------------------------------
// Main Admin Group
// prefix: /admin
// name:   dashboard.*
// middleware: auth
// -----------------------------------------------------------------------------
Route::group([
    'prefix'     => 'admin',
    'as'         => 'dashboard.',
    'middleware' => 'auth',
], function () {

    // -------------------------------------------------------------------------
    // Dashboard Home + General Settings
    // -------------------------------------------------------------------------
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/general_settings', [HomeController::class, 'general_settings'])->name('general_settings');

    // -------------------------------------------------------------------------
    // Users Management
    // -------------------------------------------------------------------------
    Route::resources([
        'users' => UserController::class,
    ]);

    Route::get('users/{user}/profile', [UserController::class, 'profile'])
        ->name('users.profile');

    // -------------------------------------------------------------------------
    // Languages
    // -------------------------------------------------------------------------
    Route::resource('languages', LanguageController::class)
        ->except(['show'])
        ->names('languages');

    // Extra AJAX endpoints for languages
    Route::post('languages/{language}/toggle-rtl', [LanguageController::class, 'toggleRtl'])
        ->name('languages.toggle-rtl');

    Route::post('languages/{language}/toggle-status', [LanguageController::class, 'toggleStatus'])
        ->name('languages.toggle-status');

    Route::delete('languages/{language}/delete', [LanguageController::class, 'destroy'])
        ->name('languages.destroy-ajax');

    // -------------------------------------------------------------------------
    // Translation Values (dictionary)
    // -------------------------------------------------------------------------
    Route::resource('translation-values', TranslationValueController::class)
        ->except(['show', 'edit', 'update', 'destroy']);

    Route::get('translation-values/{key}/edit', [TranslationValueController::class, 'edit'])
        ->name('translation-values.edit');

    Route::post('translation-values/{key}/update', [TranslationValueController::class, 'update'])
        ->name('translation-values.update');

    Route::delete('translation-values/{key}/delete', [TranslationValueController::class, 'destroy'])
        ->name('translation-values.destroy');

    // Export / Import translation values
    Route::get('translation-values/export', [TranslationValueController::class, 'export'])
        ->name('translation-values.export');

    Route::post('translation-values/import', [TranslationValueController::class, 'import'])
        ->name('translation-values.import');

    // -------------------------------------------------------------------------
    // Services / Testimonials / Portfolio
    // -------------------------------------------------------------------------
    Route::resource('services', ServicesController::class);
    Route::resource('testimonials', TestimonialsController::class);
    Route::resource('portfolios', PortfolioController::class);

    // -------------------------------------------------------------------------
    // Static internal dashboard pages (Livewire/Vue mounts, etc.)
    // -------------------------------------------------------------------------
    Route::get('clients', function () {
        return view('dashboard.clients');
    })->name('clients');

    Route::get('menus', function () {
        return view('dashboard.header');
    })->name('headers');

    // -------------------------------------------------------------------------
    // Marketing Pages Management (high-level CMS pages)
    // -------------------------------------------------------------------------
    Route::prefix('pages')
        ->name('pages.')
        ->group(function () {

            // List pages
            Route::get('/', [PageController::class, 'index'])->name('index');

            // Create page
            Route::get('/create', [PageController::class, 'create'])->name('create');
            Route::post('/', [PageController::class, 'store'])->name('store');

            // Edit page
            Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/{page}', [PageController::class, 'update'])->name('update');

            // Delete page
            Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');

            // Toggle page active/inactive
            Route::post('/{page}/toggle-active', [PageController::class, 'toggleActive'])->name('toggle-active');

            // Mark as homepage
            Route::post('/{page}/set-home', [PageController::class, 'setHome'])->name('set-home');

            // Visual Page Builder (GrapesJS)
            Route::get('/{page}/builder', [PageBuilderController::class, 'edit'])->name('builder');
            Route::get('/{page}/builder/data', [PageBuilderController::class, 'loadData'])->name('builder.data');
            Route::post('/{page}/builder/data', [PageBuilderController::class, 'saveData'])->name('builder.data.save');
            Route::post('/{page}/builder/publish', [PageBuilderController::class, 'publish'])
                ->name('builder.publish');
        });

    // -------------------------------------------------------------------------
    // Page Sections (Page Builder blocks per page)
    // Example:
    //   - /admin/pages/5/sections
    //   - /admin/pages/5/sections/create
    //   - /admin/pages/5/sections/{section}/edit
    // -------------------------------------------------------------------------
    Route::prefix('pages/{page}')
        ->name('pages.sections.')
        ->group(function () {

            // List sections for a specific page
            Route::get('sections', [SectionController::class, 'index'])->name('index');

            // Show form to create a new section for this page
            Route::get('sections/create', [SectionController::class, 'create'])->name('create');

            // Store a new section
            Route::post('sections', [SectionController::class, 'store'])->name('store');

            // Edit an existing section
            Route::get('sections/{section}/edit', [SectionController::class, 'edit'])->name('edit');

            // Update section
            Route::match(['put', 'patch'], 'sections/{section}', [SectionController::class, 'update'])
                ->name('update');

            // Delete section
            Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('destroy');
        });

    // -------------------------------------------------------------------------
    // Domains Management
    // -------------------------------------------------------------------------
    Route::get('domains/{domain}/register', [DomainController::class, 'editRegister'])
        ->name('domains.register.edit');
    Route::put('domains/{domain}/register', [DomainController::class, 'updateRegister'])
        ->name('domains.register.update');

    Route::get('domains/{domain}/renew', [DomainController::class, 'editRenew'])
        ->name('domains.renew.edit');
    Route::put('domains/{domain}/renew', [DomainController::class, 'updateRenew'])
        ->name('domains.renew.update');

    Route::get('domains/{domain}/dns', [DomainController::class, 'editDns'])
        ->name('domains.dns.edit');
    Route::put('domains/{domain}/dns', [DomainController::class, 'updateDns'])
        ->name('domains.dns.update');

    // ⚠️ Important: keep this resource as-is to preserve route name "dashboard.domains.index"
    Route::resource('domains', DomainController::class)->names('domains');

    // -------------------------------------------------------------------------
    // Media Library (UI + JSON API)
    // -------------------------------------------------------------------------
    // Blade UI for media library
    Route::get('/media-library', function () {
        return view('dashboard.media');
    })->name('media');

    // JSON API for media operations (index/store/update/delete)
    Route::prefix('media')
        ->name('media.')
        ->group(function () {
            Route::get('/', [MediaController::class, 'index'])->name('index');
            Route::post('/', [MediaController::class, 'store'])->name('store');
            Route::get('/{id}', [MediaController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [MediaController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/{id}', [MediaController::class, 'update'])->name('update');
            Route::delete('/{id}', [MediaController::class, 'destroy'])->name('destroy');
        });

    // -------------------------------------------------------------------------
    // WordPress Templates (Products)
    // -------------------------------------------------------------------------
    Route::get('templates/category', function () {
        // Dedicated Blade page for template categories (UI only)
        return view('dashboard.templates.CategoryTemplats');
    })->name('category');

    Route::resource('templates', TemplateController::class);

    // Template Reviews
    Route::get('/reviews', [TemplateReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [TemplateReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/reject', [TemplateReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [TemplateReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/bulk', [TemplateReviewController::class, 'bulk'])->name('reviews.bulk');

    // -------------------------------------------------------------------------
    // Hosting Subscriptions
    // -------------------------------------------------------------------------
    Route::post('/subscriptions/{subscription}/sync', [SubscriptionController::class, 'syncWithProvider'])
        ->name('subscriptions.sync');

    Route::post('/subscriptions/{subscription}/provision', [SubscriptionController::class, 'provision'])
        ->name('subscriptions.provision');

    Route::get('/subscriptions/{subscription}/cpanel-login', [SubscriptionController::class, 'cpanelLogin'])
        ->name('subscriptions.cpanel-login');

    // Manual WordPress installation (current UI entry point)
    Route::post(
        '/subscriptions/{subscription}/install-wordpress',
        [SubscriptionController::class, 'installWordPressManual']
    )->name('subscriptions.install-wordpress');

    // Optional: automatic WordPress install via provider
    Route::post(
        '/subscriptions/{subscription}/install-wordpress-provider',
        [SubscriptionController::class, 'installWordPress']
    )->name('subscriptions.install-wordpress.provider');

    Route::post(
        '/subscriptions/{subscription}/suspend',
        [SubscriptionController::class, 'suspendToProvider']
    )->name('subscriptions.suspend');

    Route::post(
        '/subscriptions/{subscription}/unsuspend',
        [SubscriptionController::class, 'unsuspendToProvider']
    )->name('subscriptions.unsuspend');

    Route::post(
        '/subscriptions/{subscription}/terminate',
        [SubscriptionController::class, 'terminateToProvider']
    )->name('subscriptions.terminate');

    // Bulk actions for subscriptions
    Route::post('/subscriptions/bulk', [SubscriptionController::class, 'bulk'])
        ->name('subscriptions.bulk');

    // Sync logs page
    Route::get('/subscriptions/sync-logs', [SubscriptionController::class, 'syncLogs'])
        ->name('subscriptions.sync-logs');

    // Main resource (index/show/create/edit)
    Route::resource('/subscriptions', SubscriptionController::class)->names('subscriptions');

    // AJAX: username suggestion + uniqueness check
    Route::post(
        '/subscriptions/username-suggest',
        [SubscriptionController::class, 'suggestUsername']
    )->name('subscriptions.username-suggest');

    // -------------------------------------------------------------------------
    // Servers (cPanel / WHM)
    // -------------------------------------------------------------------------
    Route::get('servers/{server}/test-connection', [ServerController::class, 'testConnection'])
        ->name('servers.test-connection');

    Route::get('servers/{server}/sso-whm', [ServerController::class, 'ssoWhm'])
        ->name('servers.sso-whm');

    Route::resource('servers', ServerController::class)->names('servers');

    Route::get('servers/{server}/accounts', [ServerController::class, 'accounts'])
        ->name('servers.accounts');

    Route::get('servers/{server}/packages', [ServerController::class, 'packages'])
        ->name('servers.packages');

    // -------------------------------------------------------------------------
    // Sites Overview
    // -------------------------------------------------------------------------
    Route::get('/sites', [HomeController::class, 'sites'])->name('sites');

    // -------------------------------------------------------------------------
    // Hosting Plans
    // -------------------------------------------------------------------------
    Route::resource('plans', PlanController::class)->names('plans');

    // Toggle plan active status
    Route::post('plans/{plan}/toggle', [PlanController::class, 'toggle'])
        ->name('plans.toggle');

    // -------------------------------------------------------------------------
    // Invoices
    // -------------------------------------------------------------------------
    Route::resource('/invoices', InvoiceController::class)->names('invoices');

    // Bulk actions for invoices
    Route::post('/invoices/bulk', [InvoiceController::class, 'bulk'])
        ->name('invoices.bulk');

    // -------------------------------------------------------------------------
    // Orders
    // -------------------------------------------------------------------------
    Route::get('/orders', [ManagementOrderController::class, 'index'])->name('orders.index');

    // Bulk actions for orders
    Route::post('/orders/bulk', [ManagementOrderController::class, 'bulk'])
        ->name('orders.bulk');

    Route::get('/orders/{order}', [ManagementOrderController::class, 'show'])
        ->name('orders.show');

    Route::patch('/orders/{order}/status', [ManagementOrderController::class, 'updateStatus'])
        ->name('orders.status');

    // -------------------------------------------------------------------------
    // Domain Providers
    // -------------------------------------------------------------------------
    Route::resource('domain_providers', DomainProviderController::class)->names('domain_providers');

    Route::get(
        'domain_providers/{domainProvider}/test-connection',
        [DomainProviderController::class, 'testConnection']
    )->name('domain_providers.test-connection');

    // -------------------------------------------------------------------------
    // Domain TLD Management (pricing, catalog, bulk actions)
    // -------------------------------------------------------------------------
    Route::get('/domain-tlds', [DomainTldController::class, 'index'])->name('domain_tlds.index');
    Route::post('/domain-tlds/sync', [DomainTldController::class, 'sync'])->name('domain_tlds.sync');
    Route::post('/domain-tlds/update-sale', [DomainTldController::class, 'updateSale'])->name('domain_tlds.update-sale');
    Route::post('/domain-tlds/save-catalog', [DomainTldController::class, 'saveCatalog'])->name('domain_tlds.save-catalog');
    Route::post('/domain-tlds/save-all', [DomainTldController::class, 'saveAll'])->name('domain_tlds.save-all');
    Route::post('/domain-tlds/apply-pricing', [DomainTldController::class, 'applyPricing'])->name('domain_tlds.apply-pricing');
    Route::delete('/domain-tlds/{domainTld}', [DomainTldController::class, 'destroy'])->name('domain_tlds.destroy');
    Route::post('/domain-tlds/bulk-destroy', [DomainTldController::class, 'bulk-destroy'])->name('domain_tlds.bulk-destroy');

    // -------------------------------------------------------------------------
    // Plan Categories
    // -------------------------------------------------------------------------
    Route::resource('/plan-categories', PlanCategoryController::class)->names('plan_categories');

    Route::post(
        '/plan-categories/{plan_category}/toggle',
        [PlanCategoryController::class, 'toggle']
    )->name('plan_categories.toggle');

    // Extra translation operations for plan categories (if needed)
    Route::delete(
        '/plan-categories/{plan_category}/translation/{lang}',
        [PlanCategoryController::class, 'destroyTranslation']
    )->name('plan_categories.translation.destroy');

    Route::post(
        '/plan-categories/{plan_category}/translation/{lang}',
        [PlanCategoryController::class, 'updateTranslation']
    )->name('plan_categories.translation.update');
});
