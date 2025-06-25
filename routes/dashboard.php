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
], function()
{
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/general_settings', [HomeController::class, 'general_settings'])->name('general_settings');


    Route::get('users/{user}/profile', [UserController::class, 'profile'])->name('users.profile');

    Route::resources([
        'users' => UserController::class,
    ]);
    // Resource routes:
    Route::resource('languages', LanguageController::class)->except(['show'])->names('languages');

    // Extra AJAX routes:
    Route::post('admin/languages/{language}/toggle-rtl', [LanguageController::class, 'toggleRtl'])
        ->name('languages.toggle-rtl');

    Route::post('admin/languages/{language}/toggle-status', [LanguageController::class, 'toggleStatus'])
        ->name('languages.toggle-status');

    Route::delete('admin/languages/{language}/delete', [LanguageController::class, 'destroy'])
        ->name('dashboardlanguages.destroy-ajax');
    Route::resource('translation-values', TranslationValueController::class)->except(['show', 'edit', 'update', 'destroy']);
    Route::get('translation-values/{key}/edit', [TranslationValueController::class, 'edit'])->name('translation-values.edit');
    Route::post('translation-values/{key}/update', [TranslationValueController::class, 'update'])->name('translation-values.update');
    Route::delete('translation-values/{key}/delete', [TranslationValueController::class, 'destroy'])->name('translation-values.destroy');
    // Export Translations
    Route::get('translation-values/export', [TranslationValueController::class, 'export'])
    ->name('translation-values.export');
    // Import Translations
    Route::post('translation-values/import', [TranslationValueController::class, 'import'])
    ->name('translation-values.import');
    
    //  Route::get('services',  [Services::class, 'index'])->name('services');
    //  Route::get('service_translations', [ServicesTranslationController::class, 'index'])->name('service_translations');

        Route::get('services', function () {
        return view('dashboard.services');
        })->middleware(['auth'])->name('services');

        Route::get('feedbacks', function () {
        return view('dashboard.feedbacks');
        })->middleware(['auth'])->name('feedbacks');


    //media
    Route::get('media', function () {
        return view('dashboard.media');
        })->middleware(['auth'])->name('media');
    });


