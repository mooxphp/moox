<?php

declare(strict_types=1);

use Moox\EBilling\Services\CopyPdfComposer;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('copy pdf stamp applies a single soft watermark and does not embed xml', function (): void {
    config([
        'e-billing.copy_pdf.term' => 'Kopie',
    ]);

    $source = dirname(__DIR__).'/fixtures/minimal-invoice.pdf';
    $binary = app(CopyPdfComposer::class)->stamp($source);

    expect($binary)->toStartWith('%PDF')
        ->and($binary)->toContain('/Helvetica-Bold')
        ->and($binary)->not->toContain('CrossIndustryInvoice')
        ->and(strlen($binary))->toBeGreaterThan((int) filesize($source));
});

test('copy pdf stamp refuses to run without marking text', function (): void {
    config([
        'e-billing.copy_pdf.term' => '',
    ]);

    $source = dirname(__DIR__).'/fixtures/minimal-invoice.pdf';

    expect(fn () => app(CopyPdfComposer::class)->stamp($source))
        ->toThrow(RuntimeException::class, 'copy marking is required');
});
