<?php

namespace Moox\LoginLink\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $loginLink;

    public function __construct(LoginLink $loginLink)
    {
        $this->loginLink = $loginLink;
    }

    public function build()
    {
        $userId = urlencode(encrypt($this->loginLink->user_id));
        $url = url("/login-link/{$userId}-{$this->loginLink->token}");

        return $this->subject('Your Login Link')
            ->markdown('login-link::emails.loginlink', ['url' => $url]);
    }
}
