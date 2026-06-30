<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\EBilling;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Models\InboxAttachment;

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
        public int $inboxAttachmentId,
    ) {}

    public function handle(EBilling $eBilling): void
    {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::warning('[EBilling] StoreBillDataJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        if (! $attachment->isPdf()) {
            $this->setProgress(100);

            return;
        }

        if ($attachment->processing_status !== InboxAttachmentProcessingStatus::Processing->value) {
            $this->setProgress(100);

            return;
        }

        $this->setProgress(20);

        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document === null) {
            Log::warning('[EBilling] StoreBillDataJob: no EbillingDocument found for attachment', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
                'attachment_id' => $attachment->id,
            ]);
            $this->setProgress(100);

            return;
        }

        $invoice = $eBilling->parseInvoiceFromPdf($attachment->fullPath());
        $document->bill_data = $invoice->toArray();
        $document->save();

        $this->setProgress(80);

        FilterForeignInvoiceJob::dispatch($this->inboxAttachmentId);

        $this->setProgress(100);
    }
}
