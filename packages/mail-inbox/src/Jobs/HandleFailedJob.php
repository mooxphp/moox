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
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Services\GraphMailService;
use Throwable;

class HandleFailedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $maxExceptions = 1;

    public function __construct(
        public ?int $inboxMessageId,
        public string $errorMessage = '',
    ) {}

    public function handle(GraphMailService $graph): void
    {
        $this->setProgress(0);

        if ($this->inboxMessageId === null) {
            $this->setProgress(100);

            return;
        }

        $message = InboxMessage::query()->find($this->inboxMessageId);

        if ($message === null) {
            Log::channel('mail-inbox')->warning('[MailInbox] HandleFailedJob: inbox message not found', [
                'inbox_message_id' => $this->inboxMessageId,
            ]);
            $this->setProgress(100);

            return;
        }

        $errorText = $this->errorMessage !== '' ? $this->errorMessage : 'Job failed';

        if ($message->hasAttachmentsPendingOrProcessing()) {
            $message->markAsPartiallyFailed($errorText);
            Log::channel('mail-inbox')->warning('[MailInbox] HandleFailedJob: attachments still pending or processing; message marked partially_failed', [
                'inbox_message_id' => $message->id,
                'error' => $errorText,
            ]);
            $this->setProgress(100);

            return;
        }

        $externalId = $message->external_id;
        if ($externalId !== null && $externalId !== '') {
            try {
                $graph->moveGraphMessageToProcessedOrFailedFolder($externalId, false, $message->scope ?? 'default');
            } catch (Throwable $e) {
                Log::channel('mail-inbox')->error('[MailInbox] HandleFailedJob: could not move message to Failed folder', [
                    'exception' => $e,
                    'inbox_message_id' => $message->id,
                    'external_id' => $externalId,
                ]);
            }
        }

        $message->markAsFailed($errorText);

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::channel('mail-inbox')->error('[MailInbox] HandleFailedJob itself failed', [
            'exception' => $exception,
            'inbox_message_id' => $this->inboxMessageId,
        ]);
    }
}
