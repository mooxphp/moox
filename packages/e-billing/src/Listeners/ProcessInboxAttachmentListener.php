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

        if (! $this->scopeIsAllowedForIntake((string) $attachment->scope)) {
            $attachment->markAsSkipped();

            return;
        }

        $document = $this->resolveOrCreateEbillingDocument($attachment);

        StoreBillDataJob::dispatch($document->getKey());
    }

    private function scopeIsAllowedForIntake(string $scope): bool
    {
        $scopes = config('e-billing.intake.scopes');

        if (! is_array($scopes)) {
            return true;
        }

        $allowed = array_values(array_filter(
            $scopes,
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        ));

        return $allowed === [] || in_array($scope, $allowed, true);
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
