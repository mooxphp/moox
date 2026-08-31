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
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Support\LinkProcessContext;

/**
 * Renders the process template_key via config('login-link.templates').
 * Domain packages contribute views in config; this package stays domain-agnostic.
 *
 * When the host has moox/mail-outbox, a matching MailTemplate is preferred
 * (process template_key / slug, then login-link.mail_template_key for login).
 * MJML views are compiled with Spatie when that package is installed; HTML
 * process templates are sent as rendered Blade.
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
        $this->process ??= $this->loginLink->processDefinition();

        $expiresMinutes = $this->process?->resolveExpiryMinutes()
            ?? (int) config('login-link.expiration_minutes', 60);

        $url = $this->signedUrl($expiresMinutes);
        $subjectModel = $this->loginLink->subject ?? $this->loginLink->user;
        $mailSubject = filled($this->process?->title)
            ? (string) $this->process->title
            : __('login-link::translations.mail_subject');

        $view = $this->process?->resolveTemplateView()
            ?? (string) config('login-link.templates.login', 'login-link::mail.login-link');

        $mailable = $this->subject($mailSubject)->html($this->renderBody($view, $this->viewData($url, $expiresMinutes, $subjectModel)));

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
    private function renderBody(string $view, array $data): string
    {
        $rendererClass = 'Moox\\MailOutbox\\Support\\MailTemplateRenderer';
        $mjmlClass = 'Spatie\\Mjml\\Mjml';

        if (class_exists($rendererClass) && Schema::hasTable('mail_templates')) {
            $renderer = app($rendererClass);

            foreach ($this->mailTemplateKeys() as $key) {
                $template = $renderer->find($key);

                if ($template !== null) {
                    return $renderer->toHtml($template, $data);
                }
            }
        }

        $viewData = array_merge($data, [
            'logoUrl' => $this->resolveLogoUrl(),
            'brandName' => config('app.name'),
            'mailContent' => null,
            'footer' => null,
        ]);

        $rendered = view($view, $viewData)->render();

        if (class_exists($mjmlClass) && $this->isMjml($rendered)) {
            return $mjmlClass::new()->toHtml($rendered);
        }

        return $rendered;
    }

    /**
     * @return list<string>
     */
    private function mailTemplateKeys(): array
    {
        $keys = [];

        $templateKey = trim((string) ($this->process?->template_key ?? ''));

        if ($templateKey !== '') {
            $keys[] = $templateKey;
        }

        $slug = trim((string) ($this->process?->slug ?: $this->loginLink->process ?: ''));

        if ($slug !== '' && ! in_array($slug, $keys, true)) {
            $keys[] = $slug;
        }

        if ($this->isLoginProcess()) {
            $configured = trim((string) config('login-link.mail_template_key', 'login-link'));

            if ($configured !== '' && ! in_array($configured, $keys, true)) {
                $keys[] = $configured;
            }
        }

        return $keys;
    }

    private function isLoginProcess(): bool
    {
        $slug = (string) ($this->process?->slug ?: $this->loginLink->process ?: '');
        $templateKey = (string) ($this->process?->template_key ?: '');

        if ($templateKey === 'login') {
            return true;
        }

        return $slug === '' || $slug === RedemptionHandlerRegistry::DEFAULT_PROCESS;
    }

    private function isMjml(string $rendered): bool
    {
        return str_starts_with(mb_strtolower(ltrim($rendered)), '<mjml');
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
