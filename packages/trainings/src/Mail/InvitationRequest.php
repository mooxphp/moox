<?php

namespace Moox\Training\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $invitationRequest;

    public function __construct($invitationRequest)
    {
        $this->invitationRequest = $invitationRequest;
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
