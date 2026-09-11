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
        $resolved = $this->renderer->find($template->slug, $locale) ?? $template;
        $this->renderer->applyLocale($resolved, $locale);

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
        $allowed = MailSendConfig::localeOptions();
        $match = $this->matchAllowedLocale($allowed, trim((string) $locale));

        if ($match !== null) {
            return $match;
        }

        foreach ($template->translations as $translation) {
            $match = $this->matchAllowedLocale($allowed, trim((string) $translation->getAttribute('locale')));

            if ($match !== null) {
                return $match;
            }
        }

        return array_key_first($allowed) ?? 'de_DE';
    }

    /**
     * @param  array<string, string>  $allowed
     */
    private function matchAllowedLocale(array $allowed, string $locale): ?string
    {
        if ($locale === '') {
            return null;
        }

        if (isset($allowed[$locale])) {
            return $locale;
        }

        foreach (array_keys($allowed) as $code) {
            if (strcasecmp($code, $locale) === 0) {
                return $code;
            }
        }

        return null;
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
