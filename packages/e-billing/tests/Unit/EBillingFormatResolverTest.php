<?php

declare(strict_types=1);

use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\Customer\Models\CustomerAssignment;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\EBillingFormatResolver;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

function attachCustomerToDocumentInvoice(
    EbillingDocument $document,
    Customer $customer,
): void {
    $invoice = $document->invoice;
    $invoice->update(['customer_number' => $customer->customer_number]);
    $document->setRelation('invoice', $invoice->fresh());
}

function customerWithPreferredFormat(string $customerNumber, string $format): Customer
{
    return Customer::factory()->create([
        'customer_number' => $customerNumber,
        'preferred_ebilling_format' => $format,
        'is_active' => true,
    ]);
}

test('resolves customer preferred_ebilling_format', function (
    string $customerNumber,
    bool $viaCustomerId,
): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;
    $customer = customerWithPreferredFormat($customerNumber, 'xrechnung');

    if ($viaCustomerId) {
        $document->update(['customer_id' => $customer->id]);
        $document = $document->fresh();
    } else {
        attachCustomerToDocumentInvoice($document, $customer);
    }

    expect(app(EBillingFormatResolver::class)->resolveForGeneration($document))->toBe('xrechnung');
})->with([
    'when customer matches via customer_number' => ['FMT-001', false],
    'from attributed customer_id' => ['FMT-002', true],
    'when customer has no company assignment' => ['ORPHAN-FMT', false],
]);

test('falls back to default_format when no customer preference is set', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    config(['e-billing.default_format' => 'zugferd']);

    expect(app(EBillingFormatResolver::class)->resolveForGeneration($document))->toBe('zugferd');
});

test('frozen format is unaffected by later preference change', function (): void {
    $document = PipelineFixtures::arrangeInvoice(
        $this,
        documentFactory: PipelineFixtures::validatingXmlDocument(...),
    )->document;

    expect($document->xml_storage_path)->not->toBeNull();

    attachCustomerToDocumentInvoice(
        $document,
        customerWithPreferredFormat('FMT-FROZEN', 'zugferd'),
    );

    expect(app(EBillingFormatResolver::class)->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default when preferred format is unknown', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    attachCustomerToDocumentInvoice(
        $document,
        customerWithPreferredFormat('FMT-003', 'ubl-peppol'),
    );

    config(['e-billing.default_format' => 'zugferd']);

    expect(app(EBillingFormatResolver::class)->resolveForGeneration($document))->toBe('zugferd');
});

test('customer preference beats company preference', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $company = Company::factory()->create([
        'name' => 'Buyer GmbH',
        'data' => ['preferred_ebilling_format' => 'zugferd'],
    ]);

    $customer = customerWithPreferredFormat('FMT-BEATS-CO', 'xrechnung');

    CustomerAssignment::query()->create([
        'customer_id' => $customer->id,
        'assignable_type' => $company->getMorphClass(),
        'assignable_id' => $company->id,
        'is_primary' => true,
    ]);

    attachCustomerToDocumentInvoice($document, $customer);

    expect(app(EBillingFormatResolver::class)->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default when only company has preference and no customer match', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'data' => ['preferred_ebilling_format' => 'xrechnung'],
    ]);

    config(['e-billing.default_format' => 'zugferd']);

    expect(app(EBillingFormatResolver::class)->resolveForGeneration($document))->toBe('zugferd');
});

test('resolveSendVisualCopy defaults to true', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    expect(app(EBillingFormatResolver::class)->resolveSendVisualCopy($document))->toBeTrue();
});

test('resolveSendVisualCopy', function (?bool $column, bool $config, bool $expected): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'is_active' => true,
        'send_visual_copy' => $column,
    ]);

    $document->setRelation('customer', $customer);

    config(['e-billing.send_visual_copy' => $config]);

    expect(app(EBillingFormatResolver::class)->resolveSendVisualCopy($document))->toBe($expected);
})->with([
    'uses customer column over config' => [false, true, false],
    'falls back to config when customer value is null' => [null, false, false],
]);
