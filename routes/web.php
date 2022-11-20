<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/custom', function () {
    return view('custom.overview');
});

Route::get('packages', function () {
    return view('packages.overview');
});

Route::get('/components', function () {
    return view('components.overview');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// use .env to create custom(s) - https://laracasts.com/discuss/channels/1/return-array-from-env?
// loop through customs to create routes
// loop in views to create buttons
// Conditional views, passing CI?

$custom_parts = ['alf', 'kim', 'reinhold'];

foreach ($custom_parts as $custom_part) {
    $custom_view = 'custom.'.$custom_part;
    $custom_route = 'custom/'.$custom_part;
    if (view()->exists($custom_view)) {
        Route::view($custom_route, $custom_view);
    }
}
