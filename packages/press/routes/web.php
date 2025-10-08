<?php

use Illuminate\Support\Facades\Route;
use Moox\Press\Http\Controllers\WordPressProxyController;
use Moox\Press\Support\WpBridge;


Route::any('/wp/{any?}', WordPressProxyController::class)->where('any', '.*');

/*
Route::any('/wp/{any?}', function ($any = null) {
    $path = '/wp' . ($any ? '/' . $any : '');
    return app(WpBridge::class)->run($path);
})->where('any', '.*');
*/
