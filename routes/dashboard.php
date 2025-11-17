<?php

use Illuminate\Support\Facades\Route;

// Dashboard Controllers
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\LanguageController;
use App\Http\Controllers\Dashboard\TranslationValueController;
use App\Http\Controllers\Dashboard\ServicesController;
use App\Http\Controllers\Dashboard\TestimonialsController;
use App\Http\Controllers\Dashboard\TemplateController;
use App\Http\Controllers\Dashboard\TemplateReviewController;
use App\Http\Controllers\Dashboard\MediaController;
use App\Http\Controllers\Dashboard\PortfolioController;

// Management Controllers
use App\Http\Controllers\Dashboard\Management\DomainController;
use App\Http\Controllers\Dashboard\Management\InvoiceController;
use App\Http\Controllers\Dashboard\Management\OrderController as ManagementOrderController;
use App\Http\Controllers\Dashboard\Management\PlanController;
use App\Http\Controllers\Dashboard\Management\ServerController;
use App\Http\Controllers\Dashboard\Management\SubscriptionController;
use App\Http\Controllers\Dashboard\Management\DomainProviderController;
use App\Http\Controllers\Dashboard\Management\DomainTldController;
use App\Http\Controllers\Dashboard\Management\PlanCategoryController;

// ─────────────────────────────────────────────
// تحويل /admin إلى الصفحة الرئيسية للداشبورد
// ─────────────────────────────────────────────
Route::get('admin/', function () {
    return redirect()->route('dashboard.home');
});

