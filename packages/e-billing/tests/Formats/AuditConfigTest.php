<?php

declare(strict_types=1);

use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Resources\CreditNoteResource;
use Moox\EBilling\Resources\InvoiceResource;
use Moox\EBilling\Support\InvoiceActivitySubjectLabel;
use Moox\EBilling\Tests\ContainerTestCase;
use Moox\Invoice\Models\Invoice;

uses(ContainerTestCase::class);

test('e-billing audit config registers invoice and document for moox/audit', function (): void {
    /** @var array<string, mixed> $config */
    $config = require dirname(__DIR__, 2).'/config/e-billing.php';

    expect($config)->toHaveKey('audit')
        ->and($config['audit']['enabled'])->toBeTrue()
        ->and($config['audit']['models'])->toHaveKeys([
            Invoice::class,
            EbillingDocument::class,
        ])
        ->and($config['audit']['models'][Invoice::class]['log_name'])->toBe('e-billing')
        ->and($config['audit']['models'][Invoice::class]['subject_label_resolver'])
        ->toBe(InvoiceActivitySubjectLabel::class)
        ->and($config['audit']['models'][EbillingDocument::class]['log_name'])->toBe('e-billing')
        ->and($config['audit']['models'][EbillingDocument::class]['label'])
        ->toBe('trans//e-billing::ebilling.ebilling_document')
        ->and($config['audit']['models'][EbillingDocument::class]['title_attribute'])->toBe('format')
        ->and($config['audit']['models'][EbillingDocument::class]['significant_updates']['gateway_status'])
        ->toContain('validated', 'validation_failed')
        ->and($config['audit']['models'][EbillingDocument::class]['attributes'])->toContain(
            'gateway_status',
            'review_status',
            'artifact_content_hash',
            'customer_id',
        )
        ->and($config['audit']['models'][EbillingDocument::class]['hidden_attributes'])->toContain(
            'bill_data',
            'field_validations',
        )
        ->and($config['audit']['filament'])->toHaveKeys([
            InvoiceResource::class,
            CreditNoteResource::class,
        ])
        ->and($config['audit']['filament'][InvoiceResource::class]['owner_model'])->toBe(Invoice::class)
        ->and($config['audit']['filament'][InvoiceResource::class]['aggregate_subjects'])
        ->toBe([EbillingDocument::class => 'ebillingDocument']);
});
