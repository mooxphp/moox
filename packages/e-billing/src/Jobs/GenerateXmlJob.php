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
use Moox\EBilling\Adapters\ZugferdInvoiceAdapter;
use Moox\EBilling\Data\Invoice as InvoiceDto;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\XmlGenerated;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Services\ParsedInvoiceMapper;
use Moox\EBilling\Support\EBillingArtifactNaming;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\Zugferd\ZugferdConverter;
use Throwable;

class GenerateXmlJob implements ShouldQueue
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
        ZugferdConverter $zugferdConverter,
        ParsedInvoiceMapper $parsedInvoiceMapper,
        InvoiceFieldValidator $invoiceFieldValidator,
    ): void {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::warning('[EBilling] GenerateXmlJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $document = EbillingDocument::forSourceAttachment($attachment);

        $canRun = $attachment->isPdf() && (
            $attachment->processing_status === InboxAttachmentProcessingStatus::Processing->value
            || $document?->gateway_status === EBillingAttachmentProcessingStatus::XmlGenerating
        );

        if (! $canRun) {
            $this->setProgress(100);

            return;
        }

        $this->setProgress(15);
        $billData = $document?->bill_data;
        if (is_array($billData) && $billData !== []) {
            $dto = InvoiceDto::fromArray($billData);
        } else {
            Log::error('GenerateXmlJob: bill_data missing, cannot generate XML without parsed data', [
                'attachment_id' => $attachment->id,
            ]);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(30);

        $invoice = $parsedInvoiceMapper->createFromDto($dto, $attachment);

        $this->setProgress(45);

        $xml = $zugferdConverter->convert(new ZugferdInvoiceAdapter($invoice));

        $diskName = (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $relativeDir = $attachment->scope.'/'.EBillingArtifactNaming::invoiceDatePathSegment($invoice->invoice_date);

        $existingXmlPath = $document?->xml_storage_path;
        if (
            is_string($existingXmlPath)
            && $existingXmlPath !== ''
            && Storage::disk($diskName)->exists($existingXmlPath)
        ) {
            $relativeXmlPath = $existingXmlPath;
        } else {
            $basename = EBillingArtifactNaming::uniqueBasenameFor($attachment, $diskName, $relativeDir);
            $relativeXmlPath = $relativeDir.'/'.$basename.'.xml';
        }

        Storage::disk($diskName)->put($relativeXmlPath, $xml);

        $this->setProgress(70);

        $billDataArray = $dto->toArray();

        if ($document !== null) {
            $document->zugferd_storage_disk = null;
            $document->zugferd_storage_path = null;
            $document->xml_storage_path = $relativeXmlPath;
            $document->bill_data = $billDataArray;
            $document->save();
        }

        if ($document !== null) {
            $document->refresh();
            $invoiceFieldValidator->validate($document);
        }

        $this->setProgress(90);

        event(new XmlGenerated($attachment->id));

        ValidateXmlJob::dispatch($attachment->id);

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
            $document->gateway_status = EBillingAttachmentProcessingStatus::XmlGenerationFailed;
            $document->save();
        }

        $attachment->markAsFailed($exception?->getMessage() ?? 'GenerateXmlJob failed');

        try {
            app(InboxMessagePipelineFinalizer::class)->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
        } catch (Throwable $e) {
            Log::error('[EBilling] GenerateXmlJob failed() finalizer error', [
                'exception' => $e,
                'inbox_attachment_id' => $attachment->id,
            ]);
        }
    }
}
