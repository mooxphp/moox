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

        return array_merge([
            'template' => $template,
            'logoUrl' => $template->logo_url,
            'brandName' => $brandName,
            'headline' => $data['headline'] ?? $brandName,
            'mailContent' => $template->mail_content,
            'footer' => $template->footer,
        ], $data);
    }
}
