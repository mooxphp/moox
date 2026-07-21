<?php

declare(strict_types=1);

use Moox\Company\Models\Company;
use Moox\EBilling\Support\EBillingFormatResolver;
use Moox\EBilling\Tests\Support\InvoiceFixtures;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('resolves consumer preferred_ebilling_format when company matches', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::hybridPipelineAttachment($billData);
    $document = $fixture['document'];

    Company::factory()->customer()->create([
        'name' => 'Buyer GmbH',
        'is_active' => true,
        'data' => ['preferred_ebilling_format' => 'xrechnung'],
    ]);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default_format when no company preference is set', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::hybridPipelineAttachment($billData);
    $document = $fixture['document'];

    config(['e-billing.default_format' => 'zugferd']);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('zugferd');
});

test('frozen format is unaffected by later preference change', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::validatingXmlDocument($billData);
    $document = $fixture['document'];

    // Document already has xml_storage_path set → frozen
    expect($document->xml_storage_path)->not->toBeNull();

    Company::factory()->customer()->create([
        'name' => 'Buyer GmbH',
        'is_active' => true,
        'data' => ['preferred_ebilling_format' => 'zugferd'],
    ]);

    $resolver = app(EBillingFormatResolver::class);

    // Should return frozen format (xrechnung), not the new preference (zugferd)
    expect($resolver->resolveForGeneration($document))->toBe('xrechnung');
});

test('falls back to default when preferred format is unknown', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::hybridPipelineAttachment($billData);
    $document = $fixture['document'];

    Company::factory()->customer()->create([
        'name' => 'Buyer GmbH',
        'is_active' => true,
        'data' => ['preferred_ebilling_format' => 'ubl-peppol'],
    ]);

    config(['e-billing.default_format' => 'zugferd']);

    $resolver = app(EBillingFormatResolver::class);

    expect($resolver->resolveForGeneration($document))->toBe('zugferd');
});
