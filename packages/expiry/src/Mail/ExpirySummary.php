<?php

namespace Moox\Expiry\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels; // Wenn Sie eine Eloquent Collection übergeben

class ExpirySummary extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $expiries;

    public function __construct(Collection $expiries)
    {
        $this->expiries = $expiries;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Expiry Weekly',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'expiry:emails.expirySummary',
            with: [
                'expiries' => $this->expiries,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
