<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Actions;

use Illuminate\Support\Facades\Mail;
use Moox\MailTemplate\Mail\RenderedMailTemplate;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Support\MailSendConfig;
use Moox\MailTemplate\Support\MailTemplateRenderer;
use Throwable;

class SendMailTemplate
{
    public function __construct(
        private MailTemplateRenderer $renderer,
    ) {
    }

    /**
     * @param  list<string>  $emails
     * @return array{sent: list<string>, failed: array<string, string>}
     */
    public function handle(MailTemplate $template, array $emails, string $subject, ?string $locale = null): array
    {
        $locale = $this->resolveLocale($template, $locale);
        $resolved = $this->renderer->find($template->key, $locale) ?? $template;

        $sent = [];
        $failed = [];
        $previousLocale = app()->getLocale();

        app()->setLocale($locale);

        try {
            foreach (MailSendConfig::allowedRecipients($emails) as $recipient) {
                try {
                    $html = $this->renderer->toHtml($resolved, $this->viewDataFor($recipient));

                    Mail::to($recipient['email'])->send(new RenderedMailTemplate($html, $subject));

                    $sent[] = $recipient['email'];
                } catch (Throwable $exception) {
                    $failed[$recipient['email']] = $exception->getMessage();
                }
            }
        } finally {
            app()->setLocale($previousLocale);
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function resolveLocale(MailTemplate $template, ?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));
        $allowed = MailSendConfig::localeOptions();

        if ($locale !== '' && isset($allowed[$locale])) {
            return $locale;
        }

        $fallback = strtolower(trim((string) $template->locale));

        if ($fallback !== '' && isset($allowed[$fallback])) {
            return $fallback;
        }

        return array_key_first($allowed) ?? 'de';
    }

    /**
     * @param  array{name: string, email: string}  $recipient
     * @return array<string, mixed>
     */
    private function viewDataFor(array $recipient): array
    {
        $data = MailSendConfig::viewData();
        $name = $recipient['name'] !== '' ? $recipient['name'] : $recipient['email'];

        $data['user'] = array_merge(
            is_array($data['user'] ?? null) ? $data['user'] : [],
            [
                'name' => $name,
                'last_name' => $this->lastName($name),
            ],
        );

        return $data;
    }

    private function lastName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if ($parts === []) {
            return $name;
        }

        return (string) end($parts);
    }
}
