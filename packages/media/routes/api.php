<?php

use Illuminate\Support\Facades\Route;
use Moox\Media\Http\Controllers\MediaController;

/** @var mixed $middlewareConfig */
$middlewareConfig = config('media.api.middleware', ['web', 'auth', 'throttle:60,1']);
$middleware = is_array($middlewareConfig)
    ? array_values(array_filter(
        $middlewareConfig,
        static fn (mixed $entry): bool => is_string($entry) && trim($entry) !== ''
    ))
    : [];

$basePrefix = trim((string) config('media.api.prefix', 'api/media'), '/');
$basePrefix = $basePrefix !== '' ? $basePrefix : 'api/media';

$version = trim((string) config('media.api.version', 'v1'), '/');
$prefix = $version !== '' ? $basePrefix.'/'.$version : $basePrefix;

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('moox-media.')
    ->group(function (): void {
        Route::get('', [MediaController::class, 'index'])->name('media.index');
        Route::post('', [MediaController::class, 'store'])->name('media.store');
    });
