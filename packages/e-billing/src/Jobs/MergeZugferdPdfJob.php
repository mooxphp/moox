<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\ZugferdPdfGenerated;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\Zugferd\ZugferdConverter;
use Throwable;

class MergeZugferdPdfJob implements ShouldQueue
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
        ZugferdConverter $converter,
        InboxMessagePipelineFinalizer $pipelineFinalizer,
    ): void {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::warning('[EBilling] MergeZugferdPdfJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document?->gateway_status === EBillingAttachmentProcessingStatus::ZugferdPdfGenerated
            && $document->zugferd_storage_path !== null) {
            $this->setProgress(100);

            return;
        }

        $validation = $document?->latestKositValidation();

        if ($validation === null || $validation->passed !== true) {
            Log::warning('[EBilling] MergeZugferdPdfJob: skipping merge — no passed KositValidation', [
                'inbox_attachment_id' => $attachment->id,
                'kosit_validation_id' => $validation?->id,
                'validation_passed' => $validation?->passed,
                'processing_status' => $attachment->processing_status,
            ]);
            $this->setProgress(100);

            return;
        }

        $allowedStatuses = [
            EBillingAttachmentProcessingStatus::XmlValidated,
            EBillingAttachmentProcessingStatus::ZugferdPdfGenerating,
            EBillingAttachmentProcessingStatus::ZugferdPdfFailed,
            EBillingAttachmentProcessingStatus::ZugferdPdfGenerated,
        ];

        if (! in_array($document?->gateway_status, $allowedStatuses, true)) {
            Log::notice('[EBilling] MergeZugferdPdfJob: unexpected gateway_status, skipping', [
                'inbox_attachment_id' => $attachment->id,
                'gateway_status' => $document?->gateway_status?->value,
                'processing_status' => $attachment->processing_status,
            ]);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(15);

        if ($document !== null) {
            $document->gateway_status = EBillingAttachmentProcessingStatus::ZugferdPdfGenerating;
            $document->save();
        }

        $diskName = $document?->zugferd_storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');

        $xmlRelative = $document?->xml_storage_path;
        if ($xmlRelative === null || $xmlRelative === '') {
            throw new InvalidArgumentException(
                'Ebilling document has no xml_storage_path; run GenerateXmlJob first.'
            );
        }

        $xmlString = Storage::disk($diskName)->get($xmlRelative);
        if (! is_string($xmlString) || $xmlString === '') {
            throw new InvalidArgumentException(
                'XML file is missing or empty at path: '.$xmlRelative.' on disk '.$diskName
            );
        }

        $invoiceData = $document?->bill_data;
        if (! is_array($invoiceData) || $invoiceData === []) {
            throw new InvalidArgumentException(
                'Ebilling document has no bill_data; run GenerateXmlJob first.'
            );
        }

        $this->setProgress(35);

        $pdfBinary = $converter->mergePdfWithXml($attachment->fullPath(), $xmlString);

        $this->setProgress(65);

        $basename = pathinfo($xmlRelative, PATHINFO_FILENAME);
        $dir = pathinfo($xmlRelative, PATHINFO_DIRNAME);
        $relativePdfPath = $dir.'/'.$basename.'.pdf';

        Storage::disk($diskName)->put($relativePdfPath, $pdfBinary);

        $this->setProgress(85);

        if ($document !== null) {
            $document->zugferd_storage_disk = $diskName;
            $document->zugferd_storage_path = $relativePdfPath;
            $document->gateway_status = EBillingAttachmentProcessingStatus::ZugferdPdfGenerated;
            $document->processed_at = now();
            $document->save();
        }

        $attachment->markAsProcessed();

        event(new ZugferdPdfGenerated($attachment->id));

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
            $document->gateway_status = EBillingAttachmentProcessingStatus::ZugferdPdfFailed;
            $document->save();
        }

        $attachment->markAsFailed($exception?->getMessage() ?? 'MergeZugferdPdfJob failed');

        try {
            app(InboxMessagePipelineFinalizer::class)->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
        } catch (Throwable $e) {
            Log::error('[EBilling] MergeZugferdPdfJob failed() finalizer error', [
                'exception' => $e,
                'inbox_attachment_id' => $attachment->id,
            ]);
        }
    }
}
