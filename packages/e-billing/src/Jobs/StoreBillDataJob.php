<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\EBilling;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Throwable;

/**
 * Parses the PDF once and persists bill data on the ebilling document before the foreign-invoice filter and XML generation run.
 */
final class StoreBillDataJob implements ShouldQueue
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
        public string $ebillingDocumentId,
    ) {}

    public function handle(EBilling $eBilling): void
    {
        $this->setProgress(0);

        $document = EbillingDocument::query()->find($this->ebillingDocumentId);

        if (! $document instanceof EbillingDocument) {
            Log::warning('[EBilling] StoreBillDataJob: document not found', [
                'ebilling_document_id' => $this->ebillingDocumentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $attachment = $document->inboxAttachment();

        if ($attachment !== null) {
            if (! $attachment->isPdf()) {
                $this->setProgress(100);

                return;
            }

            if ($attachment->processing_status !== InboxAttachmentProcessingStatus::Processing->value) {
                $this->setProgress(100);

                return;
            }
        }

        $this->setProgress(20);

        $invoice = $eBilling->parseInvoiceFromPdf($document->sourceFullPath());
        $document->bill_data = $invoice->toArray();
        $document->save();

        $this->setProgress(80);

        FilterForeignInvoiceJob::dispatch($document->getKey());

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::error('[EBilling] StoreBillDataJob failed', [
            'ebilling_document_id' => $this->ebillingDocumentId,
            'exception' => $exception,
        ]);

        $document = EbillingDocument::query()->find($this->ebillingDocumentId);

        if (! $document instanceof EbillingDocument || $document->inboxAttachment() !== null) {
            return;
        }

        $document->gateway_status = EBillingAttachmentProcessingStatus::GenerationFailed;
        $document->save();
    }
}
