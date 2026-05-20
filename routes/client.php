<?php

use App\Http\Controllers\Client\DomainController;
use App\Http\Controllers\Client\DomainDnsController;
use App\Http\Controllers\Client\DomainRenewalController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\InvoiceCheckoutController;
use App\Http\Controllers\Client\SubscriptionHomepageEditorController;
use App\Http\Controllers\Client\SubscriptionPageEditorController;
use App\Http\Controllers\Client\SubscriptionController;
use App\Http\Controllers\Client\SubscriptionSiteShellEditorController;
use App\Http\Controllers\Client\SubscriptionThemeController;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('client/', function () {
    return redirect()->route('client.home');
});

// Explicit GET/POST auth routes — required for route:cache compatibility
// (FortifyServiceProvider dynamic config doesn't work in CLI/cache context)
Route::get('client/login', function () {
    if (Auth::guard('client')->check()) {
        return redirect()->route('client.home');
    }
    return view('auth.client.login');
})->middleware('web')->name('client.login');

Route::get('client/register', function () {
    if (Auth::guard('client')->check()) {
        return redirect()->route('client.home');
    }
    return view('auth.client.register');
})->middleware('web')->name('client.register');

Route::get('client/forgot-password', function () {
    if (Auth::guard('client')->check()) {
        return redirect()->route('client.home');
    }
    return view('auth.client.forgot-password');
})->middleware('web')->name('client.password.request');

Route::post('client/forgot-password', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => ['required', 'email', 'max:191']]);

    // Always attempt to send — never reveal whether the email exists (anti-enumeration).
    \Illuminate\Support\Facades\Password::broker('clients')
        ->sendResetLink($request->only('email'));

    return back()->with('status', __('If this email is registered, you will receive a password reset link shortly.'));
})->middleware(['web', 'throttle:5,1'])->name('client.password.email');

Route::post('client/register', function (\Illuminate\Http\Request $request) {
    $creator = app(\App\Actions\Fortify\CreateNewUser::class);
    // Force client guard so CreateNewUser creates a Client model
    \Illuminate\Support\Facades\Config::set('fortify.guard', 'client');

    try {
        $client = $creator->create($request->only([
            'first_name', 'last_name', 'company_name',
            'email', 'password', 'confirm_password',
            'phone', 'zip_code', 'avatar',
        ]));
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->route('client.register')
            ->withErrors($e->errors())
            ->withInput();
    }

    Auth::guard('client')->login($client);
    $request->session()->regenerate();

    return redirect()->route('client.home');
})->middleware(['web', 'throttle:5,1'])->name('client.register.store');

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
    Route::post('subscriptions/{subscription}/brand-settings', [SubscriptionThemeController::class, 'update'])->name('subscriptions.brand-settings.update');
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
    Route::prefix('subscriptions/{subscription}/site-header/editor')
        ->name('subscriptions.site-header-editor.')
        ->group(function () {
            Route::get('/', [SubscriptionSiteShellEditorController::class, 'headerIndex'])->name('index');
            Route::get('preview', [SubscriptionSiteShellEditorController::class, 'headerPreview'])->name('preview');
            Route::post('sections/quick-store', [SubscriptionSiteShellEditorController::class, 'headerQuickStore'])->name('quick-store');
            Route::post('sections/reorder', [SubscriptionSiteShellEditorController::class, 'headerReorder'])->name('reorder');
            Route::get('sections/{section}/editor', [SubscriptionSiteShellEditorController::class, 'headerEditorPanel'])
                ->whereNumber('section')
                ->name('editor');
            Route::get('sections/{section}/edit', [SubscriptionSiteShellEditorController::class, 'headerEdit'])
                ->whereNumber('section')
                ->name('edit');
            Route::match(['post', 'put', 'patch'], 'sections/{section}', [SubscriptionSiteShellEditorController::class, 'headerUpdate'])
                ->whereNumber('section')
                ->name('update');
            Route::post('sections/{section}/toggle-active', [SubscriptionSiteShellEditorController::class, 'headerToggleActive'])
                ->whereNumber('section')
                ->name('toggle-active');
            Route::post('sections/{section}/rename', [SubscriptionSiteShellEditorController::class, 'headerRename'])
                ->whereNumber('section')
                ->name('rename');
            Route::post('sections/{section}/duplicate', [SubscriptionSiteShellEditorController::class, 'headerDuplicate'])
                ->whereNumber('section')
                ->name('duplicate');
            Route::delete('sections/{section}', [SubscriptionSiteShellEditorController::class, 'headerDestroy'])
                ->whereNumber('section')
                ->name('destroy');
        });
    Route::prefix('subscriptions/{subscription}/site-footer/editor')
        ->name('subscriptions.site-footer-editor.')
        ->group(function () {
            Route::get('/', [SubscriptionSiteShellEditorController::class, 'footerIndex'])->name('index');
            Route::get('preview', [SubscriptionSiteShellEditorController::class, 'footerPreview'])->name('preview');
            Route::post('sections/quick-store', [SubscriptionSiteShellEditorController::class, 'footerQuickStore'])->name('quick-store');
            Route::post('sections/reorder', [SubscriptionSiteShellEditorController::class, 'footerReorder'])->name('reorder');
            Route::get('sections/{section}/editor', [SubscriptionSiteShellEditorController::class, 'footerEditorPanel'])
                ->whereNumber('section')
                ->name('editor');
            Route::get('sections/{section}/edit', [SubscriptionSiteShellEditorController::class, 'footerEdit'])
                ->whereNumber('section')
                ->name('edit');
            Route::match(['post', 'put', 'patch'], 'sections/{section}', [SubscriptionSiteShellEditorController::class, 'footerUpdate'])
                ->whereNumber('section')
                ->name('update');
            Route::post('sections/{section}/toggle-active', [SubscriptionSiteShellEditorController::class, 'footerToggleActive'])
                ->whereNumber('section')
                ->name('toggle-active');
            Route::post('sections/{section}/rename', [SubscriptionSiteShellEditorController::class, 'footerRename'])
                ->whereNumber('section')
                ->name('rename');
            Route::post('sections/{section}/duplicate', [SubscriptionSiteShellEditorController::class, 'footerDuplicate'])
                ->whereNumber('section')
                ->name('duplicate');
            Route::delete('sections/{section}', [SubscriptionSiteShellEditorController::class, 'footerDestroy'])
                ->whereNumber('section')
                ->name('destroy');
        });
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::get('invoices/{invoice}/checkout', [InvoiceCheckoutController::class, 'show'])->name('invoices.checkout');
    Route::post('invoices/{invoice}/checkout', [InvoiceCheckoutController::class, 'process'])->name('invoices.checkout.process');
    Route::get('invoices', [HomeController::class, 'invoices'])->name('invoices');
});

