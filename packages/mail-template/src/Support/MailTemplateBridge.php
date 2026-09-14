<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;

/**
 * Public soft-coupling API for optional consumers (login-link, security, …).
 * Consumers may `class_exists` this class only — no hard composer require.
 */
final class MailTemplateBridge
{
    public static function isAvailable(): bool
    {
        return Schema::hasTable('mail_templates');
    }

    /**
     * @return array<string, string>
     */
    public static function templateOptions(?string $currentSlug = null): array
    {
        if (! self::isAvailable()) {
            return filled($currentSlug) ? [$currentSlug => $currentSlug] : [];
        }

        $options = MailTemplate::query()
            ->orderBy('slug')
            ->pluck('slug', 'slug')
            ->all();

        if (filled($currentSlug) && ! array_key_exists($currentSlug, $options)) {
            $options[$currentSlug] = $currentSlug;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function layoutOptions(): array
    {
        if (! Schema::hasTable('mail_layouts')) {
            return [];
        }

        $locale = app()->getLocale();

        return MailLayout::query()
            ->with('translations')
            ->orderBy('slug')
            ->get()
            ->mapWithKeys(function (Model $layout) use ($locale): array {
                $slug = (string) $layout->getAttribute('slug');

                return [(int) $layout->getKey() => self::titleFromTranslation($layout, $locale, $slug)];
            })
            ->all();
    }

    public static function labelForSlug(string $slug): string
    {
        return $slug;
    }

    /**
     * @param  array{slug?: mixed, title?: mixed, mail_layout_id?: mixed}  $data
     */
    public static function createTemplate(array $data, ?string $defaultMailContent = null): string
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $layoutId = $data['mail_layout_id'] ?? null;

        if ($slug === '' || $title === '' || $layoutId === null || $layoutId === '') {
            throw ValidationException::withMessages([
                'slug' => __('mail-template::translations.bridge_create_invalid'),
            ]);
        }

        if (! self::isAvailable()) {
            throw ValidationException::withMessages([
                'slug' => __('mail-template::translations.bridge_create_invalid'),
            ]);
        }

        $template = MailTemplate::query()->create([
            'slug' => $slug,
            'mail_layout_id' => $layoutId,
        ]);

        $fill = ['title' => $title];

        if (filled($defaultMailContent)) {
            $fill['mail_content'] = $defaultMailContent;
        }

        $template->translateOrNew(app()->getLocale())->fill($fill)->save();

        return (string) $template->getAttribute('slug');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function toHtmlBySlug(string $slug, array $data = [], ?string $locale = null): ?string
    {
        $slug = trim($slug);

        if ($slug === '' || ! self::isAvailable()) {
            return null;
        }

        $renderer = app(MailTemplateRenderer::class);
        $template = $renderer->find($slug, $locale);

        if ($template === null) {
            return null;
        }

        return $renderer->toHtml($template, $data);
    }

    private static function titleFromTranslation(Model $record, string $locale, string $fallback): string
    {
        $title = null;

        if (method_exists($record, 'translate')) {
            $translation = $record->translate($locale, true);

            if (is_object($translation) && isset($translation->title) && filled($translation->title)) {
                $title = (string) $translation->title;
            }
        }

        if (! filled($title) && $record->relationLoaded('translations')) {
            $fallbackTranslation = $record->getRelation('translations')->first();

            if (is_object($fallbackTranslation) && isset($fallbackTranslation->title) && filled($fallbackTranslation->title)) {
                $title = (string) $fallbackTranslation->title;
            }
        }

        return filled($title) ? (string) $title : $fallback;
    }
}
