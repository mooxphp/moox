<?php

namespace Moox\Training\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Invitation extends Mailable
{
    use Queueable, SerializesModels;

    public $trainingDates;

    public function __construct($trainingDates)
    {
        $this->trainingDates = $trainingDates;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'trainings::emails.invitation',
            with: [
                'trainingDates' => $this->trainingDates,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
