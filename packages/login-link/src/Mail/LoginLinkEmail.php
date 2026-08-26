<?php

declare(strict_types=1);

namespace Moox\LoginLink\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Models\LoginLink;

/**
 * @deprecated Use ProcessLinkMail. Kept for backwards compatibility.
 *
 * Optional moox/mail-outbox + moox/mjml: when installed, prefers MailTemplate rendering
 * and Spatie MJML→HTML. Without them, falls back to the package Blade view.
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

        $subject = __('login-link::translations.mail_subject');
        $rendererClass = 'Moox\\MailOutbox\\Support\\MailTemplateRenderer';
        $mjmlClass = 'Spatie\\Mjml\\Mjml';

        if (class_exists($rendererClass)) {
            $renderer = app($rendererClass);
            $templateKey = (string) config('login-link.mail_template_key', 'login-link');
            $template = $renderer->find($templateKey);

            if ($template !== null) {
                return $this->subject($subject)->html($renderer->toHtml($template, $data));
            }
        }

        $viewData = array_merge($data, [
            'logoUrl' => $this->resolveLogoUrl(),
            'brandName' => config('app.name'),
            'mailContent' => null,
            'footer' => null,
        ]);

        if (class_exists($mjmlClass)) {
            $mjml = view('login-link::mail.login-link', $viewData)->render();

            return $this->subject($subject)->html($mjmlClass::new()->toHtml($mjml));
        }

        return $this->subject($subject)->view('login-link::mail.login-link', $viewData);
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
