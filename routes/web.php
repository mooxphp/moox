<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/packages', function () {
    return view('packages');
})->name('packages');

Route::get('/components', function () {
    return view('components');
})->name('components');

Route::get('/themes', function () {
    return view('themes');
})->name('themes');

Route::get('/license', function () {
    return view('license');
})->name('license');

Route::get('/demo', function () {
    return view('demo');
})->name('demo');

Route::get('/docs', function () {
    return view('docs');
})->name('docs');

Route::get('/docsingle', function () {
    return view('doc-single');
})->name('docsingle');

Route::get('/support', function () {
    return view('support');
})->name('support');

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
