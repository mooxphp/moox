<?php
/*
|
| PLEASE INCLUDE ONLY ROUTES, EVERY DEVELOPER (AS WELL AS PHP-STAN)
| CAN USE. DO NOT INCLUDE CUSTOM ROUTES HERE, USE THE CUSTOM FEATURE!
|
*/

use Illuminate\Support\Facades\Route;

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

$custom_parts = explode(', ', env('TUI_CUSTOM_DEVELOPER'));
if (is_array($custom_parts)) {
    foreach ($custom_parts as $custom_part) {
        $custom_view = 'custom.'.$custom_part;
        $custom_route = 'custom/'.$custom_part;
        if (view()->exists($custom_view)) {
            Route::view($custom_route, $custom_view);
        }
    }
}

$custom_parts = explode(', ', env('TUI_CUSTOM_PROJECTS'));
if (is_array($custom_parts)) {
    foreach ($custom_parts as $custom_part) {
        $tui_routes = base_path('routes/custom_'.$custom_part.'.php');
        dd($tui_routes);
        if (file_exists($tui_routes)) {
            include $tui_routes;
            // will most probably not work here, but in serviceprovider
            // Route::prefix('admin')
            // ->group($tui_routes);
        }
    }
}
