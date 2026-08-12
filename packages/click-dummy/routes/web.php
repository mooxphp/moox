<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Moox\ClickDummy\Http\Controllers\ClickDummyController;

if (! config('click-dummy.enabled', true)) {
    return;
}

$prefix = trim((string) config('click-dummy.route_prefix', 'clickdummy'), '/');
$middleware = config('click-dummy.middleware', ['web', 'moox.frontend-auth']);

if (! is_array($middleware)) {
    $middleware = ['web', 'moox.frontend-auth'];
}

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('click-dummy.')
    ->group(function (): void {
        Route::get('/{path?}', ClickDummyController::class)
            ->where('path', '.*')
            ->name('show');
    });
