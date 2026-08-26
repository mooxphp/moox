<?php

declare(strict_types=1);

use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Support\InvoiceFieldLabels;
use Moox\EBilling\Support\InvoiceNumberDuplicateChecker;
use Moox\EBilling\Tests\TestCase;
use Moox\Invoice\Support\InvoiceModels;

uses(TestCase::class);

test('duplicate checker finds same invoice number and document type', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $existing = $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-100',
        'document_type' => '380',
    ]);

    $candidate = $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-100',
        'document_type' => '380',
    ]);

    $duplicate = (new InvoiceNumberDuplicateChecker)->findDuplicate($candidate);

    expect($duplicate)->not->toBeNull()
        ->and((string) $duplicate->getKey())->toBe((string) $existing->getKey());
});

test('duplicate checker ignores same number under a different document type', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-200',
        'document_type' => '380',
    ]);

    $creditNote = $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-200',
        'document_type' => '381',
    ]);

    expect((new InvoiceNumberDuplicateChecker)->findDuplicate($creditNote))->toBeNull();
});

test('duplicate checker ignores soft-deleted invoices', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $deleted = $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-300',
        'document_type' => '380',
    ]);
    $deleted->delete();

    $candidate = $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-300',
        'document_type' => '380',
    ]);

    expect((new InvoiceNumberDuplicateChecker)->findDuplicate($candidate))->toBeNull();
});

test('field validator flags duplicate invoice number as needs_review and blocks auto Validated', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $existing = $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-400',
        'document_type' => '380',
    ]);

    $invoice = $invoiceClass::factory()->create([
        'invoice_number' => 'DUP-400',
        'document_type' => '380',
    ]);

    $document = EbillingDocument::query()->create([
        'invoice_id' => $invoice->getKey(),
        'format' => 'zugferd',
        'gateway_status' => 'generating',
        'review_status' => InvoiceProcessingStatus::ParserCreated,
        'scope' => 'default',
    ]);

    app(InvoiceFieldValidator::class)->validate($document->fresh());

    $document->refresh();
    $validation = $document->field_validations['invoice_number'] ?? null;

    expect($validation)->toMatchArray([
        'status' => 'needs_review',
        'reason' => 'duplicate_invoice_number',
        'matched_id' => (string) $existing->getKey(),
    ])
        ->and($document->review_status)->toBe(InvoiceProcessingStatus::DbValidated)
        ->and($document->needsHumanReview())->toBeTrue()
        ->and(InvoiceFieldLabels::hint('invoice_number', 'needs_review', $validation))
        ->toBe(__('e-billing::fields.hint_review_duplicate_invoice_number'));
});

test('unique invoice number stays parsed when present', function (): void {
    $invoiceClass = InvoiceModels::invoice();

    $invoice = $invoiceClass::factory()->create([
        'invoice_number' => 'UNIQUE-500',
        'document_type' => '380',
    ]);

    $document = EbillingDocument::query()->create([
        'invoice_id' => $invoice->getKey(),
        'format' => 'zugferd',
        'gateway_status' => 'generating',
        'review_status' => InvoiceProcessingStatus::ParserCreated,
        'scope' => 'default',
    ]);

    app(InvoiceFieldValidator::class)->fillFieldValidations($document->fresh());

    $document->refresh();

    expect($document->field_validations['invoice_number'] ?? null)->toMatchArray([
        'status' => 'parsed',
    ]);
});
