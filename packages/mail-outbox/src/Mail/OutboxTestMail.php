<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Minimal probe mailable for verifying outbound delivery through the send
 * pipeline and a chosen transport. Transport-agnostic — the mailer is selected
 * when dispatching, not here.
 */
class OutboxTestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $recipient,
        public bool $testMode,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->recipient)],
            subject: 'Moox Mail Outbox – send test',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: sprintf(
                '<p>Outbound send test through Moox Mail Outbox.</p>'
                .'<p>Intended recipient: <strong>%s</strong><br>'
                .'Test mode: <strong>%s</strong><br>'
                .'Timestamp: %s</p>',
                e($this->recipient),
                $this->testMode ? 'on' : 'off',
                now()->toDateTimeString(),
            ),
        );
    }
}
