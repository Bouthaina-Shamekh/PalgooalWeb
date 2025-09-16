<?php

use App\Http\Controllers\Dashboard\DomainController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\LanguageController;
use App\Http\Controllers\Dashboard\Management\InvoiceController;
use App\Http\Controllers\Dashboard\Management\OrderController as ManagementOrderController;
use App\Http\Controllers\Dashboard\Management\Plan;
use App\Http\Controllers\Dashboard\Management\PlanController;
use App\Http\Controllers\Dashboard\Management\ServerController;
use App\Http\Controllers\Dashboard\Management\SubscriptionController;
use App\Http\Controllers\Dashboard\MediaController;
use App\Http\Controllers\Dashboard\ServicesController;
use App\Http\Controllers\Dashboard\TranslationValueController;
use App\Http\Controllers\Dashboard\ServicesTranslationController;
use App\Http\Controllers\Dashboard\TemplateController;
use App\Livewire\Services;
use App\Http\Controllers\Dashboard\TemplateReviewController as AdminReview;
use App\Http\Controllers\Dashboard\TemplateReviewController;
use App\Http\Controllers\Dashboard\Management\DomainProviderController;
use App\Http\Controllers\Dashboard\Management\DomainTldController;
use App\Http\Controllers\Dashboard\PortfolioController;

