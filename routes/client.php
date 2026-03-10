<?php

use App\Http\Controllers\Client\DomainController;
use App\Http\Controllers\Client\DomainDnsController;
use App\Http\Controllers\Client\DomainRenewalController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\InvoiceCheckoutController;
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
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('subscriptions/{subscription}/sections/{section}', [SubscriptionController::class, 'updateSection'])
        ->name('subscriptions.sections.update');
    Route::get('subscriptions/{subscription}/pages/{page}/builder', [\App\Http\Controllers\Client\PageBuilderController::class, 'builder'])
        ->name('subscriptions.pages.builder');
    Route::post('subscriptions/{subscription}/pages/{page}/sections/reorder', [\App\Http\Controllers\Client\PageBuilderController::class, 'reorder'])
        ->name('subscriptions.pages.sections.reorder');
    Route::post('subscriptions/{subscription}/pages/{page}/sections/add', [\App\Http\Controllers\Client\PageBuilderController::class, 'addSection'])
        ->name('subscriptions.pages.sections.add');
    Route::post('subscriptions/{subscription}/sections/{section}/update', [\App\Http\Controllers\Client\PageBuilderController::class, 'updateSection'])
        ->name('client.subscriptions.sections.update');
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
