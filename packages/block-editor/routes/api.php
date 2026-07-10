<?php

use Illuminate\Support\Facades\Route;
use Moox\BlockEditor\Http\Controllers\TemplateController;

/** @var mixed $middlewareConfig */
$middlewareConfig = config('moox-editor.api.middleware', ['web', 'auth', 'throttle:60,1']);
$middleware = is_array($middlewareConfig)
    ? array_values(array_filter(
        $middlewareConfig,
        static fn (mixed $entry): bool => is_string($entry) && trim($entry) !== ''
    ))
    : [];
$basePrefix = trim((string) config('moox-editor.api.prefix', 'api/editor'), '/');
$basePrefix = $basePrefix !== '' ? $basePrefix : 'api/editor';
$version = trim((string) config('moox-editor.api.version', 'v1'), '/');
$prefix = $version !== '' ? $basePrefix.'/'.$version : $basePrefix;

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('moox-editor.')
    ->group(function (): void {
        Route::apiResource('templates', TemplateController::class)->except(['show']);
    });
