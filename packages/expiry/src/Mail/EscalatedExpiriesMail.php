<?php

namespace Moox\Expiry\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EscalatedExpiriesMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $entries;

    public string $panelPath;

    public function __construct(array $entries, string $panelPath)
    {
        $this->entries = $entries;
        $this->panelPath = $panelPath;
    }

    public function build()
    {
        $logoPath = config('expiry.logo_url');
        $logoUrl = asset($logoPath);

        return $this->subject(__('core::expiry.escalated_entries_in_expiry_dashboard'))
            ->view('expiry::emails.escalated_expiries')
            ->with([
                'escalatedEntries' => $this->entries['escalatedEntries'],
                'panelPath' => $this->panelPath,
                'logoUrl' => $logoUrl,
            ]);
    }
}
