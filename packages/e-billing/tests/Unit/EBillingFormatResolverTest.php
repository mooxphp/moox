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

test('resolves customer preferred_ebilling_format when customer matches via customer_number', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'customer_number' => 'FMT-001',
        'preferred_ebilling_format' => 'xrechnung',
        'is_active' => true,
    ]);

    attachCustomerToDocumentInvoice($document, $customer);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('resolves customer preferred_ebilling_format from attributed customer_id', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'customer_number' => 'FMT-002',
        'preferred_ebilling_format' => 'xrechnung',
        'is_active' => true,
    ]);

    $document->update(['customer_id' => $customer->id]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document->fresh()))->toBe('xrechnung');
});

test('falls back to default_format when no customer preference is set', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    config(['e-billing.default_format' => 'zugferd']);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('zugferd');
});

test('frozen format is unaffected by later preference change', function (): void {
    $document = PipelineFixtures::arrangeInvoice(
        $this,
        documentFactory: PipelineFixtures::validatingXmlDocument(...),
    )->document;

    expect($document->xml_storage_path)->not->toBeNull();

    $customer = Customer::factory()->create([
        'customer_number' => 'FMT-FROZEN',
        'preferred_ebilling_format' => 'zugferd',
        'is_active' => true,
    ]);

    attachCustomerToDocumentInvoice($document, $customer);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default when preferred format is unknown', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'customer_number' => 'FMT-003',
        'preferred_ebilling_format' => 'ubl-peppol',
        'is_active' => true,
    ]);

    attachCustomerToDocumentInvoice($document, $customer);

    config(['e-billing.default_format' => 'zugferd']);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('zugferd');
});

test('customer without company assignment still gets preferred format', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'customer_number' => 'ORPHAN-FMT',
        'preferred_ebilling_format' => 'xrechnung',
        'is_active' => true,
    ]);

    attachCustomerToDocumentInvoice($document, $customer);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('customer preference beats company preference', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $company = Company::factory()->create([
        'name' => 'Buyer GmbH',
        'data' => ['preferred_ebilling_format' => 'zugferd'],
    ]);

    $customer = Customer::factory()->create([
        'customer_number' => 'FMT-BEATS-CO',
        'preferred_ebilling_format' => 'xrechnung',
        'is_active' => true,
    ]);

    CustomerAssignment::query()->create([
        'customer_id' => $customer->id,
        'assignable_type' => $company->getMorphClass(),
        'assignable_id' => $company->id,
        'is_primary' => true,
    ]);

    attachCustomerToDocumentInvoice($document, $customer);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default when only company has preference and no customer match', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'data' => ['preferred_ebilling_format' => 'xrechnung'],
    ]);

    config(['e-billing.default_format' => 'zugferd']);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('zugferd');
});

test('resolveSendVisualCopy defaults to true', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveSendVisualCopy($document))->toBeTrue();
});

test('resolveSendVisualCopy uses customer column over config', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'is_active' => true,
        'send_visual_copy' => false,
    ]);

    $document->setRelation('customer', $customer);

    config(['e-billing.send_visual_copy' => true]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveSendVisualCopy($document))->toBeFalse();
});

test('resolveSendVisualCopy falls back to config when customer value is null', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'is_active' => true,
        'send_visual_copy' => null,
    ]);

    $document->setRelation('customer', $customer);

    config(['e-billing.send_visual_copy' => false]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveSendVisualCopy($document))->toBeFalse();
});