$resolveClientRedirectTarget = static function (Request $request): ?string {
    $candidate = trim((string) $request->input('redirect_to', ''));

    if ($candidate === '') {
        $candidate = trim((string) $request->headers->get('referer', ''));
    }

    if ($candidate === '' || preg_match('/[\r\n]/', $candidate) === 1) {
        return null;
    }

    if (str_starts_with($candidate, '//')) {
        return null;
    }

    $path = '';

    if (str_starts_with($candidate, '/')) {
        $path = (string) parse_url($candidate, PHP_URL_PATH);
    } else {
        $parts = parse_url($candidate);

        if ($parts === false) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host !== '' && $host !== strtolower($request->getHost())) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '/');
    }

    if ($path !== '' && preg_match('#^/(?:assets|build|storage)(?:/|$)#', $path) === 1) {
        return null;
    }

    return $candidate;
};

$appendCheckoutReviewQuery = static function (string $target): string {
    if (!str_contains($target, 'checkout')) {
        return $target;
    }

    $query = (string) parse_url($target, PHP_URL_QUERY);
    if (preg_match('/(?:^|&)review=/', $query) === 1) {
        return $target;
    }

    return $target . (str_contains($target, '?') ? '&' : '?') . 'review=1';
};

$redirectClientResponse = static function (Request $request) use ($resolveClientRedirectTarget, $appendCheckoutReviewQuery) {
    $redirectTarget = $resolveClientRedirectTarget($request);

    if (!$redirectTarget) {
        return redirect()->back();
    }

    if (str_contains($redirectTarget, 'checkout')) {
        $redirectTarget = $appendCheckoutReviewQuery($redirectTarget);
    }

    return redirect()->to($redirectTarget);
};

Route::post('client/login', function (Request $request) use ($redirectClientResponse) {
    $request->validate([
        'email'    => ['required', 'email', 'max:191'],
        'password' => ['required', 'string', 'max:255'],
    ]);
    $credentials = $request->only('email', 'password');
    $client = Client::where('email', $credentials['email'] ?? '')->first();

    if ($client && !$client->can_login) {
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => 'Login access has been disabled for this account.',
            ], 403);
        }

        return $redirectClientResponse($request)
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

        // Redirect to the intended page or the client dashboard.
        $target = null;
        $redirectInput = trim((string) $request->input('redirect_to', ''));
        if ($redirectInput !== '' && str_starts_with($redirectInput, '/') &&
            !preg_match('#^/(?:assets|build|storage)(?:/|$)#', $redirectInput)) {
            $target = $redirectInput;
        }
        return redirect()->to($target ?? route('client.home'))
            ->with('success', __('Login successful.'));
    }

    if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
        return response()->json([
            'ok' => false,
            'message' => 'Invalid login credentials.',
        ], 422);
    }

    return $redirectClientResponse($request)
        ->withErrors(['email' => 'Invalid login credentials.'])
        ->withInput();
})->middleware('throttle:5,1')->name('client.login.store');

Route::post('client/logout', function (Request $request) use ($redirectClientResponse) {
    Auth::guard('client')->logout();
    $request->session()->forget([
        'client_impersonated_by_admin',
        'client_impersonator_admin_id',
    ]);
    $request->session()->regenerate();
    $request->session()->regenerateToken();

    return $redirectClientResponse($request);
})->name('client.logout');
