<?php

declare(strict_types=1);

use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Moox\VeraPdf\Http\Controllers\VeraPdfReportController;

Route::middleware(['web', Authenticate::class])
    ->prefix('admin/verapdf-validations')
    ->name('verapdf.')
    ->group(function (): void {
        Route::get('{validation}/report-html', [VeraPdfReportController::class, 'html'])
            ->name('report.html');
        Route::get('{validation}/download/input', [VeraPdfReportController::class, 'downloadInputFile'])
            ->name('download.input-file');
        Route::get('{validation}/download/report-html', [VeraPdfReportController::class, 'downloadReportHtml'])
            ->name('download.report-html');
        Route::get('{validation}/download/report', [VeraPdfReportController::class, 'downloadReportXml'])
            ->name('download.report-xml');
    });
