<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\SentMessage as LaravelSentMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailOutbox\Contracts\ProviderMessageIdReader;
use Moox\MailOutbox\Enums\MailSendSource;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Exceptions\MessageTooLargeException;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Support\CorrelationIdGenerator;
use Moox\MailOutbox\Support\MailableInspector;
use Moox\MailOutbox\Support\MailFailureClassifier;
use Moox\MailOutbox\Support\MailOutboxConfig;
use Moox\MailOutbox\Support\MessageSizeGuard;
use Moox\MailOutbox\Support\OutboundMessagePreparer;
use Moox\MailOutbox\Support\TestModeSendCoordinator;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Throwable;

class SendMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public ?int $mailSendLogId = null;

    private bool $outboundMessagePrepared = false;

    public function __construct(
        public Mailable $mailable,
        public string $mailer,
        public ?Model $related = null,
    ) {
    }

    public function tries(): int
    {
        return app(MailOutboxConfig::class)->maxTries();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return app(MailOutboxConfig::class)->backoff();
    }

    public function handle(
        MessageSizeGuard $sizeGuard,
        MailFailureClassifier $classifier,
        MailableInspector $inspector,
        CorrelationIdGenerator $correlationIds,
        ProviderMessageIdReader $providerMessageIdReader,
        MailOutboxConfig $config,
        OutboundMessagePreparer $messagePreparer,
        TestModeSendCoordinator $testModeCoordinator,
    ): void {
        $this->setProgress(0);

        $log = $this->resolveLog($inspector, $correlationIds);
        $attempt = max(1, $this->attempts());

        $log->forceFill([
            'attempt_count' => $attempt,
            'status' => MailSendStatus::Queued,
            'error' => null,
        ])->save();

        $this->setProgress(20);

        try {
            $sizeGuard->assertWithinLimit($this->mailable, $config->maxMessageBytes());
        } catch (MessageTooLargeException $e) {
            $this->failTerminally($log, $attempt, $e);

            return;
        }

        $this->setProgress(40);

        $correlationHeader = $config->correlationHeader();
        $correlationId = $log->correlation_id ?? $correlationIds->mint();

        try {
            if ($config->isTestModeEnabled()) {
                $result = $testModeCoordinator->send(
                    $this->mailable,
                    $this->mailer,
                    $config,
                    $correlationHeader,
                    $correlationId,
                );
                $sent = $result->sent;
                $status = $result->status;
                $actualRecipients = $result->actualRecipients;
            } else {
                $this->prepareOutboundMessageOnce($messagePreparer, $correlationHeader, $correlationId);
                $sent = Mail::mailer($this->mailer)->send($this->mailable);
                $status = MailSendStatus::Sent;
                $actualRecipients = [];
            }
        } catch (Throwable $e) {
            $this->handleSendFailure($log, $attempt, $e, $classifier);

            return;
        }

        $this->setProgress(80);

        $symfonySent = $this->symfonySentMessage($sent);

        $providerReference = null;
        $messageId = null;

        if ($symfonySent instanceof SymfonySentMessage) {
            if ($config->shouldReadBackProviderId($this->mailer)) {
                try {
                    $providerReference = $providerMessageIdReader->read($this->mailer, $symfonySent);
                } catch (Throwable) {
                    $providerReference = null;
                }
            }

            if ($actualRecipients === []) {
                $actualRecipients = $inspector->recipientsFromSent($symfonySent);
            }

            $messageId = $inspector->messageIdFromSent($symfonySent);
        }

        if ($actualRecipients === []) {
            $actualRecipients = $inspector->recipients($this->mailable);
        }

        $log->forceFill([
            'status' => $status,
            'attempt_count' => $attempt,
            'error' => null,
            'actual_recipients' => $actualRecipients,
            'message_id' => $messageId,
            'provider_reference' => $providerReference,
            'correlation_id' => $correlationId,
            'subject' => $inspector->subject($this->mailable) ?? $log->subject,
            'raw_message' => $symfonySent instanceof SymfonySentMessage
                ? $inspector->rawMessageFromSent($symfonySent)
                : $log->raw_message,
        ])->save();

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        if ($this->mailSendLogId === null) {
            return;
        }

        $log = MailSendLog::query()->find($this->mailSendLogId);

        if ($log === null || in_array($log->status, [MailSendStatus::Sent, MailSendStatus::Suppressed], true)) {
            return;
        }

        $log->forceFill([
            'status' => MailSendStatus::Failed,
            'attempt_count' => max(1, $log->attempt_count, $this->attempts()),
            'error' => $exception?->getMessage() ?? $log->error ?? 'SendMailJob failed',
        ])->save();
    }

    private function resolveLog(MailableInspector $inspector, CorrelationIdGenerator $correlationIds): MailSendLog
    {
        if ($this->mailSendLogId !== null) {
            return MailSendLog::query()->findOrFail($this->mailSendLogId);
        }

        $log = MailSendLog::query()->create([
            'mailer' => $this->mailer,
            'source' => MailSendSource::Outbox,
            'intended_recipients' => $inspector->recipients($this->mailable),
            'actual_recipients' => null,
            'subject' => $inspector->subject($this->mailable),
            'status' => MailSendStatus::Queued,
            'attempt_count' => 0,
            'error' => null,
            'message_id' => null,
            'provider_reference' => null,
            'correlation_id' => $correlationIds->mint(),
            'raw_message' => null,
            'resend_payload' => $this->serializeResendPayload($this->mailable),
            'related_type' => $this->related?->getMorphClass(),
            'related_id' => $this->related?->getKey(),
        ]);

        $this->mailSendLogId = $log->id;

        return $log;
    }

    private function handleSendFailure(
        MailSendLog $log,
        int $attempt,
        Throwable $exception,
        MailFailureClassifier $classifier,
    ): void {
        $classification = $classifier->classify($exception);

        if ($classification->isPermanent()) {
            $this->failTerminally($log, $attempt, $exception);

            return;
        }

        $maxTries = $this->tries();

        if ($attempt >= $maxTries) {
            $this->failTerminally($log, $attempt, $exception);

            return;
        }

        $log->forceFill([
            'status' => MailSendStatus::Queued,
            'attempt_count' => $attempt,
            'error' => $exception->getMessage(),
        ])->save();

        $delay = $classification->retryAfterSeconds;

        if ($delay !== null) {
            $this->release(max(1, $delay));

            return;
        }

        throw $exception;
    }

    private function failTerminally(MailSendLog $log, int $attempt, Throwable $exception): void
    {
        $this->markFailed($log, $attempt, $exception->getMessage());
        $this->setProgress(100);
        $this->fail($exception);
    }

    private function markFailed(MailSendLog $log, int $attempt, string $error): void
    {
        $log->forceFill([
            'status' => MailSendStatus::Failed,
            'attempt_count' => $attempt,
            'error' => $error,
        ])->save();
    }

    private function prepareOutboundMessageOnce(
        OutboundMessagePreparer $preparer,
        string $header,
        string $correlationId,
    ): void {
        if ($this->outboundMessagePrepared) {
            return;
        }

        $preparer->prepare($this->mailable, $header, $correlationId, $this->mailer);
        $this->outboundMessagePrepared = true;
    }

    private function symfonySentMessage(mixed $sent): ?SymfonySentMessage
    {
        if ($sent instanceof SymfonySentMessage) {
            return $sent;
        }

        if ($sent instanceof LaravelSentMessage) {
            return $sent->getSymfonySentMessage();
        }

        return null;
    }

    private function serializeResendPayload(Mailable $mailable): string
    {
        return encrypt(serialize($mailable));
    }
}
