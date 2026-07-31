<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Closure;
use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;

final class MediaLocaleResolver
{
    public function adminDefaultLocale(): string
    {
        if (class_exists(Localization::class) && Schema::hasTable('localizations')) {
            $localization = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->with('language')
                ->first();

            if ($localization) {
                $localeVariant = $localization->getAttribute('locale_variant');
                if (filled($localeVariant)) {
                    return (string) $localeVariant;
                }

                $alpha2 = $localization->language?->alpha2;
                if ($alpha2 === 'en') {
                    return 'en_US';
                }
                if ($alpha2 === 'de') {
                    return 'de_DE';
                }
                if (filled($alpha2)) {
                    return (string) $alpha2;
                }
            }
        }

        $appLocale = (string) config('app.locale');

        return match ($appLocale) {
            'en' => 'en_US',
            'de' => 'de_DE',
            default => $appLocale !== '' ? $appLocale : 'en_US',
        };
    }

    /**
     * @return array<int, string>
     */
    public function fallbackChain(?string $preferredLocale = null, ?string $fallbackLocale = null): array
    {
        $locales = array_filter([
            $preferredLocale,
            $fallbackLocale,
            $this->adminDefaultLocale(),
            (string) config('app.fallback_locale'),
            'en_US',
        ], static fn (?string $value): bool => is_string($value) && trim($value) !== '');

        $expanded = [];
        foreach ($locales as $locale) {
            $expanded[] = $locale;
            $expanded[] = str_replace('-', '_', $locale);
            $expanded[] = str_replace('_', '-', $locale);

            $base = preg_split('/[-_]/', $locale)[0] ?? null;
            if (is_string($base) && $base !== '') {
                $expanded[] = $base;
            }
        }

        return array_values(array_unique(array_filter(
            $expanded,
            static fn (string $value): bool => trim($value) !== '',
        )));
    }

    /**
     * @return array{name: ?string, title: ?string, alt: ?string, description: ?string, internal_note: ?string}
     */
    public function mediaMetadata(Media $media, ?string $preferredLocale = null): array
    {
        $media->loadMissing('translations');

        return [
            'name' => $this->translatedValue($media, 'name', $preferredLocale),
            'title' => $this->translatedValue($media, 'title', $preferredLocale),
            'alt' => $this->translatedValue($media, 'alt', $preferredLocale),
            'description' => $this->translatedValue($media, 'description', $preferredLocale),
            'internal_note' => $this->translatedValue($media, 'internal_note', $preferredLocale),
        ];
    }

    public function collectionName(MediaCollection $collection, ?string $preferredLocale = null): string
    {
        $collection->loadMissing('translations');

        $name = $this->translatedValue($collection, 'name', $preferredLocale);
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        return (string) ($collection->getKey() ?? __('media::fields.uncategorized'));
    }

    public function withLocale(string $locale, Closure $callback): mixed
    {
        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            return $callback();
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    private function translatedValue(Media|MediaCollection $model, string $key, ?string $preferredLocale = null): ?string
    {
        foreach ($this->fallbackChain($preferredLocale ?? app()->getLocale()) as $locale) {
            $translation = $model->translate($locale, false);
            $value = is_object($translation) ? ($translation->{$key} ?? null) : null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        if ($model->relationLoaded('translations')) {
            $first = $model->translations->first();
            $value = $first->{$key} ?? null;
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
