<?php

use Illuminate\Support\Facades\Route;
use Moox\BlockEditor\Http\Controllers\DynamicFeedController;
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
        Route::apiResource('templates', TemplateController::class);

        Route::prefix('dynamic-feeds')
            ->name('dynamic-feeds.')
            ->group(function (): void {
                Route::get('sources', [DynamicFeedController::class, 'sources'])->name('sources');
                Route::get('sources/{sourceKey}/views', [DynamicFeedController::class, 'views'])->name('views');
                Route::get('sources/{sourceKey}/filter-options/{filter}', [DynamicFeedController::class, 'filterOptions'])->name('filter-options');
                Route::get('preview', [DynamicFeedController::class, 'preview'])->name('preview');
            });
    });
