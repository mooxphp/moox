<?php

declare(strict_types=1);

use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Moox\Connect\Http\Controllers\ApiImportRecordRenderController;

Route::middleware(['web', 'panel:'.config('connect.debug_panel', 'admin'), Authenticate::class])->prefix('connect')->group(function (): void {
    Route::get(
        '/import-records/endpoint-parent/{parentEndpoint}',
        [ApiImportRecordRenderController::class, 'showParentEndpoint']
    )->name('connect.import-records.show-parent-endpoint');

    Route::get(
        '/import-records/external/{externalKey}',
        [ApiImportRecordRenderController::class, 'showByExternalKey']
    )->name('connect.import-records.show');
});
