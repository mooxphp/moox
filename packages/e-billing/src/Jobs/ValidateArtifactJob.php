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
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Support\EBillingArtifactNaming;
use Moox\Jobs\Traits\JobProgress;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\KositOutputPath;
use Moox\MailInbox\Models\InboxAttachment;
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
    ) {}

    public function handle(
        FormatRegistry $formatRegistry,
        KositService $kosit,
        RecordKositValidation $recordKositValidation,
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

        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document?->gateway_status === EBillingAttachmentProcessingStatus::Validated) {
            $this->setProgress(100);
            $pipelineFinalizer->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);

            return;
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

            return;
        }

        $formatId = is_string($document?->format) && $document->format !== ''
            ? $document->format
            : (string) config('e-billing.default_format', 'zugferd');
        $definition = $formatRegistry->get($formatId);

        $diskName = $document?->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');

        $xmlRelative = $document?->xml_storage_path;
        if ($xmlRelative === null || $xmlRelative === '') {
            throw new InvalidArgumentException(
                'Ebilling document has no xml_storage_path; run GenerateArtifactJob first.'
            );
        }

        $this->setProgress(20);

        $tempXmlPath = null;

        try {
            if ($definition->artifactKind === ArtifactKind::Pdf) {
                $strategy = $definition->strategy;
                if (! $strategy instanceof HybridArtifactGeneratorStrategyInterface) {
                    throw new LogicException("Format [{$formatId}] declares a PDF artifact but its strategy does not implement HybridArtifactGeneratorStrategyInterface.");
                }

                $pdfRelative = $document?->pdf_storage_path;
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
                $absoluteXmlPath = $tempXmlPath;
            } else {
                $absoluteXmlPath = Storage::disk($diskName)->path($xmlRelative);
            }

            $this->setProgress(40);

            $invoiceData = $document?->bill_data;
            $invoiceDate = '';
            if (is_array($invoiceData) && is_string($invoiceData['invoice_date'] ?? null)) {
                $invoiceDate = $invoiceData['invoice_date'];
            }

            $dateSegment = EBillingArtifactNaming::invoiceDatePathSegment($invoiceDate);
            $kositReportDirectory = KositOutputPath::resolve($dateSegment);

            $result = $kosit->validate($absoluteXmlPath, $kositReportDirectory);

            $this->setProgress(70);

            $errorStrings = $result->errors();

            if ($result->passed()) {
                $deliverablePath = $document?->deliverableStoragePath($definition->artifactKind);
                if ($deliverablePath === null || $deliverablePath === '') {
                    throw new InvalidArgumentException('Validated document has no deliverable storage path.');
                }

                $artifactContent = Storage::disk($diskName)->get($deliverablePath);
                if (! is_string($artifactContent)) {
                    throw new InvalidArgumentException('Deliverable artifact is missing from storage.');
                }

                $hash = hash('sha256', $artifactContent);

                DB::transaction(function () use ($recordKositValidation, $result, $document, $attachment, $hash): void {
                    $validation = $recordKositValidation($result);

                    if ($document !== null) {
                        $document->kositValidations()->attach($validation->id);
                        $document->artifact_content_hash = $hash;
                        $document->gateway_status = EBillingAttachmentProcessingStatus::Validated;
                        $document->processed_at = now();
                        $document->save();
                    }

                    $attachment->markAsProcessed();
                });

                event(new ArtifactValidated($attachment->id, $formatId));
            } else {
                $failureMessage = $errorStrings !== [] ? implode('; ', $errorStrings) : 'Artifact validation failed';

                DB::transaction(function () use ($recordKositValidation, $result, $attachment, $document, $failureMessage): void {
                    $validation = $recordKositValidation($result);

                    if ($document !== null) {
                        $document->kositValidations()->attach($validation->id);
                        $document->gateway_status = EBillingAttachmentProcessingStatus::ValidationFailed;
                        $document->save();
                    }

                    $attachment->markAsFailed($failureMessage);
                });

                event(new ArtifactValidationFailed($attachment->id, array_values($errorStrings), $formatId));
            }
        } finally {
            if (is_string($tempXmlPath) && is_file($tempXmlPath)) {
                @unlink($tempXmlPath);
            }
        }

        $this->setProgress(90);

        $pipelineFinalizer->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);

        $this->setProgress(100);
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
            app(InboxMessagePipelineFinalizer::class)->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
        } catch (Throwable $e) {
            Log::error('[EBilling] ValidateArtifactJob failed() finalizer error', [
                'exception' => $e,
                'inbox_attachment_id' => $attachment->id,
            ]);
        }
    }
}
