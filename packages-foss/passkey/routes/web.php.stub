<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Moox\Passkey\Http\Controllers\LoginController;
use Moox\Passkey\Http\Controllers\RegisterController;

Route::group(['middleware' => ['web']], function () {
    Route::prefix('passkey-register')->controller(RegisterController::class)->group(function () {
        Route::post('/options', 'generateOptions');
        Route::post('/verify', 'verify');
    });

    Route::prefix('passkey-auth')->controller(LoginController::class)->group(function () {
        Route::post('/options', 'generateOptions');
        Route::post('/verify', 'verify');
    });

    Route::get('/passkey', function () {
        if (Auth::check()) {
            return view('welcome', [
                'user' => Auth::user(),
            ]);
        }

        return view('passkey::passkey');
    });

});
