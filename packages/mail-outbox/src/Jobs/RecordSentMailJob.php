<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Support\MailOutboxConfig;
use Moox\MailOutbox\Support\RecordedSentMailSnapshot;
use Throwable;

class RecordSentMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;

    public function __construct(
        public ?RecordedSentMailSnapshot $snapshot,
    ) {
    }

    public function tries(): int
    {
        return 1;
    }

    public function handle(MailOutboxConfig $config): void
    {
        $this->setProgress(0);

        if (! $config->shouldRecordForeignMail()) {
            $this->completeProgress();

            return;
        }

        $snapshot = $this->snapshot;

        if ($snapshot === null || ! $snapshot->isRecordable()) {
            $this->completeProgress();

            return;
        }

        if ($this->matchesExistingLog($snapshot)) {
            $this->completeProgress();

            return;
        }

        try {
            MailSendLog::query()->create($snapshot->toLogAttributes());
        } catch (UniqueConstraintViolationException) {
            $this->completeProgress();

            return;
        }

        $this->completeProgress();
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::warning('RecordSentMailJob failed to record foreign mail.', [
            'exception' => $exception?->getMessage(),
            'mailer' => $this->snapshot?->mailer,
        ]);
    }

    private function matchesExistingLog(RecordedSentMailSnapshot $snapshot): bool
    {
        if ($snapshot->correlationId === null && $snapshot->messageId === null) {
            return false;
        }

        return MailSendLog::query()
            ->matchingIdentifiers($snapshot->correlationId, $snapshot->messageId)
            ->exists();
    }

    private function completeProgress(): void
    {
        $this->setProgress(100);
    }
}
