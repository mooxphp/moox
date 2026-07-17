<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Moox\EBilling\Data\Invoice;
use Moox\EBilling\Data\InvoiceLine;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('constructing Invoice and InvoiceLine performs no database query', function (): void {
    DB::flushQueryLog();
    DB::enableQueryLog();

    new Invoice(
        invoiceNumber: '1',
        invoiceDate: '2026-01-01',
        documentType: 'Rechnung',
        documentTypeCode: '380',
    );

    new InvoiceLine(
        position: 1,
        unit: 'Stück',
        unitCode: 'C62',
        quantity: 1,
        description: 'Item',
        unitPrice: 10,
        lineTotal: 10,
    );

    expect(DB::getQueryLog())->toBeEmpty();
});
