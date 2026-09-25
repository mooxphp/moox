<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Moox\Localization\Models\Localization;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailLayoutTranslation;
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

    public static function defaultLocale(): string
    {
        if (class_exists(Localization::class)) {
            $variant = Localization::defaultLocalization()?->locale_variant;

            if (is_string($variant) && trim($variant) !== '') {
                return $variant;
            }
        }

        $configured = (string) config('app.locale');

        return $configured !== '' ? $configured : 'en';
    }

    public static function resolveLocale(): string
    {
        $requestLang = request()->query('lang') ?? request()->input('lang');

        if (is_string($requestLang) && trim($requestLang) !== '') {
            return trim($requestLang);
        }

        return self::defaultLocale();
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

    public static function layoutTitleForLocale(MailLayout $layout, string $locale): ?string
    {
        $translation = $layout->translate($locale, false);
        $title = $translation instanceof MailLayoutTranslation ? $translation->title : null;

        return filled($title) ? (string) $title : null;
    }

    public static function layoutLabel(MailLayout $layout, string $locale): string
    {
        $title = self::layoutTitleForLocale($layout, $locale);

        if (filled($title)) {
            return $title;
        }

        $defaultLocale = self::defaultLocale();

        if ($defaultLocale !== $locale) {
            $fallback = self::layoutTitleForLocale($layout, $defaultLocale);

            if (filled($fallback)) {
                return $fallback.' ('.$defaultLocale.')';
            }
        }

        return $layout->slug;
    }

    /**
     * @return array<int, string>
     */
    public static function layoutOptions(?string $locale = null): array
    {
        if (! Schema::hasTable('mail_layouts')) {
            return [];
        }

        $locale = is_string($locale) && trim($locale) !== ''
            ? trim($locale)
            : self::resolveLocale();

        return MailLayout::query()
            ->with('translations')
            ->get()
            ->sortBy(fn (MailLayout $layout): string => sprintf(
                '%d-%s',
                filled(self::layoutTitleForLocale($layout, $locale)) ? 0 : 1,
                $layout->slug,
            ))
            ->mapWithKeys(function (MailLayout $layout) use ($locale): array {
                return [(int) $layout->getKey() => self::layoutLabel($layout, $locale)];
            })
            ->all();
    }

    public static function labelForSlug(string $slug): string
    {
        return $slug;
    }

    /**
     * @return array<int, TextInput|Select>
     */
    public static function createOptionForm(): array
    {
        return [
            TextInput::make('slug')
                ->label(__('mail-template::translations.slug'))
                ->required()
                ->maxLength(255)
                ->unique(table: 'mail_templates', column: 'slug', ignoreRecord: false),
            TextInput::make('title')
                ->label(__('mail-template::translations.subject'))
                ->required()
                ->maxLength(255),
            Select::make('mail_layout_id')
                ->label(__('mail-template::translations.layout'))
                ->options(fn (): array => self::layoutOptions())
                ->required()
                ->searchable()
                ->native(false),
        ];
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

        $template->translateOrNew(self::defaultLocale())->fill($fill)->save();

        Notification::make()
            ->success()
            ->title(__('mail-template::translations.bridge_created'))
            ->send();

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

    /**
     * Localized template title (Filament “Betreff”) for use as the email subject.
     */
    public static function titleBySlug(string $slug, ?string $locale = null): ?string
    {
        $slug = trim($slug);

        if ($slug === '' || ! self::isAvailable()) {
            return null;
        }

        $template = app(MailTemplateRenderer::class)->find($slug, $locale);

        if ($template === null) {
            return null;
        }

        $translationLocale = method_exists($template, 'getDefaultLocale')
            ? (string) $template->getDefaultLocale()
            : (string) ($locale ?? app()->getLocale());

        $translation = null;

        if (method_exists($template, 'getTranslation')) {
            $translation = $template->getTranslation($translationLocale, false);
        }

        if ($translation === null) {
            $translation = $template->translations->first(
                fn ($row): bool => strcasecmp((string) ($row->locale ?? ''), $translationLocale) === 0
            ) ?? $template->translations->first();
        }

        $title = trim((string) ($translation?->title ?? ''));

        return $title !== '' ? $title : null;
    }
}
