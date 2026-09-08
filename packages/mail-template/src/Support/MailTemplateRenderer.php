<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

use Moox\MailTemplate\Models\MailTemplate;
use Spatie\Mjml\Mjml;

class MailTemplateRenderer
{
    public function find(string $slug, ?string $locale = null): ?MailTemplate
    {
        $locale ??= app()->getLocale();

        $template = MailTemplate::query()->where('slug', $slug)->first();

        if ($template === null) {
            return null;
        }

        $this->applyLocale($template, $locale);

        return $template;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function toHtml(MailTemplate $template, array $data = []): string
    {
        $rendered = view($template->layout, $this->viewData($template, $data))->render();

        if (! $this->isMjml($rendered)) {
            return $rendered;
        }

        return Mjml::new()->toHtml($rendered);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function toMjml(MailTemplate $template, array $data = []): string
    {
        return view($template->layout, $this->viewData($template, $data))->render();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function viewData(MailTemplate $template, array $data = []): array
    {
        $translation = $this->translationFor($template);
        $brandName = config('app.name');

        $merged = array_merge([
            'template' => $template,
            'logoUrl' => $template->logo_url,
            'brandName' => $brandName,
            'headline' => $data['headline'] ?? $brandName,
            'mailContent' => $translation?->mail_content,
            'footer' => $translation?->footer,
        ], $data);

        $merged['mailContent'] = $this->interpolate(
            is_string($merged['mailContent'] ?? null) ? $merged['mailContent'] : null,
            $merged,
        );
        $merged['footer'] = $this->interpolate(
            is_string($merged['footer'] ?? null) ? $merged['footer'] : null,
            $merged,
        );

        return $merged;
    }

    public function applyLocale(MailTemplate $template, string $locale): void
    {
        $template->setDefaultLocale($this->resolveTranslationLocale($template, $locale));
    }

    private function resolveTranslationLocale(MailTemplate $template, string $locale): string
    {
        if ($template->hasTranslation($locale)) {
            return $locale;
        }

        foreach ($template->translations as $translation) {
            $code = (string) $translation->locale;

            if (strcasecmp($code, $locale) === 0) {
                return $code;
            }
        }

        $language = strtolower(explode('_', $locale)[0]);

        foreach ($template->translations as $translation) {
            $code = (string) $translation->locale;
            $codeLanguage = strtolower(explode('_', $code)[0]);

            if ($language !== '' && $codeLanguage === $language) {
                return $code;
            }
        }

        return $template->translations()->first()?->locale ?? $locale;
    }

    private function translationFor(MailTemplate $template): mixed
    {
        $locale = $template->getDefaultLocale() ?: app()->getLocale();

        return $template->translate($locale, true)
            ?? $template->translations()->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function interpolate(?string $content, array $data): ?string
    {
        if ($content === null || $content === '') {
            return $content;
        }

        $replaced = preg_replace_callback(
            '/\{([A-Za-z_][A-Za-z0-9_.]*)\}/',
            function (array $matches) use ($data): string {
                $token = $matches[1];
                $paths = match ($token) {
                    'displayName' => ['displayName', 'user.display_name', 'subject.display_name', 'user.name'],
                    'lastName' => ['lastName', 'user.last_name'],
                    default => [$token],
                };

                foreach ($paths as $path) {
                    $value = data_get($data, $path);

                    if (is_scalar($value) && (string) $value !== '') {
                        return (string) $value;
                    }
                }

                return $matches[0];
            },
            $content,
        );

        return is_string($replaced) ? $replaced : $content;
    }

    private function isMjml(string $rendered): bool
    {
        return str_starts_with(mb_strtolower(ltrim($rendered)), '<mjml');
    }
}
