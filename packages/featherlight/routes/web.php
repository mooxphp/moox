<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::get('/themes/featherlight', function () {
        return view('featherlight::welcome');
    })->name('featherlight.welcome');
});
