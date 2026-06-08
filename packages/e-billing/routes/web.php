<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Moox\EBilling\Http\Controllers\InvoiceDocumentController;

Route::middleware(['web', 'auth'])->prefix('ebilling')->group(function (): void {
    Route::get('pdf/{attachment}', [InvoiceDocumentController::class, 'previewOriginal'])
        ->name('ebilling.pdf.preview');

    Route::get('zugferd-download/{attachment}', [InvoiceDocumentController::class, 'downloadZugferd'])
        ->name('ebilling.zugferd.download');

    Route::get('xml-download/{attachment}', [InvoiceDocumentController::class, 'downloadXml'])
        ->name('ebilling.xml.download');
});
