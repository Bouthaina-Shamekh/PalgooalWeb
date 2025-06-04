<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('tamplate.home');
});

require __DIR__.'/dashboard.php';
