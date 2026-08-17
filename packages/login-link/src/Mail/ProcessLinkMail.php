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
use Moox\LoginLink\Support\LinkProcessContext;

/**
 * Renders the process template_key via config('login-link.templates').
 * Domain packages contribute views in config; this package stays domain-agnostic.
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

        $view = $this->process?->resolveTemplateView()
            ?? (string) config('login-link.templates.login', 'login-link::mail.login-link');

        $mailable = $this->subject($mailSubject)->view($view, [
            'title' => $this->process?->title ?? __('login-link::translations.mail_title'),
            'content' => $this->process?->content,
            'url' => $url,
            'expiresMinutes' => $expiresMinutes,
            'user' => $subjectModel,
            'subject' => $subjectModel,
            'payload' => $this->loginLink->payload ?? [],
            'logoUrl' => $this->resolveLogoUrl(),
            'process' => $this->process,
            'loginLink' => $this->loginLink,
        ]);

        if (filled($this->process?->mail_from)) {
            $mailable->from((string) $this->process->mail_from);
        }

        return $mailable;
    }

    private function signedUrl(int $expiresMinutes): string
    {
        $context = $this->process !== null && LinkProcessContext::isValid((string) $this->process->context)
            ? (string) $this->process->context
            : LinkProcessContext::AUTH;

        if ($context === LinkProcessContext::PUBLIC) {
            try {
                return URL::temporarySignedRoute(
                    'login-link.public.consume',
                    now()->addMinutes($expiresMinutes),
                    [
                        'loginLink' => $this->loginLink->getKey(),
                    ],
                );
            } catch (\Throwable) {
                return url('/');
            }
        }

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