Route::get('admin/', function () {
    return redirect()->route('dashboard.home');
});
Route::group([
    'prefix' => 'admin',
    'as' => 'dashboard.',
    'middleware' => 'auth'
], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/general_settings', [HomeController::class, 'general_settings'])->name('general_settings');
    Route::get('users/{user}/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::resources([
        'users' => UserController::class,
    ]);
    // Resource routes:
    Route::resource('languages', LanguageController::class)->except(['show'])->names('languages');
    // Extra AJAX routes:
    Route::post('admin/languages/{language}/toggle-rtl', [LanguageController::class, 'toggleRtl'])->name('languages.toggle-rtl');
    Route::post('admin/languages/{language}/toggle-status', [LanguageController::class, 'toggleStatus'])->name('languages.toggle-status');
    Route::delete('admin/languages/{language}/delete', [LanguageController::class, 'destroy'])->name('dashboardlanguages.destroy-ajax');
    Route::resource('translation-values', TranslationValueController::class)->except(['show', 'edit', 'update', 'destroy']);
    Route::get('translation-values/{key}/edit', [TranslationValueController::class, 'edit'])->name('translation-values.edit');
    Route::post('translation-values/{key}/update', [TranslationValueController::class, 'update'])->name('translation-values.update');
    Route::delete('translation-values/{key}/delete', [TranslationValueController::class, 'destroy'])->name('translation-values.destroy');

    // Export Translations
    Route::get('translation-values/export', [TranslationValueController::class, 'export'])->name('translation-values.export');
    // Import Translations
    Route::post('translation-values/import', [TranslationValueController::class, 'import'])->name('translation-values.import');

    Route::get('services', function () {
        return view('dashboard.services');
    })->name('services');

    Route::get('feedbacks', function () {
        return view('dashboard.feedbacks');
    })->name('feedbacks');

    Route::resource('portfolios', PortfolioController::class);

    Route::get('clients', function () {
        return view('dashboard.clients');
    })->name('clients');

    Route::get('pages', function () {
        return view('dashboard.page');
    })->middleware(['auth'])->name('pages');

    Route::get('menus', function () {
        return view('dashboard.header');
    })->middleware(['auth'])->name('headers');


    Route::resource('domains', DomainController::class)->names('domains');

    //media
    Route::get('media', function () {
        return view('dashboard.media');
    })->name('media');
    Route::get('/media-index', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/{id}', [MediaController::class, 'show'])->name('media.show');
    Route::get('/media/{id}/edit', [MediaController::class, 'edit'])->name('media.edit');
    Route::put('/media/{id}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('templates/category', function () {
        return view('dashboard.templates.CategoryTemplats');
    })->name('category');


    // Route::get('template', function () {
    //     return view('dashboard.template.Templates');
    // })->name('template');
    Route::resource('templates', TemplateController::class);
    Route::get('/reviews', [TemplateReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [TemplateReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('/reviews/{review}/reject', [TemplateReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [TemplateReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/bulk', [TemplateReviewController::class, 'bulk'])->name('reviews.bulk');

    Route::post('/subscriptions/{subscription}/sync', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'syncWithProvider'])->name('subscriptions.sync');
    Route::get('/subscriptions/{subscription}/cpanel-login', [SubscriptionController::class, 'cpanelLogin'])->name('subscriptions.cpanel-login');
    Route::post('/subscriptions/{subscription}/install-wordpress', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'installWordPress'])->name('subscriptions.install-wordpress');
    Route::post('/subscriptions/{subscription}/install-wordpress', [SubscriptionController::class, 'installWordPressManual'])->name('subscriptions.install-wordpress');
    Route::post('/subscriptions/{subscription}/suspend', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'suspendToProvider'])->name('subscriptions.suspend');
    Route::post('/subscriptions/{subscription}/unsuspend', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'unsuspendToProvider'])->name('subscriptions.unsuspend');
    Route::post('/subscriptions/{subscription}/terminate', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'terminateToProvider'])->name('subscriptions.terminate');
    // Bulk actions for subscriptions (select multiple and perform an action)
    Route::post('/subscriptions/bulk', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'bulk'])->name('subscriptions.bulk');
    // Sync logs page (shows last_sync_message and timestamps)
    Route::get('/subscriptions/sync-logs', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'syncLogs'])->name('subscriptions.sync-logs');
    Route::resource('/subscriptions', SubscriptionController::class)->names('subscriptions');
    // AJAX: اقتراح وتحقق من تفرد username
    Route::post('/subscriptions/username-suggest', [\App\Http\Controllers\Dashboard\Management\SubscriptionController::class, 'suggestUsername'])->name('subscriptions.username-suggest');
    Route::get('servers/{server}/test-connection', [ServerController::class, 'testConnection'])->name('servers.test-connection');
    Route::get('servers/{server}/sso-whm', [ServerController::class, 'ssoWhm'])->name('servers.sso-whm');
    Route::resource('servers', ServerController::class)->names('servers');
    Route::get('servers/{server}/accounts', [ServerController::class, 'accounts'])->name('servers.accounts');
    Route::get('/sites', [HomeController::class, 'sites'])->name('sites');
    Route::resource('plans', PlanController::class);
    Route::resource('/invoices', InvoiceController::class)->names('invoices');
    // Bulk actions for invoices
    Route::post('/invoices/bulk', [InvoiceController::class, 'bulk'])->name('invoices.bulk');
    Route::get('/orders', [ManagementOrderController::class, 'index'])->name('orders.index');
    // Bulk actions for orders
    Route::post('/orders/bulk', [ManagementOrderController::class, 'bulk'])->name('orders.bulk');
    Route::get('/orders/{order}', [ManagementOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [ManagementOrderController::class, 'updateStatus'])->name('orders.status');

    // مزودي الدومينات
    Route::resource('domain_providers', DomainProviderController::class)->names('domain_providers');
    Route::get('domain_providers/{domainProvider}/test-connection', [DomainProviderController::class, 'testConnection'])->name('domain_providers.test-connection');
    // إدارة أسعار TLD (لوحة الإدارة)
    // ملاحظة: داخل هذا الجروب الاسم يُسبق تلقائياً بـ dashboard. لذا لا نكرر dashboard. في اسم الراوت.
    Route::get('/domain-tlds', [DomainTldController::class, 'index'])->name('domain_tlds.index');
    Route::post('/domain-tlds/sync', [DomainTldController::class, 'sync'])->name('domain_tlds.sync');
    Route::post('/domain-tlds/update-sale', [DomainTldController::class, 'updateSale'])->name('domain_tlds.update-sale');
    Route::post('/domain-tlds/save-catalog', [DomainTldController::class, 'saveCatalog'])->name('domain_tlds.save-catalog');
    Route::post('/domain-tlds/save-all', [DomainTldController::class, 'saveAll'])->name('domain_tlds.save-all');
    Route::post('/domain-tlds/apply-pricing', [DomainTldController::class, 'applyPricing'])->name('domain_tlds.apply-pricing');
    Route::delete('/domain-tlds/{domainTld}', [DomainTldController::class, 'destroy'])->name('domain_tlds.destroy');
    Route::post('/domain-tlds/bulk-destroy', [DomainTldController::class, 'bulkDestroy'])->name('domain_tlds.bulk-destroy');
});
