<?php

declare(strict_types=1);

namespace Moox\LoginLink\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Support\LinkProcessContext;

/**
 * Sends the signed URL. When moox/mail-template is installed, the process
 * template_key is looked up as a MailTemplate key (no composer dependency).
 * Otherwise the packaged HTML demo view is used.
 */
class ProcessLinkMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public const DEMO_VIEW = 'login-link::mail.process-link';

    public function __construct(
        public LoginLink $loginLink,
        public ?LoginLinkProcess $process = null,
    ) {
    }

    public function build(): static
    {
        $this->process ??= $this->loginLink->processDefinition();

        $expiresMinutes = $this->process?->resolveExpiryMinutes()
            ?? (int) config('login-link.expiration_minutes', 60);

        $url = $this->signedUrl($expiresMinutes);
        $subjectModel = $this->loginLink->subject ?? $this->loginLink->user;
        $mailSubject = filled($this->process?->title)
            ? (string) $this->process->title
            : __('login-link::translations.mail_subject');

        $mailable = $this->subject($mailSubject)->html($this->renderBody($this->viewData($url, $expiresMinutes, $subjectModel)));

        if (filled($this->process?->mail_from)) {
            $mailable->from((string) $this->process->mail_from);
        }

        return $mailable;
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(string $url, int $expiresMinutes, mixed $subjectModel): array
    {
        $title = filled($this->process?->title)
            ? (string) $this->process->title
            : __('login-link::translations.mail_title');

        return [
            'title' => $title,
            'headline' => $title,
            'content' => $this->process?->content,
            'url' => $url,
            'magicLink' => $url,
            'expiresMinutes' => $expiresMinutes,
            'user' => $subjectModel,
            'subject' => $subjectModel,
            'payload' => $this->loginLink->payload ?? [],
            'cta' => __('login-link::translations.mail_cta'),
            'process' => $this->process,
            'loginLink' => $this->loginLink,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderBody(array $data): string
    {
        $html = $this->renderMailTemplate($data);

        if (is_string($html)) {
            return $html;
        }

        return view(self::DEMO_VIEW, array_merge($data, [
            'logoUrl' => $this->resolveLogoUrl(),
            'brandName' => config('app.name'),
            'mailContent' => null,
            'footer' => null,
        ]))->render();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderMailTemplate(array $data): ?string
    {
        $rendererClass = 'Moox\\MailTemplate\\Support\\MailTemplateRenderer';
        $key = trim((string) ($this->process?->template_key ?? ''));

        if ($key === '' || ! class_exists($rendererClass) || ! Schema::hasTable('mail_templates')) {
            return null;
        }

        $renderer = app($rendererClass);
        $template = $renderer->find($key);

        if ($template === null) {
            return null;
        }

        return $renderer->toHtml($template, $data);
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
