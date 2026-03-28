<?php

use App\Http\Controllers\Client\DomainController;
use App\Http\Controllers\Client\DomainDnsController;
use App\Http\Controllers\Client\DomainRenewalController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\InvoiceCheckoutController;
use App\Http\Controllers\Client\SubscriptionHomepageEditorController;
use App\Http\Controllers\Client\SubscriptionPageEditorController;
use App\Http\Controllers\Client\SubscriptionController;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('client/', function () {
    return redirect()->route('client.home');
});

Route::group([
    'middleware' => ['web', 'auth:client', 'client.dashboard.impersonation'],
    'prefix' => 'client',
    'as' => 'client.',
], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/update_account_clinet', [HomeController::class, 'updateClient'])->name('update_account');
    Route::post('/update_account_clinet', [HomeController::class, 'saveUpdateClient'])->name('update_account.save');

    Route::get('/search', [DomainController::class, 'search'])->name('domains.search');
    Route::post('/search', [DomainController::class, 'processSearch'])->name('domains.search.process');
    Route::get('/buy', [DomainController::class, 'buy'])->name('domains.buy');
    Route::post('/purchase', [DomainController::class, 'purchase'])->name('domains.purchase');
    Route::patch('domains/{domain}/auto-renew', [DomainController::class, 'toggleAutoRenew'])->name('domains.auto-renew');
    Route::post('domains/{domain}/renew', [DomainRenewalController::class, 'store'])->name('domains.renew');
    Route::get('domains/{domain}/dns', [DomainDnsController::class, 'edit'])->name('domains.dns.edit');
    Route::put('domains/{domain}/dns', [DomainDnsController::class, 'update'])->name('domains.dns.update');

    Route::resource('domains', DomainController::class)->names('domains');

    Route::get('subscriptions', [HomeController::class, 'subscriptions'])->name('subscriptions');
    Route::get('subscriptions/{subscription}/site', [SubscriptionController::class, 'site'])->name('subscriptions.site');
    Route::post('subscriptions/{subscription}/verify-domain', [SubscriptionController::class, 'verifyDomain'])->name('subscriptions.verify-domain');
    Route::get('subscriptions/{subscription}/content', [SubscriptionController::class, 'content'])->name('subscriptions.content');
    Route::get('subscriptions/{subscription}/pages', [SubscriptionPageEditorController::class, 'pages'])->name('subscriptions.pages');
    Route::post('subscriptions/{subscription}/pages', [SubscriptionPageEditorController::class, 'storePage'])->name('subscriptions.pages.store');
    Route::match(['put', 'patch'], 'subscriptions/{subscription}/pages/{page}', [SubscriptionPageEditorController::class, 'updatePageSettings'])->name('subscriptions.pages.update');
    Route::post('subscriptions/{subscription}/pages/{page}/set-home', [SubscriptionPageEditorController::class, 'setHomePage'])->name('subscriptions.pages.set-home');
    Route::delete('subscriptions/{subscription}/pages/{page}', [SubscriptionPageEditorController::class, 'destroyPage'])->name('subscriptions.pages.destroy');
    Route::prefix('subscriptions/{subscription}/pages/{page}/editor')
        ->name('subscriptions.pages.editor.')
        ->group(function () {
            Route::get('/', [SubscriptionPageEditorController::class, 'pageIndex'])->name('index');
            Route::get('preview', [SubscriptionPageEditorController::class, 'pagePreview'])->name('preview');
            Route::post('sections/quick-store', [SubscriptionPageEditorController::class, 'pageQuickStore'])->name('quick-store');
            Route::post('sections/reorder', [SubscriptionPageEditorController::class, 'pageReorder'])->name('reorder');
            Route::get('sections/{section}/editor', [SubscriptionPageEditorController::class, 'pageEditorPanel'])
                ->whereNumber('section')
                ->name('editor');
            Route::get('sections/{section}/edit', [SubscriptionPageEditorController::class, 'pageEdit'])
                ->whereNumber('section')
                ->name('edit');
            Route::match(['post', 'put', 'patch'], 'sections/{section}', [SubscriptionPageEditorController::class, 'pageUpdate'])
                ->whereNumber('section')
                ->name('update');
            Route::post('sections/{section}/toggle-active', [SubscriptionPageEditorController::class, 'pageToggleActive'])
                ->whereNumber('section')
                ->name('toggle-active');
            Route::post('sections/{section}/rename', [SubscriptionPageEditorController::class, 'pageRename'])
                ->whereNumber('section')
                ->name('rename');
            Route::post('sections/{section}/duplicate', [SubscriptionPageEditorController::class, 'pageDuplicate'])
                ->whereNumber('section')
                ->name('duplicate');
            Route::delete('sections/{section}', [SubscriptionPageEditorController::class, 'pageDestroy'])
                ->whereNumber('section')
                ->name('destroy');
        });
    Route::prefix('subscriptions/{subscription}/homepage/editor')
        ->name('subscriptions.homepage-editor.')
        ->group(function () {
            Route::get('/', [SubscriptionHomepageEditorController::class, 'homepageIndex'])->name('index');
            Route::get('preview', [SubscriptionHomepageEditorController::class, 'homepagePreview'])->name('preview');
            Route::post('sections/quick-store', [SubscriptionHomepageEditorController::class, 'homepageQuickStore'])->name('quick-store');
            Route::post('sections/reorder', [SubscriptionHomepageEditorController::class, 'homepageReorder'])->name('reorder');
            Route::get('sections/{section}/editor', [SubscriptionHomepageEditorController::class, 'homepageEditorPanel'])
                ->whereNumber('section')
                ->name('editor');
            Route::get('sections/{section}/edit', [SubscriptionHomepageEditorController::class, 'homepageEdit'])
                ->whereNumber('section')
                ->name('edit');
            Route::match(['post', 'put', 'patch'], 'sections/{section}', [SubscriptionHomepageEditorController::class, 'homepageUpdate'])
                ->whereNumber('section')
                ->name('update');
            Route::post('sections/{section}/toggle-active', [SubscriptionHomepageEditorController::class, 'homepageToggleActive'])
                ->whereNumber('section')
                ->name('toggle-active');
            Route::post('sections/{section}/rename', [SubscriptionHomepageEditorController::class, 'homepageRename'])
                ->whereNumber('section')
                ->name('rename');
            Route::post('sections/{section}/duplicate', [SubscriptionHomepageEditorController::class, 'homepageDuplicate'])
                ->whereNumber('section')
                ->name('duplicate');
            Route::delete('sections/{section}', [SubscriptionHomepageEditorController::class, 'homepageDestroy'])
                ->whereNumber('section')
                ->name('destroy');
        });
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::get('invoices/{invoice}/checkout', [InvoiceCheckoutController::class, 'show'])->name('invoices.checkout');
    Route::post('invoices/{invoice}/checkout', [InvoiceCheckoutController::class, 'process'])->name('invoices.checkout.process');
    Route::get('invoices', [HomeController::class, 'invoices'])->name('invoices');
});

