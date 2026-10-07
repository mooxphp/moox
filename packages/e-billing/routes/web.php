<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Moox\EBilling\Http\Controllers\InvoiceDocumentController;
use Moox\EBilling\Http\Middleware\SetFilamentPanelFromRequest;

/*
| Document preview/download are plain web routes (iframe + download links), not
| Filament panel routes. SetFilamentPanelFromRequest extends Filament Authenticate
| so ?panel= is applied before guard/login resolution (Laravel middleware priority
| would otherwise run Authenticate before a separate panel middleware).
| Livewire persistent middleware is unnecessary: these handlers are not Livewire.
*/
Route::middleware([
    'web',
    SetFilamentPanelFromRequest::class,
])->prefix('ebilling')->group(function (): void {
    Route::get('pdf/{document}', [InvoiceDocumentController::class, 'previewOriginal'])
        ->name('ebilling.pdf.preview');

    Route::get('zugferd-download/{document}', [InvoiceDocumentController::class, 'downloadZugferd'])
        ->name('ebilling.zugferd.download');

    Route::get('xml-download/{document}', [InvoiceDocumentController::class, 'downloadXml'])
        ->name('ebilling.xml.download');

    Route::get('copy-download/{document}', [InvoiceDocumentController::class, 'downloadCopy'])
        ->name('ebilling.copy.download');
});
