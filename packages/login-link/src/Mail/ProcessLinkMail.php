<?php

declare(strict_types=1);

namespace Moox\LoginLink\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;

/**
 * Interim mailable until moox/mail-outbox owns templates/transport.
 * Uses the process definition's content/from when set; otherwise the login blade fallback.
 */
class ProcessLinkMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public LoginLink $loginLink,
        public ?LoginLinkProcess $process = null,
    ) {
    }

    public function build(): static
    {
        $expiresMinutes = $this->process?->resolveExpiryMinutes()
            ?? (int) config('login-link.expiration_minutes', 60);

        $url = $this->signedUrl($expiresMinutes);
        $subjectModel = $this->loginLink->subject()->first() ?? $this->loginLink->user()->first();
        $mailSubject = filled($this->process?->title)
            ? (string) $this->process->title
            : __('login-link::translations.mail_subject');

        $mailable = $this->subject($mailSubject);

        if (filled($this->process?->mail_from)) {
            $mailable->from((string) $this->process->mail_from);
        }

        if (filled($this->process?->content)) {
            return $mailable->view('login-link::mail.process-link', [
                'title' => $this->process->title,
                'content' => $this->process->content,
                'url' => $url,
                'expiresMinutes' => $expiresMinutes,
                'user' => $subjectModel,
                'logoUrl' => $this->resolveLogoUrl(),
            ]);
        }

        return $mailable->view('login-link::mail.login-link', [
            'user' => $subjectModel,
            'url' => $url,
            'expiresMinutes' => $expiresMinutes,
            'logoUrl' => $this->resolveLogoUrl(),
        ]);
    }

    private function signedUrl(int $expiresMinutes): string
    {
        $panelId = (string) $this->loginLink->panel_id;
        $routeName = 'filament.'.$panelId.'.auth.login-link.consume';

        try {
            return URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes($expiresMinutes),
                [
                    'loginLink' => $this->loginLink->getKey(),
                ],
            );
        } catch (\Throwable) {
            return url('/'.$panelId.'/login');
        }
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
