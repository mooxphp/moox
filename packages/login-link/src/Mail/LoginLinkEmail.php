<?php

declare(strict_types=1);

namespace Moox\LoginLink\Mail;

use Moox\LoginLink\Models\LoginLink;
use Moox\MailOutbox\Support\MailTemplateRenderer;
use Spatie\Mjml\Mjml;

/**
 * @deprecated Use ProcessLinkMail. Kept for backwards compatibility.
 */
class LoginLinkEmail extends ProcessLinkMail
{
    use Queueable;
    use SerializesModels;

    public function __construct(public LoginLink $loginLink)
    {
    }

    public function build(): static
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

        $user = $this->loginLink->user()->first();
        $data = [
            'user' => $user,
            'url' => $url,
            'magicLink' => $url,
            'expiresMinutes' => $expiresMinutes,
            'headline' => __('login-link::translations.mail_title'),
        ];

        $renderer = app(MailTemplateRenderer::class);
        $templateKey = (string) config('login-link.mail_template_key', 'login-link');
        $template = $renderer->find($templateKey);

        if ($template !== null) {
            $html = $renderer->toHtml($template, $data);
        } else {
            $mjml = view('login-link::mail.login-link', array_merge($data, [
                'logoUrl' => $this->resolveLogoUrl(),
                'brandName' => config('app.name'),
                'mailContent' => null,
                'footer' => null,
            ]))->render();

            $html = Mjml::new()->toHtml($mjml);
        }

        return $this->subject(__('login-link::translations.mail_subject'))
            ->html($html);
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
