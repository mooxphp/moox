<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Moox\MailOutbox\Enums\MailSendSource;
use Moox\MailOutbox\Enums\MailSendStatus;

/**
 * Immutable send-log fields for {@see RecordSentMailJob}.
 *
 * Built before the job is queued so the queue payload never carries live MIME.
 */
final readonly class RecordedSentMailSnapshot
{
    /**
     * @param  list<string>|null  $recipients
     */
    public function __construct(
        public string $mailer,
        public ?array $recipients,
        public ?string $subject,
        public ?string $messageId,
        public ?string $correlationId,
    ) {
    }

    /**
     * A framework send is recordable when it carries identifiers and/or visible payload.
     * SendMailJob always stamps a correlation id, so outbox sends still dedupe safely.
     */
    public function isRecordable(): bool
    {
        return $this->correlationId !== null
            || $this->messageId !== null
            || $this->recipients !== null
            || $this->subject !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogAttributes(): array
    {
        return [
            'mailer' => $this->mailer,
            'source' => MailSendSource::Recorded,
            'intended_recipients' => $this->recipients,
            'actual_recipients' => $this->recipients,
            'subject' => $this->subject,
            'status' => MailSendStatus::Sent,
            'attempt_count' => 1,
            'error' => null,
            'message_id' => $this->messageId,
            'provider_reference' => null,
            'correlation_id' => $this->correlationId,
        ];
    }
}
