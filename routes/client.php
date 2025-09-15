<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Clinet\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



Route::get('client/', function () {
    return redirect()->route('client.home');
});

Route::group([
    'middleware' => ['web', 'auth:client'],
    'prefix' => 'client',
    'as' => 'client.',
], function () {

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/update_account_clinet', [HomeController::class, 'updateClient'])->name('update_account');
    Route::get('/domain-name-search', [HomeController::class, 'domainNameSearch'])->name('domain_name_search');
    Route::get('domain-table', [HomeController::class, 'domains'])->name('domain_table');
    Route::get('subscriptions', [HomeController::class, 'subscriptions'])->name('subscriptions');
    Route::get('invoices', [HomeController::class, 'invoices'])->name('invoices');
});

// دعم النماذج التي تستخدم اسم المسار login.store
Route::post('client/login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::guard('client')->attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();
        // ردّ JSON عند الطلب عبر AJAX
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            $u = Auth::guard('client')->user();
            return response()->json([
                'ok' => true,
                'user' => [
                    'first_name' => $u->first_name ?? '',
                    'last_name'  => $u->last_name ?? '',
                    'email'      => $u->email ?? '',
                ],
                'message' => 'تم تسجيل الدخول بنجاح.'
            ]);
        }
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, 'checkout')) {
            return redirect()->to($referer . (str_contains($referer, '?') ? '&' : '?') . 'review=1')->with('success', 'تم تسجيل الدخول بنجاح!');
        }
        return redirect()->back()->with('success', 'تم تسجيل الدخول بنجاح!');
    }
    if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
        return response()->json(['ok' => false, 'message' => 'بيانات الدخول غير صحيحة.'], 422);
    }
    return redirect()->back()->withErrors(['email' => 'بيانات الدخول غير صحيحة'])->withInput();
})->name('login.store');

// تسجيل خروج العميل
Route::post('client/logout', function (Request $request) {
    Auth::guard('client')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    $referer = $request->headers->get('referer');
    if ($referer && str_contains($referer, 'checkout')) {
        return redirect()->to($referer . (str_contains($referer, '?') ? '&' : '?') . 'review=1');
    }
    return redirect()->back();
})->name('client.logout');
