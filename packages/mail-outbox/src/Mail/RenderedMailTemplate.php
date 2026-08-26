<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RenderedMailTemplate extends Mailable
{
    public function __construct(
        public string $htmlBody,
        public string $mailSubject,
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
            htmlString: $this->htmlBody,
        );
    }
}
