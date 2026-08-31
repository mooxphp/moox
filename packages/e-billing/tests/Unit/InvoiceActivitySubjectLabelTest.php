<?php

declare(strict_types=1);

use Moox\Audit\Contracts\ActivitySubjectLabelResolver;
use Moox\Audit\Models\Activity;
use Moox\EBilling\Support\InvoiceActivitySubjectLabel;
use Moox\EBilling\Tests\ContainerTestCase;
use Moox\Invoice\Models\Invoice;

uses(ContainerTestCase::class);

beforeEach(function (): void {
    if (! class_exists(Activity::class) || ! interface_exists(ActivitySubjectLabelResolver::class)) {
        $this->markTestSkipped('moox/audit is not installed');
    }
});

test('invoice activity subject label uses document type and number', function (): void {
    $invoice = new Invoice;
    $invoice->forceFill([
        'invoice_number' => '3465925',
        'document_type' => '381',
    ]);

    $activity = new Activity;
    $activity->subject_type = Invoice::class;
    $activity->subject_id = 'test-id';
    $activity->setRelation('subject', $invoice);

    expect((new InvoiceActivitySubjectLabel)->resolve($activity))->toBe(__('e-billing::ebilling.credit_note').' 3465925');
});

test('invoice activity subject label reads deleted snapshot attributes', function (): void {
    $activity = new Activity;
    $activity->subject_type = Invoice::class;
    $activity->subject_id = 'gone';
    $activity->attribute_changes = [
        'old' => [
            'invoice_number' => 'INV-9',
            'document_type' => '380',
        ],
    ];
    $activity->setRelation('subject', null);

    expect((new InvoiceActivitySubjectLabel)->resolve($activity))->toBe(__('e-billing::ebilling.invoice').' INV-9');
});
