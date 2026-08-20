<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Illuminate\Support\Facades\Log;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\InboxDriverManager;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use Throwable;

/**
 * Mirrors {@see MailInboxService::finalizeMessageProcessingAfterAttachments}
 * with awareness of gateway attachment statuses on {@see EbillingDocument::$gateway_status} so messages can
 * complete without modifying moox/mail-inbox.
 */
class InboxMessagePipelineFinalizer
{
    public function __construct(
        private InboxDriverManager $drivers,
    ) {
    }

    public function finalizeAfterAttachmentPipelineStep(?int $inboxMessageId): void
    {
        if ($inboxMessageId === null) {
            return;
        }

        $message = InboxMessage::query()->with('attachments')->find($inboxMessageId);

        if ($message === null) {
            return;
        }

        if ($message->processing_status !== InboxMessageProcessingStatus::PartiallyFailed->value
            && in_array($message->processing_status, [
                InboxMessageProcessingStatus::Processed->value,
                InboxMessageProcessingStatus::Failed->value,
            ], true)
        ) {
            return;
        }

        foreach ($message->attachments as $attachment) {
            if ($attachment->processing_status === InboxAttachmentProcessingStatus::New->value && ! $attachment->is_pdf) {
                $attachment->markAsSkipped();
            }
        }

        $message->load('attachments');

        if ($message->processing_status === InboxMessageProcessingStatus::PartiallyFailed->value
            && $message->attachments->isEmpty()
        ) {
            $error = $message->error_message !== null && $message->error_message !== ''
                ? $message->error_message
                : 'Attachment storage failed';
            $message->markAsFailed($error);
            $this->settleMessage($message, false);

            return;
        }

        if ($message->attachments->contains(fn (InboxAttachment $a): bool => $this->attachmentIsInFlight($a))) {
            return;
        }

        $hasFailed = $message->attachments->contains(
            fn (InboxAttachment $a): bool => $this->attachmentHasFailure($a)
        );

        $allPdfs = $message->pdfAttachments()->get();

        if ($message->processing_status === InboxMessageProcessingStatus::PartiallyFailed->value) {
            if ($hasFailed) {
                $error = $message->error_message !== null && $message->error_message !== ''
                    ? $message->error_message
                    : 'One or more attachments failed processing';
                $message->markAsFailed($error);
                $this->settleMessage($message, false);
            } else {
                $message->error_message = null;
                $message->markAsProcessed();
                $this->settleMessage($message, true);
            }

            return;
        }

        if ($allPdfs->isEmpty()) {
            $message->markAsProcessed();
            $this->settleMessage($message, true);

            return;
        }

        if ($hasFailed) {
            $message->markAsFailed('One or more attachments failed processing');
            $this->settleMessage($message, false);

            return;
        }

        if ($allPdfs->every(fn (InboxAttachment $a): bool => $this->pdfPipelineComplete($a))) {
            $message->markAsProcessed();
            $this->settleMessage($message, true);
        }
    }

    private function attachmentIsInFlight(InboxAttachment $attachment): bool
    {
        if (in_array($attachment->processing_status, [
            InboxAttachmentProcessingStatus::New->value,
            InboxAttachmentProcessingStatus::Processing->value,
        ], true)) {
            return true;
        }

        return in_array($this->gatewayStatus($attachment), [
            EBillingAttachmentProcessingStatus::Generating,
            EBillingAttachmentProcessingStatus::Validating,
        ], true);
    }

    private function attachmentHasFailure(InboxAttachment $attachment): bool
    {
        if ($attachment->processing_status === InboxAttachmentProcessingStatus::Failed->value) {
            return true;
        }

        return in_array($this->gatewayStatus($attachment), [
            EBillingAttachmentProcessingStatus::GenerationFailed,
            EBillingAttachmentProcessingStatus::ValidationFailed,
            EBillingAttachmentProcessingStatus::ValidatorError,
        ], true);
    }

    /**
     * True when this PDF attachment has left the async pipeline (mail-inbox terminal on attachment).
     */
    private function pdfPipelineComplete(InboxAttachment $attachment): bool
    {
        return in_array($attachment->processing_status, [
            InboxAttachmentProcessingStatus::Processed->value,
            InboxAttachmentProcessingStatus::Skipped->value,
        ], true);
    }

    private function gatewayStatus(InboxAttachment $attachment): ?EBillingAttachmentProcessingStatus
    {
        return EbillingDocument::forSourceAttachment($attachment)?->gateway_status;
    }

    private function settleMessage(InboxMessage $message, bool $success): void
    {
        $externalId = $message->external_id;
        if ($externalId === null || $externalId === '') {
            return;
        }

        $outcome = $success ? SettlementOutcome::Processed : SettlementOutcome::Failed;
        $mailbox = (string) ($message->scope ?? 'default');

        try {
            $this->drivers->mailbox($mailbox)->settle($externalId, $outcome);
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[EBilling] Settlement failed after XML pipeline finalization', [
                'exception' => $e,
                'external_id' => $externalId,
                'inbox_message_id' => $message->id,
                'outcome' => $outcome->value,
                'scope' => $mailbox,
            ]);
        }
    }
}
