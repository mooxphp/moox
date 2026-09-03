<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

use Moox\MailTemplate\Models\MailTemplate;
use Spatie\Mjml\Mjml;

class MailTemplateRenderer
{
    public function find(string $key, ?string $locale = null): ?MailTemplate
    {
        $locale ??= app()->getLocale();

        return MailTemplate::query()
            ->where('key', $key)
            ->where('locale', $locale)
            ->first()
            ?? MailTemplate::query()->where('key', $key)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function toHtml(MailTemplate $template, array $data = []): string
    {
        $rendered = view($template->view, $this->viewData($template, $data))->render();

        if (! $this->isMjml($rendered)) {
            return $rendered;
        }

        return Mjml::new()->toHtml($rendered);
    }

    private function isMjml(string $rendered): bool
    {
        return str_starts_with(mb_strtolower(ltrim($rendered)), '<mjml');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function toMjml(MailTemplate $template, array $data = []): string
    {
        return view($template->view, $this->viewData($template, $data))->render();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function viewData(MailTemplate $template, array $data = []): array
    {
        $brandName = filled($template->brand_name)
            ? $template->brand_name
            : config('app.name');

        $viewData = array_merge([
            'template' => $template,
            'logoUrl' => $template->logo_url,
            'brandName' => $brandName,
            'headline' => $data['headline'] ?? $brandName,
        ], $data);

        $viewData['mailContent'] = $this->interpolate($template->mail_content, $viewData);
        $viewData['footer'] = $this->interpolate($template->footer, $viewData);

        return $viewData;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function interpolate(?string $fragment, array $data): ?string
    {
        if ($fragment === null || $fragment === '') {
            return $fragment;
        }

        return strtr($fragment, $this->tokens($data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function tokens(array $data): array
    {
        $tokens = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && (is_string($value) || is_int($value) || is_float($value))) {
                $tokens['{'.$key.'}'] = e((string) $value);
            }
        }

        $person = $data['subject'] ?? $data['user'] ?? null;
        $displayName = trim((string) data_get($person, 'display_name', ''));

        if ($displayName !== '') {
            $tokens['{displayName}'] = e($displayName);
        } else {
            $tokens['{displayName}'] ??= '';
        }

        $lastName = trim((string) data_get($person, 'last_name', ''));
        $name = trim((string) data_get($person, 'name', ''));

        if ($lastName !== '') {
            $tokens['{lastName}'] = e($lastName);
        }

        if ($name !== '') {
            $tokens['{name}'] = e($name);
        }

        return $tokens;
    }
}
