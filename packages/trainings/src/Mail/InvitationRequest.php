<?php

namespace Moox\Training\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationRequest extends Mailable
{
    use Queueable;
    use SerializesModels;
    public function __construct(public $invitationRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation Request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'trainings::emails.invitation-request',
            with: [
                'invitationId' => $this->invitationRequest,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
