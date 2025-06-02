<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\UserController;

Route::get('/admin', function () {
    return redirect()->route('dashboard.home');
});
Route::group([
    'prefix' => 'admin',
    'as' => 'dashboard.',
    'middleware' => 'auth'
], function()
{
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('users/{user}/profile', [UserController::class, 'profile'])->name('users.profile');

    Route::resources([
        'users' => UserController::class,
    ]);
});

