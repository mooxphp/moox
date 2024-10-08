<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/custom', function () {
    return view('custom.overview');
});

Route::get('/packages', function () {
    return view('packages.overview');
});

Route::get('/components', function () {
    return view('components.overview');
});

Route::get('/demo', function () {
    return view('demo.overview');
});

$custom_parts = config('moox.custom_views');
if (is_array($custom_parts)) {
    foreach ($custom_parts as $custom_part) {
        $custom_view = 'custom.'.$custom_part;
        $custom_route = 'custom/'.$custom_part;
        if (View::exists($custom_view)) {
            Route::view($custom_route, $custom_view);
        }
    }
}

$custom_parts = explode(', ', config('moox.custom_routes'));
if (is_array($custom_parts)) {
    foreach ($custom_parts as $custom_part) {
        $tui_routes = base_path('routes/custom_'.$custom_part.'.php');
        if (file_exists($tui_routes)) {
            include $tui_routes;
        }
    }
}