// ─────────────────────────────────────────────
// مجموعة مسارات لوحة التحكم
// prefix: /admin
// name: dashboard.*
// middleware: auth
// ─────────────────────────────────────────────
Route::group([
    'prefix' => 'admin',
    'as' => 'dashboard.',
    'middleware' => 'auth',
], function () {

    // ───── الصفحة الرئيسية وإعدادات عامة ─────
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/general_settings', [HomeController::class, 'general_settings'])->name('general_settings');

    // ───── المستخدمون ─────
    Route::resources([
        'users' => UserController::class,
    ]);
    Route::get('users/{user}/profile', [UserController::class, 'profile'])->name('users.profile');

    // ───── اللغات ─────
    Route::resource('languages', LanguageController::class)
        ->except(['show'])
        ->names('languages');

    // مسارات إضافية للغات (Ajax)
    Route::post('languages/{language}/toggle-rtl', [LanguageController::class, 'toggleRtl'])
        ->name('languages.toggle-rtl');

    Route::post('languages/{language}/toggle-status', [LanguageController::class, 'toggleStatus'])
        ->name('languages.toggle-status');

    Route::delete('languages/{language}/delete', [LanguageController::class, 'destroy'])
        ->name('languages.destroy-ajax');

    // ───── قيم الترجمة (Translation Values) ─────
    Route::resource('translation-values', TranslationValueController::class)
        ->except(['show', 'edit', 'update', 'destroy']);

    Route::get('translation-values/{key}/edit', [TranslationValueController::class, 'edit'])
        ->name('translation-values.edit');

    Route::post('translation-values/{key}/update', [TranslationValueController::class, 'update'])
        ->name('translation-values.update');

    Route::delete('translation-values/{key}/delete', [TranslationValueController::class, 'destroy'])
        ->name('translation-values.destroy');

    // Export / Import
    Route::get('translation-values/export', [TranslationValueController::class, 'export'])
        ->name('translation-values.export');

    Route::post('translation-values/import', [TranslationValueController::class, 'import'])
        ->name('translation-values.import');

    // ───── الخدمات والتقييمات والملفّات (Portfolio) ─────
    Route::resource('services', ServicesController::class);
    Route::resource('testimonials', TestimonialsController::class);
    Route::resource('portfolios', PortfolioController::class);

    // ───── صفحات ثابتة داخل الداشبورد (واجهات Livewire/Vue مثلاً) ─────
    Route::get('clients', function () {
        return view('dashboard.clients');
    })->name('clients');

    Route::get('pages', function () {
        return view('dashboard.page');
    })->name('pages');

    Route::get('menus', function () {
        return view('dashboard.header');
    })->name('headers');

    // ───── إدارة النطاقات (Domains) ─────
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

    Route::resource('domains', DomainController::class)->names('domains');

    // ───── الميديا (Media Library) ─────
    Route::get('media', function () {
        return view('dashboard.media');
    })->name('media');

    Route::get('/media-index', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/{id}', [MediaController::class, 'show'])->name('media.show');
    Route::get('/media/{id}/edit', [MediaController::class, 'edit'])->name('media.edit');
    Route::put('/media/{id}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');

    // ───── قوالب ووردبريس (Templates) ─────
    Route::get('templates/category', function () {
        // صفحة إدارة تصنيفات القوالب (تصميم مستقل)
        return view('dashboard.templates.CategoryTemplats');
    })->name('category');

    Route::resource('templates', TemplateController::class);

    // ───── مراجعات القوالب (Template Reviews) ─────
    Route::get('/reviews', [TemplateReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [TemplateReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/reject', [TemplateReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [TemplateReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/bulk', [TemplateReviewController::class, 'bulk'])->name('reviews.bulk');

    // ───── الاشتراكات (Hosting Subscriptions) ─────
    Route::post(
        '/subscriptions/{subscription}/sync',
        [SubscriptionController::class, 'syncWithProvider']
    )->name('subscriptions.sync');

    Route::get(
        '/subscriptions/{subscription}/cpanel-login',
        [SubscriptionController::class, 'cpanelLogin']
    )->name('subscriptions.cpanel-login');

    // تنصيب ووردبريس (المسار الحالي المستخدم في الواجهة)
    Route::post(
        '/subscriptions/{subscription}/install-wordpress',
        [SubscriptionController::class, 'installWordPressManual']
    )->name('subscriptions.install-wordpress');

    // خيار إضافي (أوتوماتيكي) في حال احتجته لاحقًا
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
    Route::post(
        '/subscriptions/bulk',
        [SubscriptionController::class, 'bulk']
    )->name('subscriptions.bulk');

    // Sync logs page
    Route::get(
        '/subscriptions/sync-logs',
        [SubscriptionController::class, 'syncLogs']
    )->name('subscriptions.sync-logs');

    Route::resource('/subscriptions', SubscriptionController::class)->names('subscriptions');

    // AJAX: اقتراح وتحقق من تفرد username
    Route::post(
        '/subscriptions/username-suggest',
        [SubscriptionController::class, 'suggestUsername']
    )->name('subscriptions.username-suggest');

    // ───── الخوادم (Servers) ─────
    Route::get('servers/{server}/test-connection', [ServerController::class, 'testConnection'])
        ->name('servers.test-connection');

    Route::get('servers/{server}/sso-whm', [ServerController::class, 'ssoWhm'])
        ->name('servers.sso-whm');

    Route::resource('servers', ServerController::class)->names('servers');

    Route::get('servers/{server}/accounts', [ServerController::class, 'accounts'])
        ->name('servers.accounts');

    Route::get('servers/{server}/packages', [ServerController::class, 'packages'])
        ->name('servers.packages');

    // ───── مواقع (Sites Overview) ─────
    Route::get('/sites', [HomeController::class, 'sites'])->name('sites');

    // ───── الخطط (Plans) ─────
    Route::resource('plans', PlanController::class)->names('plans');

    // Toggle plan active status
    Route::post('plans/{plan}/toggle', [PlanController::class, 'toggle'])
        ->name('plans.toggle');

    // ───── الفواتير (Invoices) ─────
    Route::resource('/invoices', InvoiceController::class)->names('invoices');

    // Bulk actions for invoices
    Route::post('/invoices/bulk', [InvoiceController::class, 'bulk'])
        ->name('invoices.bulk');

    // ───── الطلبات (Orders) ─────
    Route::get('/orders', [ManagementOrderController::class, 'index'])->name('orders.index');

    // Bulk actions for orders
    Route::post('/orders/bulk', [ManagementOrderController::class, 'bulk'])->name('orders.bulk');

    Route::get('/orders/{order}', [ManagementOrderController::class, 'show'])->name('orders.show');

    Route::patch('/orders/{order}/status', [ManagementOrderController::class, 'updateStatus'])
        ->name('orders.status');

    // ───── مزودي الدومينات (Domain Providers) ─────
    Route::resource('domain_providers', DomainProviderController::class)->names('domain_providers');

    Route::get(
        'domain_providers/{domainProvider}/test-connection',
        [DomainProviderController::class, 'testConnection']
    )->name('domain_providers.test-connection');

    // ───── إدارة أسعار TLD (Domain TLDs) ─────
    Route::get('/domain-tlds', [DomainTldController::class, 'index'])->name('domain_tlds.index');
    Route::post('/domain-tlds/sync', [DomainTldController::class, 'sync'])->name('domain_tlds.sync');
    Route::post('/domain-tlds/update-sale', [DomainTldController::class, 'updateSale'])->name('domain_tlds.update-sale');
    Route::post('/domain-tlds/save-catalog', [DomainTldController::class, 'saveCatalog'])->name('domain_tlds.save-catalog');
    Route::post('/domain-tlds/save-all', [DomainTldController::class, 'saveAll'])->name('domain_tlds.save-all');
    Route::post('/domain-tlds/apply-pricing', [DomainTldController::class, 'applyPricing'])->name('domain_tlds.apply-pricing');
    Route::delete('/domain-tlds/{domainTld}', [DomainTldController::class, 'destroy'])->name('domain_tlds.destroy');
    Route::post('/domain-tlds/bulk-destroy', [DomainTldController::class, 'bulkDestroy'])->name('domain_tlds.bulk-destroy');

    // ───── تصنيفات الخطط (Plan Categories) ─────
    Route::resource('/plan-categories', PlanCategoryController::class)->names('plan_categories');

    Route::post(
        '/plan-categories/{plan_category}/toggle',
        [PlanCategoryController::class, 'toggle']
    )->name('plan_categories.toggle');

    // عمليات إضافية للترجمات (إن وجدت)
    Route::delete(
        '/plan-categories/{plan_category}/translation/{lang}',
        [PlanCategoryController::class, 'destroyTranslation']
    )->name('plan_categories.translation.destroy');

    Route::post(
        '/plan-categories/{plan_category}/translation/{lang}',
        [PlanCategoryController::class, 'updateTranslation']
    )->name('plan_categories.translation.update');
});
