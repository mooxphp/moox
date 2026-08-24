<?php

declare(strict_types=1);

use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('download filename prefers the original upload name over the storage path', function (): void {
    $source = new UploadedPdfSource([
        'source_pdf_path' => 'uploads/01HXYZABCDEFGHJKLMNPQRSTUV.pdf',
        'original_filename' => 'Gutschrift 3465925.pdf',
    ]);

    $document = new EbillingDocument([
        'copy_pdf_storage_path' => 'default/2026/08/21/01HXYZABCDEFGHJKLMNPQRSTUV_copy.pdf',
        'xml_storage_path' => 'default/2026/08/21/01HXYZABCDEFGHJKLMNPQRSTUV.xml',
        'pdf_storage_path' => null,
    ]);
    $document->setRelation('source', $source);

    expect($document->downloadFilenameForStoredPath((string) $document->copy_pdf_storage_path))
        ->toBe('Gutschrift_3465925_copy.pdf')
        ->and($document->downloadFilenameForStoredPath((string) $document->xml_storage_path))
        ->toBe('Gutschrift_3465925.xml');
});
