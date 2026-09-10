<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Tests\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Mailable that defines recipients only via envelope(), not Mailable property arrays. */
class EnvelopeRecipientMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $toAddresses
     * @param  list<string>  $ccAddresses
     */
    public function __construct(
        private array $toAddresses = [],
        private array $ccAddresses = [],
        public string $mailSubject = 'Envelope recipient test',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            to: array_map(fn (string $email): Address => new Address($email), $this->toAddresses),
            cc: array_map(fn (string $email): Address => new Address($email), $this->ccAddresses),
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Envelope recipients</p>');
    }
}
