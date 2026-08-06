<?php

declare(strict_types=1);

use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Support\EBillingFormatResolver;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('resolves consumer preferred_ebilling_format when company matches', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'status' => 'active',
        'data' => ['preferred_ebilling_format' => 'xrechnung'],
    ]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('customer preferred_ebilling_format overrides company preference', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;
    $document->forceFill([
        'bill_data' => array_merge($document->bill_data ?? [], [
            'customer_number' => 'C-10001',
        ]),
    ])->save();

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'status' => 'active',
        'data' => ['preferred_ebilling_format' => 'zugferd'],
    ]);

    Customer::factory()->create([
        'customer_number' => 'C-10001',
        'is_active' => true,
        'preferred_ebilling_format' => 'xrechnung',
    ]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to company preference when customer format is null', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;
    $document->forceFill([
        'bill_data' => array_merge($document->bill_data ?? [], [
            'customer_number' => 'C-10002',
        ]),
    ])->save();

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'status' => 'active',
        'data' => ['preferred_ebilling_format' => 'factur-x'],
    ]);

    Customer::factory()->create([
        'customer_number' => 'C-10002',
        'is_active' => true,
        'preferred_ebilling_format' => null,
    ]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('factur-x');
});

test('falls back to default_format when no company preference is set', function (): void {
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

    // Document already has xml_storage_path set → frozen
    expect($document->xml_storage_path)->not->toBeNull();

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'status' => 'active',
        'data' => ['preferred_ebilling_format' => 'zugferd'],
    ]);

    $resolver = app(EBillingFormatResolver::class);

    // Should return frozen format (xrechnung), not the new preference (zugferd)
    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default when preferred format is unknown', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'status' => 'active',
        'data' => ['preferred_ebilling_format' => 'ubl-peppol'],
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

test('resolveSendVisualCopy uses customer column over company and config', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;
    $document->forceFill([
        'bill_data' => array_merge($document->bill_data ?? [], [
            'customer_number' => 'C-10003',
        ]),
    ])->save();

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'status' => 'active',
        'data' => ['send_visual_copy' => true],
    ]);

    Customer::factory()->create([
        'customer_number' => 'C-10003',
        'is_active' => true,
        'send_visual_copy' => false,
    ]);

    config(['e-billing.send_visual_copy' => true]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveSendVisualCopy($document))->toBeFalse();
});

test('resolveSendVisualCopy falls back to company.data when customer is null', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;
    $document->forceFill([
        'bill_data' => array_merge($document->bill_data ?? [], [
            'customer_number' => 'C-10004',
        ]),
    ])->save();

    Company::factory()->create([
        'name' => 'Buyer GmbH',
        'status' => 'active',
        'data' => ['send_visual_copy' => false],
    ]);

    Customer::factory()->create([
        'customer_number' => 'C-10004',
        'is_active' => true,
        'send_visual_copy' => null,
    ]);

    config(['e-billing.send_visual_copy' => true]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveSendVisualCopy($document))->toBeFalse();
});
