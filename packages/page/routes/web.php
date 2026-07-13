<?php

use Illuminate\Support\Facades\Route;
use Moox\Page\Http\Controllers\PageController;

Route::get('/', [PageController::class, 'index'])->name('home');

Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9\-_]+')
    ->name('page.show');
