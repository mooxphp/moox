<?php

declare(strict_types=1);

use Moox\EBilling\Adapters\ZugferdInvoiceAdapter;
use Moox\EBilling\Adapters\ZugferdInvoiceLineAdapter;
use Moox\EBilling\Tests\TestCase;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceLine;

uses(TestCase::class);

test('ZugferdInvoiceAdapter returns human-readable documentType and code in documentTypeCode', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $model = new Invoice;
    $model->forceFill([
        'invoice_number' => '2026001',
        'invoice_date' => '2026-01-15',
        'document_type' => '381',
        'currency' => 'EUR',
        'vat_rate' => 19,
        'net_total' => 100,
        'vat_amount' => 19,
        'gross_total' => 119,
    ]);

    $adapter = new ZugferdInvoiceAdapter($model);

    expect($adapter->documentTypeCode)->toBe('381')
        ->and($adapter->documentType)->toBe('Gutschrift');
});

test('ZugferdInvoiceLineAdapter resolves unitCode once from seeded codelist', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $line = new InvoiceLine;
    $line->forceFill([
        'position' => 1,
        'unit' => 'Stück',
        'quantity' => 1,
        'description' => 'Item',
        'unit_price' => 10,
        'line_total' => 10,
    ]);

    $adapter = new ZugferdInvoiceLineAdapter($line);

    expect($adapter->unit)->toBe('Stück')
        ->and($adapter->unitCode)->toBe('C62');
});
