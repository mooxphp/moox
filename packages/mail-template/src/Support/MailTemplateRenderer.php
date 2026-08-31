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
        $mjml = view($template->view, $this->viewData($template, $data))->render();

        return Mjml::new()->toHtml($mjml);
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
        return array_merge([
            'template' => $template,
            'logoUrl' => $template->logo_url,
            'brandName' => $template->brand_name,
            'headline' => $data['headline'] ?? $template->brand_name,
            'mailContent' => $template->mail_content,
            'footer' => $template->footer,
        ], $data);
    }
}
