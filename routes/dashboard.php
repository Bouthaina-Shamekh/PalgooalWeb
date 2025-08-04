<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\LanguageController;
use App\Http\Controllers\Dashboard\ServicesController;
use App\Http\Controllers\Dashboard\TranslationValueController;
use App\Http\Controllers\Dashboard\ServicesTranslationController;
use App\Livewire\Services;

Route::get('/admin', function () {
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

    Route::get('portfolios', function () {
        return view('dashboard.portfolios');
    })->name('portfolios');

    Route::get('clients', function () {
        return view('dashboard.clients');
    })->name('clients');

    Route::get('pages', function () {
        return view('dashboard.page');
    })->middleware(['auth'])->name('pages');

    Route::get('menus', function () {
        return view('dashboard.header');
    })->middleware(['auth'])->name('headers');
    Route::get('/domains', [HomeController::class, 'domains'])->name('domains');



    //media
    Route::get('media', function () {
        return view('dashboard.media');
    })->name('media');

    Route::get('template/category', function () {
        return view('dashboard.template.CategoryTemplats');
    })->name('category');

    Route::get('template', function () {
        return view('dashboard.template.Templates');
    })->name('template');
});
