<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Services\LetterheadUnderlayService;
use Moox\EBilling\Support\LetterheadSourcePdfPreparer;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('letterhead overlay is required when the upload flag is set and the path is missing', function (): void {
    config(['e-billing.letterhead.default.pdf_path' => '']);

    $source = new UploadedPdfSource([
        'source_pdf_disk' => 'local',
        'source_pdf_path' => 'ebilling/manual-uploads/source/test.pdf',
        'requires_letterhead_overlay' => true,
    ]);

    $document = new EbillingDocument;
    $document->setRelation('source', $source);

    expect(fn () => app(LetterheadSourcePdfPreparer::class)->prepare($document))
        ->toThrow(RuntimeException::class, 'pdf_path is empty');
});

test('letterhead underlay produces a PDF larger than the raw source', function (): void {
    $source = dirname(__DIR__).'/fixtures/minimal-invoice.pdf';
    $letterhead = base_path('packages/heco/resources/letterheads/heco-default.pdf');

    if (! is_file($letterhead)) {
        test()->markTestSkipped('HECO letterhead fixture not available in this workspace.');
    }

    $binary = app(LetterheadUnderlayService::class)->compose($source, $letterhead, 3.5);

    expect($binary)->toStartWith('%PDF')
        ->and(strlen($binary))->toBeGreaterThan((int) filesize($source));
});

test('seller-specific letterhead overrides the default profile', function (): void {
    $source = dirname(__DIR__).'/fixtures/minimal-invoice.pdf';
    $defaultLetterhead = base_path('packages/heco/resources/letterheads/heco-default.pdf');

    if (! is_file($defaultLetterhead)) {
        test()->markTestSkipped('HECO letterhead fixture not available in this workspace.');
    }

    $sellerLetterheadPath = tempnam(sys_get_temp_dir(), 'seller-letterhead-');

    expect($sellerLetterheadPath)->not->toBeFalse();

    copy($defaultLetterhead, $sellerLetterheadPath);

    config([
        'e-billing.letterhead.default.pdf_path' => $defaultLetterhead,
        'e-billing.letterhead.by_seller.655371.pdf_path' => $sellerLetterheadPath,
    ]);

    Storage::disk('local')->put('ebilling/manual-uploads/source/test.pdf', file_get_contents($source));

    $sourceModel = UploadedPdfSource::create([
        'source_pdf_disk' => 'local',
        'source_pdf_path' => 'ebilling/manual-uploads/source/test.pdf',
        'original_filename' => 'test.pdf',
        'requires_letterhead_overlay' => true,
    ]);

    $document = new EbillingDocument([
        'bill_data' => [
            'supplier_number' => '655371',
        ],
    ]);
    $document->setRelation('source', $sourceModel);

    $preparedPath = app(LetterheadSourcePdfPreparer::class)->prepare($document);

    expect(is_file($preparedPath))->toBeTrue()
        ->and(filesize($preparedPath))->toBeGreaterThan((int) filesize($source));

    @unlink($sellerLetterheadPath);
    @unlink($preparedPath);
});
