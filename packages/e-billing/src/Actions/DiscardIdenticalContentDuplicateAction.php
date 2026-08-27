<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Support\IdenticalDuplicateNotifier;
use Moox\Invoice\Models\Invoice;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\InboxDriverManager;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use Throwable;

/**
 * Drops a pipeline run that would create a second invoice version for the same
 * number when the source PDF bytes are identical to an already stored document.
 */
final class DiscardIdenticalContentDuplicateAction
{
    public function execute(EbillingDocument $document, Invoice $matchedInvoice): void
    {
        $matchedDocument = EbillingDocument::query()
            ->where('invoice_id', $matchedInvoice->getKey())
            ->orderBy('created_at')
            ->first();

        $ignoredReason = [
            'reason' => 'identical_content_duplicate',
            'matched_invoice_id' => (string) $matchedInvoice->getKey(),
            'matched_document_id' => $matchedDocument instanceof EbillingDocument
                ? (string) $matchedDocument->getKey()
                : null,
            'classified_at' => now()->utc()->toIso8601String(),
        ];

        $attachment = $document->inboxAttachment();

        DB::transaction(function () use ($document, $ignoredReason, $attachment): void {
            $document->ignored_reason = $ignoredReason;
            $document->gateway_status = EBillingAttachmentProcessingStatus::IgnoredIdenticalDuplicate;
            $document->invoice_id = null;
            $document->save();

            if ($attachment instanceof InboxAttachment) {
                $attachment->error_message = null;
                $attachment->markAsSkipped();
            }
        });

        if ($attachment instanceof InboxAttachment) {
            $this->settleInboxMessage($attachment);
        }

        app(IdenticalDuplicateNotifier::class)
            ->notifyIdenticalDuplicateDiscarded($document, $matchedInvoice);

        Log::info('[EBilling] Identical source duplicate discarded', [
            'ebilling_document_id' => $document->getKey(),
            'matched_invoice_id' => $matchedInvoice->getKey(),
        ]);
    }

    private function settleInboxMessage(InboxAttachment $attachment): void
    {
        $message = $attachment->message;

        if (! $message instanceof InboxMessage) {
            return;
        }

        $externalId = $message->external_id;
        if (is_string($externalId) && $externalId !== '') {
            try {
                app(InboxDriverManager::class)
                    ->mailbox((string) ($message->scope ?? 'default'))
                    ->settle($externalId, SettlementOutcome::Ignored);
            } catch (Throwable $e) {
                Log::warning('[EBilling] Could not settle identical-duplicate inbox message', [
                    'exception' => $e,
                    'inbox_message_id' => $message->getKey(),
                ]);
            }
        }

        try {
            app(InboxMessagePipelineFinalizer::class)
                ->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
        } catch (Throwable $e) {
            Log::warning('[EBilling] Identical-duplicate finalizer error', [
                'exception' => $e,
                'inbox_message_id' => $attachment->inbox_message_id,
            ]);
        }
    }
}
