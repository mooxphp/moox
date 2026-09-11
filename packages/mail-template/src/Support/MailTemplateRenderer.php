<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailLayoutTranslation;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Models\MailTemplateTranslation;
use Spatie\Mjml\Mjml;

class MailTemplateRenderer
{
    public function __construct(
        private MjmlDocumentComposer $composer = new MjmlDocumentComposer,
    ) {
    }

    public function find(string $slug, ?string $locale = null): ?MailTemplate
    {
        $locale ??= app()->getLocale();

        $template = MailTemplate::query()
            ->with(['translations', 'mailLayout.translations'])
            ->where('slug', $slug)
            ->first();

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
        $rendered = $this->toMjml($template, $data);

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
        return $this->composer->compose($this->viewData($template, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function viewData(MailTemplate $template, array $data = []): array
    {
        $template->loadMissing(['translations', 'mailLayout.translations']);

        $translation = $this->translationFor($template);
        $layoutTranslation = $this->layoutTranslationFor($template);
        $brandName = config('app.name');

        $footer = filled($translation?->footer)
            ? $translation->footer
            : $layoutTranslation?->footer;

        $layout = $template->mailLayout;

        $merged = array_merge([
            'template' => $template,
            'logoUrl' => $template->logo_url ?? $layout?->logo_url,
            'brandName' => $brandName,
            'headline' => $data['headline'] ?? $brandName,
            'mailContent' => $translation?->mail_content,
            'footer' => $footer,
            'backgroundColor' => $this->color($layout?->background_color, '#ECF2F6'),
            'buttonColor' => $this->color($layout?->button_color, '#005CA3'),
            'textColor' => $this->color($layout?->text_color, '#000000'),
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

    public function applyLocale(BaseDraftModel $record, string $locale): void
    {
        $record->setDefaultLocale($this->resolveTranslationLocale($record, $locale));
    }

    private function resolveTranslationLocale(BaseDraftModel $record, string $locale): string
    {
        if ($record->hasTranslation($locale)) {
            return $locale;
        }

        foreach ($record->translations as $translation) {
            $code = $this->localeOf($translation);

            if ($code !== '' && strcasecmp($code, $locale) === 0) {
                return $code;
            }
        }

        $language = strtolower(explode('_', $locale)[0]);

        foreach ($record->translations as $translation) {
            $code = $this->localeOf($translation);
            $codeLanguage = strtolower(explode('_', $code)[0]);

            if ($language !== '' && $codeLanguage === $language) {
                return $code;
            }
        }

        $firstLocale = $this->localeOf($record->translations()->first());

        return $firstLocale !== '' ? $firstLocale : $locale;
    }

    private function localeOf(mixed $translation): string
    {
        if (! $translation instanceof Model) {
            return '';
        }

        $locale = $translation->getAttribute('locale');

        return is_string($locale) ? $locale : '';
    }

    private function translationFor(MailTemplate $template): ?MailTemplateTranslation
    {
        $locale = $template->getDefaultLocale() ?: app()->getLocale();

        $translation = $template->translate($locale, true)
            ?? $template->translations()->first();

        return $translation instanceof MailTemplateTranslation ? $translation : null;
    }

    private function layoutTranslationFor(MailTemplate $template): ?MailLayoutTranslation
    {
        $layout = $template->mailLayout;

        if (! $layout instanceof MailLayout) {
            return null;
        }

        $this->applyLocale($layout, $template->getDefaultLocale() ?: app()->getLocale());

        $locale = $layout->getDefaultLocale() ?: app()->getLocale();
        $translation = $layout->translate($locale, true)
            ?? $layout->translations()->first();

        return $translation instanceof MailLayoutTranslation ? $translation : null;
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

    private function color(mixed $value, string $fallback): string
    {
        $color = is_string($value) ? trim($value) : '';

        if ($color === '' || ! preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
            return $fallback;
        }

        return strtoupper($color);
    }

    private function isMjml(string $rendered): bool
    {
        return str_starts_with(mb_strtolower(ltrim($rendered)), '<mjml');
    }
}
