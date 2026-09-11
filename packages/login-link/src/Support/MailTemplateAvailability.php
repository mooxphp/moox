<?php

declare(strict_types=1);

namespace Moox\LoginLink\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class MailTemplateAvailability
{
    public const RENDERER_CLASS = 'Moox\\MailTemplate\\Support\\MailTemplateRenderer';

    public const MODEL_CLASS = 'Moox\\MailTemplate\\Models\\MailTemplate';

    public const LAYOUT_MODEL_CLASS = 'Moox\\MailTemplate\\Models\\MailLayout';

    public static function enabled(): bool
    {
        return class_exists(self::RENDERER_CLASS)
            && (bool) config('login-link.mail_template.enabled', true)
            && Schema::hasTable('mail_templates');
    }

    /**
     * @return array<string, string>
     */
    public static function templateOptions(?string $currentSlug = null): array
    {
        $modelClass = self::MODEL_CLASS;

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class) || ! Schema::hasTable('mail_templates')) {
            return filled($currentSlug) ? [$currentSlug => $currentSlug] : [];
        }

        $locale = app()->getLocale();

        $options = $modelClass::query()
            ->with('translations')
            ->orderBy('slug')
            ->get()
            ->mapWithKeys(function (Model $template) use ($locale): array {
                $slug = (string) $template->getAttribute('slug');

                return [$slug => self::titleFromTranslation($template, $locale, $slug)];
            })
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
        $layoutClass = self::LAYOUT_MODEL_CLASS;

        if (! class_exists($layoutClass) || ! is_subclass_of($layoutClass, Model::class) || ! Schema::hasTable('mail_layouts')) {
            return [];
        }

        $locale = app()->getLocale();

        return $layoutClass::query()
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
        return self::templateOptions($slug)[$slug] ?? $slug;
    }

    /**
     * @param  array{slug?: mixed, title?: mixed, mail_layout_id?: mixed}  $data
     */
    public static function createTemplate(array $data): string
    {
        $modelClass = self::MODEL_CLASS;
        $slug = trim((string) ($data['slug'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $layoutId = $data['mail_layout_id'] ?? null;

        if ($slug === '' || $title === '' || $layoutId === null || $layoutId === '') {
            throw ValidationException::withMessages([
                'slug' => __('login-link::translations.template_key_required'),
            ]);
        }

        if (! self::enabled() || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw ValidationException::withMessages([
                'slug' => __('login-link::translations.template_key_required'),
            ]);
        }

        $template = $modelClass::query()->create([
            'slug' => $slug,
            'mail_layout_id' => $layoutId,
        ]);

        $template->translateOrNew(app()->getLocale())->fill([
            'title' => $title,
        ])->save();

        return (string) $template->getAttribute('slug');
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
