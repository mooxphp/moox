<?php

declare(strict_types=1);

use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Moox\KositValidator\Http\Controllers\KositReportController;

Route::middleware(['web', Authenticate::class])
    ->prefix('admin/kosit-validations')
    ->name('kosit-validator.')
    ->group(function (): void {
        Route::get('{validation}/report-html', [KositReportController::class, 'html'])
            ->name('report.html');
        Route::get('{validation}/download/input', [KositReportController::class, 'downloadInputFile'])
            ->name('download.input-file');
        Route::get('{validation}/download/report-html', [KositReportController::class, 'downloadReportHtml'])
            ->name('download.report-html');
        Route::get('{validation}/download/report', [KositReportController::class, 'downloadReportXml'])
            ->name('download.report-xml');
    });
