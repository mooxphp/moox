<?php

use Illuminate\Support\Facades\Route;
use Moox\LoginLink\Http\Controllers\LoginLinkController;

Route::group(['middleware' => ['web']], function () {
    Route::get('/login-link', [LoginLinkController::class, 'requestForm'])->name('login-link.request');
    Route::post('/login-link', [LoginLinkController::class, 'sendLink'])->name('login-link.send');
    Route::get('/login-link/{userId}-{token}', [LoginLinkController::class, 'authenticate'])->name('login-link.authenticate');
});
