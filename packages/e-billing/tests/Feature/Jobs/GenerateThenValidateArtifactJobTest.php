<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\ArtifactValidated;
use Moox\EBilling\Events\ArtifactValidationFailed;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Contracts\HybridArtifactGeneratorStrategyInterface;
use Moox\EBilling\Formats\FormatDefinition;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Jobs\ValidateArtifactJob;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Tests\Support\InvoiceFixtures;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\DTOs\KositResult;
use Moox\KositValidator\Services\KositService;
use Moox\VeraPdf\Actions\RecordVeraPdfValidation;
use Moox\VeraPdf\DTOs\VeraPdfResult;
use Moox\VeraPdf\Services\VeraPdfService;

uses(TestCase::class);

/**
 * @return array{0: MockInterface&KositService, 1: MockInterface&VeraPdfService}
 */
function mockKositOnlyValidation(): array
{
    $kosit = Mockery::mock(KositService::class);
    app()->instance(KositService::class, $kosit);

    $veraPdf = Mockery::mock(VeraPdfService::class);
    app()->instance(VeraPdfService::class, $veraPdf);
    $veraPdf->shouldReceive('isInstalled')->andReturn(false);
    $veraPdf->shouldReceive('javaAvailable')->andReturn(false);
    $veraPdf->shouldNotReceive('validate');

    return [$kosit, $veraPdf];
}

function mockValidationServicesDisabled(): void
{
    $kosit = Mockery::mock(KositService::class);
    app()->instance(KositService::class, $kosit);
    $kosit->shouldNotReceive('validate');

    $veraPdf = Mockery::mock(VeraPdfService::class);
    app()->instance(VeraPdfService::class, $veraPdf);
    $veraPdf->shouldNotReceive('validate');
}

function mockDualValidationServices(): array
{
    $kosit = Mockery::mock(KositService::class);
    app()->instance(KositService::class, $kosit);

    $veraPdf = Mockery::mock(VeraPdfService::class);
    app()->instance(VeraPdfService::class, $veraPdf);
    $veraPdf->shouldReceive('isInstalled')->andReturn(true);
    $veraPdf->shouldReceive('javaAvailable')->andReturn(true);

    return [$kosit, $veraPdf];
}

function mockHybridXmlExtraction(): HybridArtifactGeneratorStrategyInterface
{
    $strategy = mock(HybridArtifactGeneratorStrategyInterface::class);
    $strategy->shouldReceive('extractXmlForValidation')
        ->andReturn('<?xml version="1.0"?><invoice/>');

    app()->forgetInstance(FormatRegistry::class);

    app()->singleton(FormatRegistry::class, function () use ($strategy): FormatRegistry {
        $registry = new FormatRegistry;
        $registry->register(new FormatDefinition(
            id: 'zugferd',
            label: 'ZUGFeRD',
            artifactKind: ArtifactKind::Pdf,
            profile: 'EN16931',
            strategy: $strategy,
        ));

        return $registry;
    });

    return $strategy;
}

function runValidateArtifactJob(int $attachmentId): void
{
    app(ValidateArtifactJob::class, ['inboxAttachmentId' => $attachmentId])->handle(
        app(FormatRegistry::class),
        app(KositService::class),
        app(RecordKositValidation::class),
        app(VeraPdfService::class),
        app(RecordVeraPdfValidation::class),
        app(InboxMessagePipelineFinalizer::class),
    );
}

test('validate artifact job seam passes for hybrid zugferd in kosit-only degraded mode', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::validatingHybridDocument($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    mockHybridXmlExtraction();

    Event::fake([ArtifactValidated::class, ArtifactValidationFailed::class]);

    [$kosit] = mockKositOnlyValidation();
    $kosit->shouldReceive('validate')
        ->once()
        ->andReturn(new KositResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
            reportXmlPath: null,
            reportHtmlPath: null,
            xmlPath: '/tmp/validated.xml',
        ));

    runValidateArtifactJob($attachment->id);

    $document->refresh();

    $pdfContent = Storage::disk('zugferd')->get((string) $document->pdf_storage_path);

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Validated)
        ->and($document->artifact_content_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($document->artifact_content_hash)->toBe(hash('sha256', (string) $pdfContent))
        ->and($document->latestKositValidation()?->passed)->toBeTrue()
        ->and($document->latestVeraPdfValidation())->toBeNull();

    Event::assertDispatched(ArtifactValidated::class);
    Event::assertNotDispatched(ArtifactValidationFailed::class);
});

