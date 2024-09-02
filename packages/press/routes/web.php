<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {

    if (config('press.redirect_index') === true) {
        Route::get('/', function () {
            return Redirect::to('https://'.$_SERVER['SERVER_NAME'].config('press.wordpress_slug'));
        });
    }

    if (config('press.redirect_logout') === true) {
        Route::get('/moox/logout', function () {
            Auth::logout();
            request()->session()->invalidate();

            return Redirect::to('https://'.$_SERVER['SERVER_NAME'].'/');
        });
    }

    if (config('press.registration') === true) {
        Route::get('/register', function () {
            if (Auth::check()) {
                return Redirect::to('https://'.$_SERVER['SERVER_NAME'].'/');
            }

            return view('filament-panels::pages.auth.register');
        });
    }

    // TODO: Would be better as middleware?
    // this must be the last route
    if (config('press.redirect_to_wp') === true) {
        Route::any('{any}', function ($any) {
            if (! str_contains(request()->server()['REQUEST_URI'], config('press.wordpress_slug').'/')) {
                return redirect('/wp/'.ltrim(request()->path(), '/'));
            }
        })->where('any', '.*');
    }
});
