<?php

declare(strict_types=1);

namespace Moox\EBilling\Listeners;

use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Jobs\StoreBillDataJob;
use Moox\EBilling\Models\EbillingDocument;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Events\InboxAttachmentProcessed;
use Moox\MailInbox\Models\InboxAttachment;

class ProcessInboxAttachmentListener
{
    public function handle(InboxAttachmentProcessed $event): void
    {
        $attachment = $event->attachment->fresh();

        if ($attachment === null || ! $attachment->isPdf()
            || $attachment->processing_status !== InboxAttachmentProcessingStatus::Processing->value) {
            return;
        }

        if ($attachment->message === null) {
            return;
        }

        $this->resolveOrCreateEbillingDocument($attachment);

        StoreBillDataJob::dispatch($attachment->id);
    }

    private function resolveOrCreateEbillingDocument(InboxAttachment $attachment): EbillingDocument
    {
        /** @var EbillingDocument $document */
        $document = EbillingDocument::query()->firstOrCreate(
            [
                'source_type' => $attachment->getMorphClass(),
                'source_id' => $attachment->getKey(),
            ],
            [
                'scope' => $attachment->scope,
                'gateway_status' => null,
                'review_status' => InvoiceProcessingStatus::ParserCreated,
            ],
        );

        return $document;
    }
}
