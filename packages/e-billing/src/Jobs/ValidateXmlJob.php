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
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\XmlValidationFailed;
use Moox\EBilling\Events\XmlValidationPassed;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Support\EBillingArtifactNaming;
use Moox\Jobs\Traits\JobProgress;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\KositOutputPath;
use Moox\MailInbox\Models\InboxAttachment;
use Throwable;

class ValidateXmlJob implements ShouldQueue
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
        KositService $kosit,
        RecordKositValidation $recordKositValidation,
        InboxMessagePipelineFinalizer $pipelineFinalizer,
    ): void {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::warning('[EBilling] ValidateXmlJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document?->gateway_status === EBillingAttachmentProcessingStatus::XmlValidated) {
            $zugferdPath = $document?->pdf_storage_path;
            if (is_string($zugferdPath) && $zugferdPath !== '') {
                $this->setProgress(100);
                $pipelineFinalizer->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);

                return;
            }

            MergeZugferdPdfJob::dispatch($this->inboxAttachmentId);
            $this->setProgress(100);
            $pipelineFinalizer->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);

            return;
        }

        $allowedStatuses = [
            EBillingAttachmentProcessingStatus::XmlGenerating,
            EBillingAttachmentProcessingStatus::XmlValidationFailed,
            EBillingAttachmentProcessingStatus::KositError,
        ];

        if (! in_array($document?->gateway_status, $allowedStatuses, true)) {
            Log::notice('[EBilling] ValidateXmlJob: unexpected gateway_status, skipping', [
                'inbox_attachment_id' => $attachment->id,
                'gateway_status' => $document?->gateway_status?->value,
                'processing_status' => $attachment->processing_status,
            ]);
            $this->setProgress(100);

            return;
        }

        $xmlRelative = $document?->xml_storage_path;
        if ($xmlRelative === null || $xmlRelative === '') {
            throw new InvalidArgumentException(
                'Ebilling document has no xml_storage_path; run GenerateXmlJob first.'
            );
        }

        $diskName = $document?->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');

        $absoluteXmlPath = Storage::disk($diskName)->path($xmlRelative);

        $this->setProgress(30);

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
            DB::transaction(function () use ($recordKositValidation, $result, $document): void {
                $validation = $recordKositValidation($result);

                if ($document !== null) {
                    $document->kositValidations()->attach($validation->id);
                    $document->gateway_status = EBillingAttachmentProcessingStatus::XmlValidated;
                    $document->save();
                }
            });

            event(new XmlValidationPassed($attachment->id));

            MergeZugferdPdfJob::dispatch($this->inboxAttachmentId);
        } else {
            $failureMessage = $errorStrings !== [] ? implode('; ', $errorStrings) : 'XML validation failed';

            DB::transaction(function () use ($recordKositValidation, $result, $attachment, $document, $failureMessage): void {
                $validation = $recordKositValidation($result);

                if ($document !== null) {
                    $document->kositValidations()->attach($validation->id);
                    $document->gateway_status = EBillingAttachmentProcessingStatus::XmlValidationFailed;
                    $document->save();
                }

                $attachment->markAsFailed($failureMessage);
            });

            event(new XmlValidationFailed($attachment->id, array_values($errorStrings)));
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
            $document->gateway_status = EBillingAttachmentProcessingStatus::KositError;
            $document->save();
        }

        $attachment->markAsFailed($exception?->getMessage() ?? 'ValidateXmlJob failed');

        try {
            app(InboxMessagePipelineFinalizer::class)->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
        } catch (Throwable $e) {
            Log::error('[EBilling] ValidateXmlJob failed() finalizer error', [
                'exception' => $e,
                'inbox_attachment_id' => $attachment->id,
            ]);
        }
    }
}
