<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Moox\EBilling\Http\Controllers\InvoiceDocumentController;

/*
| Document preview/download routes (iframe + download links), registered by the
| e-billing plugin as authenticated routes of every panel that uses it. They run
| under that panel's middleware and guard and are named filament.{panel}.ebilling.*;
| build URLs with $panel->route('ebilling.pdf.preview', ...).
*/
Route::prefix('ebilling')->name('ebilling.')->group(function (): void {
    Route::get('pdf/{document}', [InvoiceDocumentController::class, 'previewOriginal'])
        ->name('pdf.preview');

    Route::get('zugferd-download/{document}', [InvoiceDocumentController::class, 'downloadZugferd'])
        ->name('zugferd.download');

    Route::get('xml-download/{document}', [InvoiceDocumentController::class, 'downloadXml'])
        ->name('xml.download');

    Route::get('copy-download/{document}', [InvoiceDocumentController::class, 'downloadCopy'])
        ->name('copy.download');
});