Route::post('client/login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    $client = Client::where('email', $credentials['email'] ?? '')->first();

    if ($client && !$client->can_login) {
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => 'Login access has been disabled for this account.',
            ], 403);
        }

        return redirect()->back()
            ->withErrors(['email' => 'Login access has been disabled for this account.'])
            ->withInput();
    }

    $credentials['can_login'] = 1;

    if (Auth::guard('client')->attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();

        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            $user = Auth::guard('client')->user();

            return response()->json([
                'ok' => true,
                'user' => [
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'email' => $user->email ?? '',
                ],
                'message' => 'Login successful.',
            ]);
        }

        $referer = $request->headers->get('referer');

        if ($referer && str_contains($referer, 'checkout')) {
            return redirect()->to($referer . (str_contains($referer, '?') ? '&' : '?') . 'review=1')
                ->with('success', 'Login successful.');
        }

        return redirect()->back()->with('success', 'Login successful.');
    }

    if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
        return response()->json([
            'ok' => false,
            'message' => 'Invalid login credentials.',
        ], 422);
    }

    return redirect()->back()
        ->withErrors(['email' => 'Invalid login credentials.'])
        ->withInput();
})->name('login.store');

Route::post('client/logout', function (Request $request) {
    Auth::guard('client')->logout();
    $request->session()->forget([
        'client_impersonated_by_admin',
        'client_impersonator_admin_id',
    ]);
    $request->session()->regenerate();
    $request->session()->regenerateToken();

    $referer = $request->headers->get('referer');

    if ($referer && str_contains($referer, 'checkout')) {
        return redirect()->to($referer . (str_contains($referer, '?') ? '&' : '?') . 'review=1');
    }

    return redirect()->back();
})->name('client.logout');
