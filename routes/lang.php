<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\LocaleController;

// ✅ تغيير اللغة
Route::get('change-locale/{locale}', [LocaleController::class, 'change'])->name('change_locale');

// ✅ ترجمة كاملة كـ JSON (لـ Frontend أو JS)
Route::get('translate-json/{locale}', [LocaleController::class, 'translateJson'])->name('translate_json');
