<?php

declare(strict_types=1);

use Moox\EBilling\Resources\CreditNoteResource;
use Moox\EBilling\Resources\InvoiceResource;
use Moox\EBilling\Tests\TestCase;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\InvoiceModels;

uses(TestCase::class);

test('credit note tab queries count only document type 381', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    Invoice::factory()->count(3)->create(['document_type' => '380']);
    Invoice::factory()->create(['document_type' => '381']);
    Invoice::factory()->create(['document_type' => '381'])->delete();

    $allTabConditions = [
        [
            'field' => 'deleted_at',
            'operator' => '=',
            'value' => null,
        ],
    ];

    $deletedTabConditions = [
        [
            'field' => 'deleted_at',
            'operator' => '!=',
            'value' => null,
        ],
    ];

    expect(CreditNoteResource::applyListTabConditions($invoiceClass::query(), $allTabConditions)->count())->toBe(1)
        ->and(CreditNoteResource::applyListTabConditions($invoiceClass::query(), $deletedTabConditions)->count())->toBe(1)
        ->and(InvoiceResource::applyListTabConditions($invoiceClass::query(), $allTabConditions)->count())->toBe(3)
        ->and(InvoiceResource::getNavigationBadge())->toBe('3')
        ->and(CreditNoteResource::getNavigationBadge())->toBe('1');
});
