<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Contracts\PdfaNormalizerInterface;
use Moox\EBilling\Contracts\SourcePdfPreparerInterface;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Contracts\GeneratorStrategyInterface;
use Moox\EBilling\Formats\FormatDefinition;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Jobs\GenerateArtifactJob;
use Moox\EBilling\Jobs\ValidateArtifactJob;
use Moox\EBilling\Services\CopyPdfComposer;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Services\ParsedInvoiceMapper;
use Moox\EBilling\Support\EBillingFormatResolver;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('generate artifact job freezes the attributed customer format not the default', function (): void {
    $fixture = PipelineFixtures::arrangeInvoice($this);
    $document = $fixture->document;

    $customerNumber = 'K-1042';
    $billData = $document->bill_data;
    $billData['customer_number'] = $customerNumber;
    $document->update([
        'bill_data' => $billData,
        'customer_id' => null,
        'xml_storage_path' => null,
    ]);

    Customer::factory()->create([
        'customer_number' => $customerNumber,
        'preferred_ebilling_format' => 'xrechnung',
        'is_active' => true,
    ]);

    config(['e-billing.default_format' => 'zugferd']);

    $strategy = mock(GeneratorStrategyInterface::class);
    $strategy->shouldReceive('generateXml')
        ->once()
        ->andReturn('<?xml version="1.0"?><invoice/>');

    app()->forgetInstance(FormatRegistry::class);
    app()->singleton(FormatRegistry::class, function () use ($strategy): FormatRegistry {
        $registry = new FormatRegistry;
        $registry->register(new FormatDefinition(
            id: 'xrechnung',
            label: 'XRechnung',
            artifactKind: ArtifactKind::Xml,
            profile: 'XRECHNUNG',
            strategy: $strategy,
        ));
        $registry->register(new FormatDefinition(
            id: 'zugferd',
            label: 'ZUGFeRD',
            artifactKind: ArtifactKind::Pdf,
            profile: 'EN16931',
            strategy: $strategy,
        ));

        return $registry;
    });

    Bus::fake([ValidateArtifactJob::class]);

    app(GenerateArtifactJob::class, ['ebillingDocumentId' => $document->getKey()])->handle(
        app(FormatRegistry::class),
        app(EBillingFormatResolver::class),
        app(ParsedInvoiceMapper::class),
        app(InvoiceFieldValidator::class),
        app(SourcePdfPreparerInterface::class),
        app(PdfaNormalizerInterface::class),
        app(CopyPdfComposer::class),
    );

    $document->refresh();

    expect($document->customer_id)->not->toBeNull()
        ->and($document->format)->toBe('xrechnung')
        ->and($document->pdf_storage_path)->toBeNull()
        ->and($document->copy_pdf_storage_path)->toEndWith('_copy.pdf');

    $copy = Storage::disk((string) $document->storage_disk)->get((string) $document->copy_pdf_storage_path);

    expect($copy)->toStartWith('%PDF')
        ->and($copy)->toContain('/Helvetica-Bold');
});
