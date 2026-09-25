<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Tests\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $body = 'Hello from mail-outbox',
        public string $mailSubject = 'Mail outbox test',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>'.$this->body.'</p>',
        );
    }
}