test('failed validation retains artifact and is not deliverable', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::validatingHybridDocument($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    mockHybridXmlExtraction();

    Event::fake([ArtifactValidated::class, ArtifactValidationFailed::class]);

    [$kosit] = mockKositOnlyValidation();
    $kosit->shouldReceive('validate')
        ->once()
        ->andReturn(new KositResult(
            exitCode: 1,
            stdout: '',
            stderr: 'KOSIT validation failed',
            reportXmlPath: null,
            reportHtmlPath: null,
            xmlPath: '/tmp/validated.xml',
        ));

    $pdfPath = (string) $document->pdf_storage_path;

    runValidateArtifactJob($attachment->id);

    $document->refresh();

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::ValidationFailed)
        ->and($document->artifact_content_hash)->toBeNull()
        ->and(Storage::disk('zugferd')->exists($pdfPath))->toBeTrue()
        ->and($document->gateway_status->isSuccessfulTerminal())->toBeFalse();

    Event::assertDispatched(ArtifactValidationFailed::class);
    Event::assertNotDispatched(ArtifactValidated::class);
});

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

    mockValidationServicesDisabled();

    runValidateArtifactJob($attachment->id);

    expect($document->fresh()->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Validated);
});

test('hybrid validation passes only when kosit and verapdf both pass', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::validatingHybridDocument($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    mockHybridXmlExtraction();

    Event::fake([ArtifactValidated::class, ArtifactValidationFailed::class]);

    [$kosit, $veraPdf] = mockDualValidationServices();
    $kosit->shouldReceive('validate')
        ->once()
        ->andReturn(new KositResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
            reportXmlPath: null,
            reportHtmlPath: null,
            xmlPath: '/tmp/validated.xml',
        ));
    $veraPdf->shouldReceive('validate')
        ->once()
        ->andReturn(new VeraPdfResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
            reportXmlPath: null,
            reportHtmlPath: null,
            pdfPath: '/tmp/validated.pdf',
        ));

    runValidateArtifactJob($attachment->id);

    $document->refresh();

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Validated)
        ->and($document->latestKositValidation()?->passed)->toBeTrue()
        ->and($document->latestVeraPdfValidation()?->passed)->toBeTrue();

    Event::assertDispatched(ArtifactValidated::class);
});

test('hybrid validation fails when verapdf fails though kosit passes', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::validatingHybridDocument($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    mockHybridXmlExtraction();

    Event::fake([ArtifactValidated::class, ArtifactValidationFailed::class]);

    [$kosit, $veraPdf] = mockDualValidationServices();
    $kosit->shouldReceive('validate')
        ->once()
        ->andReturn(new KositResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
            reportXmlPath: null,
            reportHtmlPath: null,
            xmlPath: '/tmp/validated.xml',
        ));
    $veraPdf->shouldReceive('validate')
        ->once()
        ->andReturn(new VeraPdfResult(
            exitCode: 1,
            stdout: '',
            stderr: 'PDF/A-3 conformance failed',
            reportXmlPath: null,
            reportHtmlPath: null,
            pdfPath: '/tmp/validated.pdf',
        ));

    runValidateArtifactJob($attachment->id);

    $document->refresh();

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::ValidationFailed)
        ->and($document->artifact_content_hash)->toBeNull()
        ->and($document->latestKositValidation()?->passed)->toBeTrue()
        ->and($document->latestVeraPdfValidation()?->passed)->toBeFalse();

    Event::assertDispatched(ArtifactValidationFailed::class);
    Event::assertNotDispatched(ArtifactValidated::class);
});

test('hybrid validation surfaces validator error on verapdf tooling failure', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $billData = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    )->toArray();

    $fixture = PipelineFixtures::validatingHybridDocument($billData);
    $attachment = $fixture['attachment'];
    $document = $fixture['document'];

    mockHybridXmlExtraction();

    [$kosit, $veraPdf] = mockDualValidationServices();
    $kosit->shouldReceive('validate')
        ->once()
        ->andReturn(new KositResult(
            exitCode: 0,
            stdout: '',
            stderr: '',
            reportXmlPath: null,
            reportHtmlPath: null,
            xmlPath: '/tmp/validated.xml',
        ));
    $veraPdf->shouldReceive('validate')
        ->once()
        ->andThrow(new RuntimeException('veraPDF launcher crashed'));

    try {
        runValidateArtifactJob($attachment->id);
    } catch (RuntimeException) {
        app(ValidateArtifactJob::class, ['inboxAttachmentId' => $attachment->id])->failed(
            new RuntimeException('veraPDF launcher crashed')
        );
    }

    $document->refresh();

    expect($document->gateway_status)->toBe(EBillingAttachmentProcessingStatus::ValidatorError)
        ->and($document->artifact_content_hash)->toBeNull();
});
