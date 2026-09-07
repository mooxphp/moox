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

        return $this->canonicalLocale($appLocale !== '' ? $appLocale : 'en_US');
    }

    /**
     * Locale selected in the Filament language switcher (?lang=), falling back to the admin default.
     */
    public function currentLocale(?string $preferredLocale = null): string
    {
        $candidates = [
            $preferredLocale,
            request()->query('lang'),
            request()->input('lang'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $this->canonicalLocale($candidate);
            }
        }

        return $this->adminDefaultLocale();
    }

    /**
     * @return array<int, string>
     */
    public function localeVariants(string $locale): array
    {
        $locale = trim($locale);
        if ($locale === '') {
            return [];
        }

        $normalized = str_replace('-', '_', $locale);
        $dashed = str_replace('_', '-', $locale);
        $base = preg_split('/[-_]/', $locale)[0] ?? $locale;
        $canonical = $this->canonicalLocale($locale);

        return array_values(array_unique(array_filter(
            [$locale, $normalized, $dashed, $base, $canonical],
            static fn (string $value): bool => trim($value) !== '',
        )));
    }

    public function canonicalLocale(string $locale): string
    {
        $normalized = str_replace('-', '_', trim($locale));

        return match ($normalized) {
            'en' => 'en_US',
            'de' => 'de_DE',
            default => $normalized !== '' ? $normalized : $this->adminDefaultLocale(),
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
            foreach ($this->localeVariants($locale) as $variant) {
                $expanded[] = $variant;
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @return array{name: ?string, title: ?string, alt: ?string, description: ?string, internal_note: ?string}
     */
    public function mediaMetadata(Media $media, ?string $preferredLocale = null, bool $fallbackToOtherLocales = true): array
    {
        $media->loadMissing('translations');
        $preferred = $preferredLocale ?? $this->currentLocale();

        return [
            'name' => $this->translatedValue($media, 'name', $preferred, $fallbackToOtherLocales),
            'title' => $this->translatedValue($media, 'title', $preferred, $fallbackToOtherLocales),
            'alt' => $this->translatedValue($media, 'alt', $preferred, $fallbackToOtherLocales),
            'description' => $this->translatedValue($media, 'description', $preferred, $fallbackToOtherLocales),
            'internal_note' => $this->translatedValue($media, 'internal_note', $preferred, $fallbackToOtherLocales),
        ];
    }

    public function findTranslation(Media|MediaCollection $model, ?string $preferredLocale = null): mixed
    {
        $model->loadMissing('translations');
        $preferred = $preferredLocale ?? $this->currentLocale();

        foreach ($this->localeVariants($preferred) as $locale) {
            $translation = $model->translations->firstWhere('locale', $locale);
            if ($translation !== null) {
                return $translation;
            }
        }

        return null;
    }

    public function matchingLocale(Media|MediaCollection $model, ?string $preferredLocale = null): ?string
    {
        $translation = $this->findTranslation($model, $preferredLocale);
        $locale = is_object($translation) ? ($translation->locale ?? null) : null;

        return is_string($locale) && $locale !== '' ? $locale : null;
    }

    public function collectionName(MediaCollection $collection, ?string $preferredLocale = null): string
    {
        $collection->loadMissing('translations');

        $name = $this->translatedValue($collection, 'name', $preferredLocale, fallbackToOtherLocales: true);
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

    private function translatedValue(Media|MediaCollection $model, string $key, ?string $preferredLocale = null, bool $fallbackToOtherLocales = true): ?string
    {
        $preferred = $preferredLocale ?? $this->currentLocale();
        $locales = $fallbackToOtherLocales
            ? $this->fallbackChain($preferred)
            : $this->localeVariants($preferred);

        foreach ($locales as $locale) {
            $translation = $model->translate($locale, false);
            $value = is_object($translation) ? ($translation->{$key} ?? null) : null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        if ($fallbackToOtherLocales && $model->relationLoaded('translations')) {
            $first = $model->translations->first();
            $value = $first->{$key} ?? null;
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
