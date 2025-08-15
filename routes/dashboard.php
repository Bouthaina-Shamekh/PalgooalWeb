<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\LanguageController;
use App\Http\Controllers\Dashboard\MediaController;
use App\Http\Controllers\Dashboard\ServicesController;
use App\Http\Controllers\Dashboard\TranslationValueController;
use App\Http\Controllers\Dashboard\ServicesTranslationController;
use App\Http\Controllers\Dashboard\TemplateController;
use App\Livewire\Services;
use App\Http\Controllers\Dashboard\TemplateReviewController as AdminReview;
use App\Http\Controllers\Dashboard\TemplateReviewController;

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
    Route::resource('Languages', LanguageController::class)->except(['show'])->names('languages');

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
});
