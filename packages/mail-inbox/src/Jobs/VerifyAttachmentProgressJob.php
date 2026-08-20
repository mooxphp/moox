<?php

declare(strict_types=1);

namespace Moox\MailInbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\InboxDriverManager;
use Moox\MailInbox\Models\InboxAttachment;

class VerifyAttachmentProgressJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const STALLED_ATTACHMENT_ERROR = 'Inbox PDF was not processed (e.g. moox/e-billing listener not registered).';

    public function __construct(
        public int $attachmentId,
    ) {
    }

    public function handle(InboxDriverManager $drivers): void
    {
        $attachment = InboxAttachment::query()->with('message')->find($this->attachmentId);
        if ($attachment === null) {
            return;
        }

        if ($attachment->processing_status === InboxAttachmentProcessingStatus::Processing->value) {
            Log::channel('mail-inbox')->warning('[MailInbox] PDF still in processing after InboxAttachmentProcessed (no listener handled it)', [
                'inbox_attachment_id' => $attachment->id,
                'listener_timeout_minutes' => (int) config('mail-inbox.listener_timeout_minutes', 5),
            ]);

            $attachment->markAsFailed(self::STALLED_ATTACHMENT_ERROR);
            $attachment->refresh();
        }

        if ($attachment->processing_status !== InboxAttachmentProcessingStatus::Failed->value
            || $attachment->error_message !== self::STALLED_ATTACHMENT_ERROR
        ) {
            return;
        }

        $externalId = $attachment->message?->external_id;
        if ($externalId === null || $externalId === '') {
            return;
        }

        $scope = (string) ($attachment->scope ?? $attachment->message?->scope ?? 'default');
        $drivers->mailbox($scope)->settle($externalId, SettlementOutcome::Ignored);
    }
}
