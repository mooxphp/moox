<?php

declare(strict_types=1);

use Moox\Customer\Models\Customer;
use Moox\EBilling\Support\EBillingFormatResolver;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('resolves customer preferred_ebilling_format when customer is attributed', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'preferred_ebilling_format' => 'xrechnung',
        'is_active' => true,
    ]);

    $document->setRelation('customer', $customer);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
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

    // Document already has xml_storage_path set → frozen
    expect($document->xml_storage_path)->not->toBeNull();

    $customer = Customer::factory()->create([
        'preferred_ebilling_format' => 'zugferd',
        'is_active' => true,
    ]);

    $document->setRelation('customer', $customer);

    $resolver = app(EBillingFormatResolver::class);

    // Should return frozen format (xrechnung), not the new preference (zugferd)
    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default when preferred format is unknown', function (): void {
    $document = PipelineFixtures::arrangeInvoice($this)->document;

    $customer = Customer::factory()->create([
        'preferred_ebilling_format' => 'ubl-peppol',
        'is_active' => true,
    ]);

    $document->setRelation('customer', $customer);

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
