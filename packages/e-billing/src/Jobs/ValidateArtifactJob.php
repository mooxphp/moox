<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\ArtifactValidated;
use Moox\EBilling\Events\ArtifactValidationFailed;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Contracts\HybridArtifactGeneratorStrategyInterface;
use Moox\EBilling\Formats\FormatDefinition;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Support\ArtifactValidationPersister;
use Moox\EBilling\Support\EBillingArtifactNaming;
use Moox\Jobs\Traits\JobProgress;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\DTOs\KositResult;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\KositOutputPath;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\VeraPdf\Actions\RecordVeraPdfValidation;
use Moox\VeraPdf\DTOs\VeraPdfResult;
use Moox\VeraPdf\Services\VeraPdfService;
use Moox\VeraPdf\Support\VeraPdfOutputPath;
use Throwable;

class ValidateArtifactJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300];

    public function __construct(
        public int $inboxAttachmentId,
    ) {
    }

    public function handle(
        FormatRegistry $formatRegistry,
        KositService $kosit,
        RecordKositValidation $recordKositValidation,
        VeraPdfService $veraPdf,
        RecordVeraPdfValidation $recordVeraPdfValidation,
        ArtifactValidationPersister $validationPersister,
        InboxMessagePipelineFinalizer $pipelineFinalizer,
    ): void {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::warning('[EBilling] ValidateArtifactJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $document = $this->resolveActionableDocument($attachment, $pipelineFinalizer);

        if ($document === null) {
            return;
        }

        $formatId = $document->format !== ''
            ? $document->format
            : (string) config('e-billing.default_format', 'zugferd');
        $definition = $formatRegistry->get($formatId);

        $diskName = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');

        $xmlRelative = $document->xml_storage_path;
        if ($xmlRelative === null || $xmlRelative === '') {
            throw new InvalidArgumentException(
                'Ebilling document has no xml_storage_path; run GenerateArtifactJob first.'
            );
        }

        $this->setProgress(20);

        $inputs = null;

        try {
            $inputs = $this->resolveValidationInputs($document, $definition, $formatId, $diskName);

            $this->setProgress(40);

            $invoiceData = $document->bill_data;
            $invoiceDate = '';
            if (is_array($invoiceData) && is_string($invoiceData['invoice_date'] ?? null)) {
                $invoiceDate = $invoiceData['invoice_date'];
            }

            $dateSegment = EBillingArtifactNaming::invoiceDatePathSegment($invoiceDate);
            $outcome = $this->runValidations(
                $document,
                $definition,
                $inputs,
                $dateSegment,
                $kosit,
                $veraPdf,
            );

            $this->setProgress(70);

            $supplementalPersisters = $this->supplementalValidationPersisters(
                $outcome['veraPdfResult'],
                $recordVeraPdfValidation,
            );

            if ($outcome['passed']) {
                $this->persistSuccess(
                    $document,
                    $attachment,
                    $definition,
                    $formatId,
                    $diskName,
                    $outcome['kositResult'],
                    $supplementalPersisters,
                    $validationPersister,
                    $recordKositValidation,
                );
            } else {
                $this->persistFailure(
                    $document,
                    $attachment,
                    $formatId,
                    $outcome['errorStrings'],
                    $outcome['kositResult'],
                    $supplementalPersisters,
                    $validationPersister,
                    $recordKositValidation,
                );
            }
        } finally {
            $tempXmlPath = $inputs?->tempXmlPath;
            if (is_string($tempXmlPath) && is_file($tempXmlPath)) {
                @unlink($tempXmlPath);
            }
        }

        $this->setProgress(90);

        $pipelineFinalizer->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);

        $this->setProgress(100);
    }

    /**
     * Returns the document to validate, or null when handle() must return early.
     * Already-validated short-circuit finalizes the pipeline; unexpected statuses skip.
     */
    private function resolveActionableDocument(
        InboxAttachment $attachment,
        InboxMessagePipelineFinalizer $pipelineFinalizer,
    ): ?EbillingDocument {
        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document?->gateway_status === EBillingAttachmentProcessingStatus::Validated) {
            $this->setProgress(100);
            $pipelineFinalizer->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);

            return null;
        }

        $allowedStatuses = [
            EBillingAttachmentProcessingStatus::Validating,
            EBillingAttachmentProcessingStatus::ValidationFailed,
            EBillingAttachmentProcessingStatus::ValidatorError,
        ];

        if (! in_array($document?->gateway_status, $allowedStatuses, true)) {
            Log::notice('[EBilling] ValidateArtifactJob: unexpected gateway_status, skipping', [
                'inbox_attachment_id' => $attachment->id,
                'gateway_status' => $document?->gateway_status?->value,
                'processing_status' => $attachment->processing_status,
            ]);
            $this->setProgress(100);

            return null;
        }

        return $document;
    }

    /**
     * Builds absolute XML path (and PDF path / temp file for hybrid PDF formats).
     */
    private function resolveValidationInputs(
        EbillingDocument $document,
        FormatDefinition $definition,
        string $formatId,
        string $diskName,
    ): ValidationInputs {
        if ($definition->artifactKind === ArtifactKind::Pdf) {
            $strategy = $definition->strategy;
            if (! $strategy instanceof HybridArtifactGeneratorStrategyInterface) {
                throw new LogicException(
                    "Format [{$formatId}] declares a PDF artifact but its strategy does not "
                    .'implement HybridArtifactGeneratorStrategyInterface.'
                );
            }

            $pdfRelative = $document->pdf_storage_path;
            if ($pdfRelative === null || $pdfRelative === '') {
                throw new InvalidArgumentException(
                    'Ebilling document has no pdf_storage_path; run GenerateArtifactJob first.'
                );
            }

            $absolutePdfPath = Storage::disk($diskName)->path($pdfRelative);
            $xmlString = $strategy->extractXmlForValidation($absolutePdfPath);
            $tempXmlPath = tempnam(sys_get_temp_dir(), 'ebilling-kosit-');
            if ($tempXmlPath === false) {
                throw new \RuntimeException('Failed to allocate temp file for KOSIT validation.');
            }
            file_put_contents($tempXmlPath, $xmlString);

            return new ValidationInputs(
                absoluteXmlPath: $tempXmlPath,
                absolutePdfPath: $absolutePdfPath,
                tempXmlPath: $tempXmlPath,
            );
        }

        $xmlRelative = $document->xml_storage_path;
        if ($xmlRelative === null || $xmlRelative === '') {
            throw new InvalidArgumentException(
                'Ebilling document has no xml_storage_path; run GenerateArtifactJob first.'
            );
        }

        return new ValidationInputs(
            absoluteXmlPath: Storage::disk($diskName)->path($xmlRelative),
            absolutePdfPath: null,
            tempXmlPath: null,
        );
    }

    /**
     * Runs KOSIT (+ optional veraPDF) and aggregates the outcome.
     *
     * @return array{
     *     kositResult: KositResult,
     *     veraPdfResult: ?VeraPdfResult,
     *     passed: bool,
     *     errorStrings: list<string>,
     * }
     */
    private function runValidations(
        EbillingDocument $document,
        FormatDefinition $definition,
        ValidationInputs $inputs,
        string $dateSegment,
        KositService $kosit,
        VeraPdfService $veraPdf,
    ): array {
        $kositReportDirectory = KositOutputPath::resolve($dateSegment);
        $kositResult = $kosit->validate($inputs->absoluteXmlPath, $kositReportDirectory);

        $this->setProgress(55);

        $veraPdfResult = null;
        $veraPdfConfigured = $definition->artifactKind === ArtifactKind::Pdf
            && $veraPdf->isInstalled()
            && $veraPdf->javaAvailable();

        if ($veraPdfConfigured) {
            if ($inputs->absolutePdfPath === null) {
                throw new LogicException('Hybrid validation requires an absolute PDF path.');
            }

            $veraPdfReportDirectory = VeraPdfOutputPath::resolve(
                $dateSegment.'/'.($document->getKey() ?? 'unknown')
            );
            $veraPdfResult = $veraPdf->validate($inputs->absolutePdfPath, $veraPdfReportDirectory);
        }

        $kositPassed = $kositResult->passed();
        $veraPdfPassed = $veraPdfResult === null || $veraPdfResult->passed();
        $passed = $kositPassed && $veraPdfPassed;

        $errorStrings = array_values(array_merge(
            $kositPassed ? [] : $kositResult->errors(),
            ($veraPdfResult !== null && ! $veraPdfPassed) ? $veraPdfResult->errors() : [],
        ));

        return [
            'kositResult' => $kositResult,
            'veraPdfResult' => $veraPdfResult,
            'passed' => $passed,
            'errorStrings' => $errorStrings,
        ];
    }

    /**
     * Passing branch: read deliverable, hash, transactional persist + status, event.
     *
     * @param  list<\Closure(EbillingDocument): void>  $supplementalPersisters
     */
    private function persistSuccess(
        EbillingDocument $document,
        InboxAttachment $attachment,
        FormatDefinition $definition,
        string $formatId,
        string $diskName,
        KositResult $kositResult,
        array $supplementalPersisters,
        ArtifactValidationPersister $validationPersister,
        RecordKositValidation $recordKositValidation,
    ): void {
        $deliverablePath = $document->deliverableStoragePath($definition->artifactKind);
        if ($deliverablePath === null || $deliverablePath === '') {
            throw new InvalidArgumentException('Validated document has no deliverable storage path.');
        }

        $artifactContent = Storage::disk($diskName)->get($deliverablePath);
        if (! is_string($artifactContent)) {
            throw new InvalidArgumentException('Deliverable artifact is missing from storage.');
        }

        $hash = hash('sha256', $artifactContent);

        DB::transaction(function () use (
            $validationPersister,
            $recordKositValidation,
            $kositResult,
            $supplementalPersisters,
            $document,
            $attachment,
            $hash,
        ): void {
            $validationPersister->persist(
                $document,
                $kositResult,
                $recordKositValidation,
                $supplementalPersisters,
            );

            $document->artifact_content_hash = $hash;
            $document->gateway_status = EBillingAttachmentProcessingStatus::Validated;
            $document->processed_at = now();
            $document->save();

            $attachment->markAsProcessed();
        });

        event(new ArtifactValidated($attachment->id, $formatId));
    }

    /**
     * Failing branch: transactional persist + ValidationFailed status, failure event.
     *
     * @param  list<string>  $errorStrings
     * @param  list<\Closure(EbillingDocument): void>  $supplementalPersisters
     */
    private function persistFailure(
        EbillingDocument $document,
        InboxAttachment $attachment,
        string $formatId,
        array $errorStrings,
        KositResult $kositResult,
        array $supplementalPersisters,
        ArtifactValidationPersister $validationPersister,
        RecordKositValidation $recordKositValidation,
    ): void {
        $failureMessage = $errorStrings !== [] ? implode('; ', $errorStrings) : 'Artifact validation failed';

        DB::transaction(function () use (
            $validationPersister,
            $recordKositValidation,
            $kositResult,
            $supplementalPersisters,
            $attachment,
            $document,
            $failureMessage,
        ): void {
            $validationPersister->persist(
                $document,
                $kositResult,
                $recordKositValidation,
                $supplementalPersisters,
            );

            $document->gateway_status = EBillingAttachmentProcessingStatus::ValidationFailed;
            $document->save();

            $attachment->markAsFailed($failureMessage);
        });

        event(new ArtifactValidationFailed($attachment->id, array_values($errorStrings), $formatId));
    }

    /**
     * @return list<\Closure(EbillingDocument): void>
     */
    private function supplementalValidationPersisters(
        mixed $veraPdfResult,
        RecordVeraPdfValidation $recordVeraPdfValidation,
    ): array {
        if (! $veraPdfResult instanceof VeraPdfResult) {
            return [];
        }

        return [
            static function (EbillingDocument $document) use ($veraPdfResult, $recordVeraPdfValidation): void {
                $veraPdfValidation = $recordVeraPdfValidation($veraPdfResult);
                $document->veraPdfValidations()->attach($veraPdfValidation->id);
            },
        ];
    }

    public function failed(?Throwable $exception = null): void
    {
        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            return;
        }

        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document !== null) {
            $document->gateway_status = EBillingAttachmentProcessingStatus::ValidatorError;
            $document->save();
        }

        $attachment->markAsFailed($exception?->getMessage() ?? 'ValidateArtifactJob failed');

        try {
            app(InboxMessagePipelineFinalizer::class)
                ->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
        } catch (Throwable $e) {
            Log::error('[EBilling] ValidateArtifactJob failed() finalizer error', [
                'exception' => $e,
                'inbox_attachment_id' => $attachment->id,
            ]);
        }
    }
}
