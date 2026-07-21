<?php

declare(strict_types=1);

use horstoeko\zugferd\ZugferdDocumentPdfMerger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\ArtifactGenerated;
use Moox\EBilling\Events\ArtifactValidated;
use Moox\EBilling\Events\ArtifactValidationFailed;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Jobs\GenerateArtifactJob;
use Moox\EBilling\Jobs\ValidateArtifactJob;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Services\ParsedInvoiceMapper;
use Moox\EBilling\Tests\Support\InvoiceFixtures;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\DTOs\KositResult;
use Moox\KositValidator\Services\KositService;
use Moox\Zugferd\ZugferdConverter;

uses(TestCase::class);

test('generate then validate artifact job seam passes for hybrid zugferd', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::hybridPipelineAttachment($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    Event::fake([ArtifactGenerated::class, ArtifactValidated::class, ArtifactValidationFailed::class]);

    $this->mock(KositService::class)
        ->shouldReceive('validate')
        ->once()
        ->andReturn(new KositResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
            reportXmlPath: null,
            reportHtmlPath: null,
            xmlPath: '/tmp/validated.xml',
        ));

    $generateJob = app(GenerateArtifactJob::class, ['inboxAttachmentId' => $attachment->id]);
    $generateJob->handle(
        app(FormatRegistry::class),
        app(ParsedInvoiceMapper::class),
        app(InvoiceFieldValidator::class),
    );

    $document->refresh();

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Validating)
        ->and($document->xml_storage_path)->not->toBeNull()
        ->and($document->pdf_storage_path)->not->toBeNull()
        ->and(Storage::disk('zugferd')->exists((string) $document->xml_storage_path))->toBeTrue()
        ->and(Storage::disk('zugferd')->exists((string) $document->pdf_storage_path))->toBeTrue();

    $pdfContent = Storage::disk('zugferd')->get((string) $document->pdf_storage_path);
    expect($pdfContent)->toBeString()
        ->and($pdfContent)->not->toContain('/Encrypt');

    $extractedXml = app(ZugferdConverter::class)->extractXmlFromPdf(
        Storage::disk('zugferd')->path((string) $document->pdf_storage_path)
    );
    expect(trim($extractedXml))->not->toBe('');

    $validateJob = app(ValidateArtifactJob::class, ['inboxAttachmentId' => $attachment->id]);
    $validateJob->handle(
        app(FormatRegistry::class),
        app(KositService::class),
        app(RecordKositValidation::class),
        app(InboxMessagePipelineFinalizer::class),
    );

    $document->refresh();

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Validated)
        ->and($document->artifact_content_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($document->artifact_content_hash)->toBe(hash('sha256', (string) $pdfContent))
        ->and($document->latestKositValidation()?->passed)->toBeTrue();

    Event::assertDispatched(ArtifactGenerated::class);
    Event::assertDispatched(ArtifactValidated::class);
    Event::assertNotDispatched(ArtifactValidationFailed::class);
})->skip(fn (): bool => ! class_exists(ZugferdDocumentPdfMerger::class), 'horstoeko/zugferd is required for hybrid generation');

test('failed validation retains artifact and is not deliverable', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::hybridPipelineAttachment($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    Event::fake([ArtifactGenerated::class, ArtifactValidated::class, ArtifactValidationFailed::class]);

    $this->mock(KositService::class)
        ->shouldReceive('validate')
        ->once()
        ->andReturn(new KositResult(
            exitCode: 1,
            stdout: '',
            stderr: 'KOSIT validation failed',
            reportXmlPath: null,
            reportHtmlPath: null,
            xmlPath: '/tmp/validated.xml',
        ));

    app(GenerateArtifactJob::class, ['inboxAttachmentId' => $attachment->id])->handle(
        app(FormatRegistry::class),
        app(ParsedInvoiceMapper::class),
        app(InvoiceFieldValidator::class),
    );

    $document->refresh();
    $pdfPath = (string) $document->pdf_storage_path;

    app(ValidateArtifactJob::class, ['inboxAttachmentId' => $attachment->id])->handle(
        app(FormatRegistry::class),
        app(KositService::class),
        app(RecordKositValidation::class),
        app(InboxMessagePipelineFinalizer::class),
    );

    $document->refresh();

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::ValidationFailed)
        ->and($document->artifact_content_hash)->toBeNull()
        ->and(Storage::disk('zugferd')->exists($pdfPath))->toBeTrue()
        ->and($document->gateway_status->isSuccessfulTerminal())->toBeFalse();

    Event::assertDispatched(ArtifactValidationFailed::class);
    Event::assertNotDispatched(ArtifactValidated::class);
})->skip(fn (): bool => ! class_exists(ZugferdDocumentPdfMerger::class), 'horstoeko/zugferd is required for hybrid generation');

test('validate artifact job short-circuits when already validated', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::hybridPipelineAttachment($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    $document->update([
        'gateway_status' => EBillingAttachmentProcessingStatus::Validated,
        'xml_storage_path' => 'test/invoice.xml',
        'pdf_storage_path' => 'test/invoice.pdf',
        'artifact_content_hash' => hash('sha256', 'already-validated'),
    ]);

    $this->mock(KositService::class)->shouldNotReceive('validate');

    app(ValidateArtifactJob::class, ['inboxAttachmentId' => $attachment->id])->handle(
        app(FormatRegistry::class),
        app(KositService::class),
        app(RecordKositValidation::class),
        app(InboxMessagePipelineFinalizer::class),
    );

    expect($document->fresh()->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Validated);
});
