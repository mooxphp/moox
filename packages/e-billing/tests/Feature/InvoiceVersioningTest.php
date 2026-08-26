<?php

declare(strict_types=1);

use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Tests\TestCase;
use Moox\Invoice\Support\InvoiceModels;

uses(TestCase::class);

test('second upload of same number becomes version 2 and is not current', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $first = $invoiceClass::factory()->create([
        'invoice_number' => 'VER-100',
        'document_type' => '381',
    ]);

    $second = $invoiceClass::factory()->create([
        'invoice_number' => 'VER-100',
        'document_type' => '381',
    ]);

    expect($first->fresh()->document_version)->toBe(1)
        ->and($first->fresh()->is_current)->toBeTrue()
        ->and($second->document_version)->toBe(2)
        ->and($second->is_current)->toBeFalse();
});

test('confirming a newer version makes it current and keeps older versions', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $first = $invoiceClass::factory()->create([
        'invoice_number' => 'VER-200',
        'document_type' => '381',
    ]);

    $second = $invoiceClass::factory()->create([
        'invoice_number' => 'VER-200',
        'document_type' => '381',
    ]);

    EbillingDocument::query()->create([
        'invoice_id' => $second->getKey(),
        'format' => 'zugferd',
        'gateway_status' => 'validated',
        'review_status' => InvoiceProcessingStatus::DbValidated,
        'scope' => 'credit-notes',
    ]);

    $result = app(ConfirmInvoiceAction::class)->execute($second->fresh());

    expect($result['confirmed'])->toBeTrue()
        ->and($result['previous_current_count'])->toBe(1)
        ->and($second->fresh()->is_current)->toBeTrue()
        ->and($second->fresh()->document_version)->toBe(2)
        ->and($first->fresh()->is_current)->toBeFalse()
        ->and($first->fresh()->trashed())->toBeFalse()
        ->and($first->fresh()->document_version)->toBe(1);
});

test('confirm without siblings still succeeds', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $invoice = $invoiceClass::factory()->create([
        'invoice_number' => 'VER-300',
        'document_type' => '380',
    ]);

    EbillingDocument::query()->create([
        'invoice_id' => $invoice->getKey(),
        'format' => 'zugferd',
        'gateway_status' => 'validated',
        'review_status' => InvoiceProcessingStatus::DbValidated,
        'scope' => 'default',
    ]);

    $result = app(ConfirmInvoiceAction::class)->execute($invoice->fresh());

    expect($result['confirmed'])->toBeTrue()
        ->and($result['previous_current_count'])->toBe(0)
        ->and($invoice->fresh()->is_current)->toBeTrue();
});
