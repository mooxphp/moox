<?php

namespace Moox\LoginLink\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkEmail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @var LoginLink
     */
    public $loginLink;

    public function __construct(LoginLink $loginLink)
    {
        $this->loginLink = $loginLink;
    }

    public function build()
    {
        $expiresMinutes = (int) config('login-link.expiration_minutes', 60);

        $panelId = (string) $this->loginLink->panel_id;
        $routeName = 'filament.'.$panelId.'.auth.login-link.consume';

        try {
            $url = URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes($expiresMinutes),
                [
                    'loginLink' => $this->loginLink->getKey(),
                ],
            );
        } catch (\Throwable) {
            $url = url('/'.$panelId.'/login');
        }

        return $this->subject(__('login-link::translations.mail_subject'))
            ->view('login-link::mail.login-link', [
                'user' => $this->loginLink->user()->first(),
                'url' => $url,
                'expiresMinutes' => $expiresMinutes,
                'logoUrl' => $this->resolveLogoUrl(),
            ]);
    }

    private function resolveLogoUrl(): ?string
    {
        $configuredUrl = config('login-link.mail_logo_url');

        if (! filled($configuredUrl)) {
            return null;
        }

        $configuredUrl = (string) $configuredUrl;

        if (str_starts_with($configuredUrl, 'http://') || str_starts_with($configuredUrl, 'https://')) {
            return $configuredUrl;
        }

        $publicPath = str_starts_with($configuredUrl, '/')
            ? public_path(ltrim($configuredUrl, '/'))
            : public_path($configuredUrl);

        if (! is_file($publicPath)) {
            return null;
        }

        return str_starts_with($configuredUrl, '/')
            ? url($configuredUrl)
            : url('/'.ltrim($configuredUrl, '/'));
    }
}
