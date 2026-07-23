<?php

declare(strict_types=1);

namespace Moox\MailInbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Events\InboxAttachmentProcessed;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Services\MailInboxService;
use Throwable;

class ParsePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $maxExceptions = 1;

    public function __construct(
        public int $inboxAttachmentId,
    ) {
    }

    public function handle(MailInboxService $inbox): void
    {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::channel('mail-inbox')->warning('[MailInbox] ParsePdfJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $message = $attachment->message;

        if ($message === null) {
            Log::channel('mail-inbox')->error('[MailInbox] ParsePdfJob: attachment has no message', [
                'inbox_attachment_id' => $attachment->id,
            ]);
            $this->setProgress(100);

            return;
        }

        if (! $attachment->isPdf() || $attachment->processing_status !== InboxAttachmentProcessingStatus::New->value) {
            $this->setProgress(100);

            return;
        }

        $attachment->markAsProcessing();
        $this->setProgress(20);

        $freshAttachment = $attachment->fresh();
        $freshMessage = $message->fresh(['attachments']);

        if ($freshAttachment !== null && $freshMessage !== null) {
            event(new InboxAttachmentProcessed($freshMessage, $freshAttachment));
        }

        VerifyAttachmentProgressJob::dispatch($this->inboxAttachmentId)
            ->delay(now()->addMinutes((int) config('mail-inbox.listener_timeout_minutes', 5)));

        $this->setProgress(80);

        $finalMessage = InboxAttachment::query()->find($this->inboxAttachmentId)?->message;
        if ($finalMessage !== null) {
            $inbox->finalizeMessageProcessingAfterAttachments($finalMessage->fresh(['attachments']));
        }

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);
        $messageId = $attachment?->inbox_message_id;

        try {
            HandleFailedJob::dispatchSync(
                $messageId,
                $exception?->getMessage() ?? 'ParsePdfJob failed'
            );
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] HandleFailedJob also failed', [
                'inbox_message_id' => $messageId,
                'original_error' => $exception?->getMessage(),
                'handler_error' => $e->getMessage(),
            ]);
        }
    }
}
