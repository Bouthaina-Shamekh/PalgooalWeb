<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Clinet\HomeController;



Route::get('client/', function () {
    return redirect()->route('client.home');

});

Route::group([
    'middleware' => ['web','auth:client'],
    'prefix' => 'client',
    'as' => 'client.',
], function () {

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/update_account_clinet', [HomeController::class, 'updateClient'])->name('update_account');
});
