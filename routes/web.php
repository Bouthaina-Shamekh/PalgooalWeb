<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('tamplate.index');
});

require __DIR__.'/dashboard.php';
