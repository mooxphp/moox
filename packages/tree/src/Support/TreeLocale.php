<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class TreeLocale
{
    /**
     * Active tree language from request query, app locale, or config fallback.
     */
    public static function resolveActiveLanguage(string $requestKey = 'lang'): string
    {
        $requestLanguage = trim((string) request()->input($requestKey, ''));
        if ($requestLanguage !== '') {
            return $requestLanguage;
        }

        $appLanguage = trim((string) app()->getLocale());
        if ($appLanguage !== '') {
            return $appLanguage;
        }

        return (string) config('app.locale');
    }

    /**
     * Default tree language from Moox Localization or app config.
     */
    public static function resolveDefaultLocale(): string
    {
        $localizationClass = 'Moox\Localization\Models\Localization';

        if (class_exists($localizationClass)) {
            /** @var class-string<Model> $localizationClass */
            $model = new $localizationClass;

            if (Schema::hasTable($model->getTable())) {
                $defaultLocale = $localizationClass::query()
                    ->where('is_default', true)
                    ->where('is_active_admin', true)
                    ->first();

                if ($defaultLocale !== null) {
                    $variant = $defaultLocale->locale_variant ?? null;
                    $alpha2 = $defaultLocale->language?->alpha2 ?? null;

                    if (filled($variant)) {
                        return (string) $variant;
                    }

                    if (filled($alpha2)) {
                        return (string) $alpha2;
                    }
                }
            }
        }

        return (string) config('app.locale');
    }

    public static function syncToRequest(string $lang): void
    {
        $lang = trim($lang);

        if ($lang !== '') {
            request()->merge(['lang' => $lang]);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function languageChangeParameters(string $lang, ?string $tab = null): array
    {
        $parameters = ['lang' => $lang];

        if ($tab !== null && $tab !== '') {
            $parameters['tab'] = $tab;
        } elseif (filled(request()->query('tab'))) {
            $parameters['tab'] = (string) request()->query('tab');
        }

        return $parameters;
    }

    public static function syncApplicationLocale(string $lang): void
    {
        $lang = trim($lang);

        if ($lang === '') {
            return;
        }

        $normalized = str_replace('-', '_', $lang);
        $baseLanguage = strtolower(explode('_', $normalized, 2)[0] ?: $normalized);
        $resolvedLocale = $normalized !== '' ? $normalized : $baseLanguage;

        if ($resolvedLocale === '') {
            return;
        }

        app()->setLocale($resolvedLocale);
    }

    /**
     * Locale strings to match translated records (variants, base language, optional Moox admin locales).
     *
     * @return array<int, string>
     */
    public static function localeCandidates(string $lang): array
    {
        $lang = trim($lang);

        if ($lang === '') {
            return [];
        }

        $normalized = str_replace('-', '_', $lang);
        $hyphenated = str_replace('_', '-', $normalized);
        $candidates = array_values(array_filter([$lang, $normalized, $hyphenated], fn (string $value): bool => $value !== ''));

        if (str_contains($normalized, '_')) {
            $baseLanguage = explode('_', $normalized, 2)[0];

            if ($baseLanguage !== '') {
                $candidates[] = $baseLanguage;
            }
        } else {
            $baseLanguage = $normalized;

            if ($baseLanguage !== '') {
                $candidates = [...$candidates, ...self::adminLocaleVariantsForBaseLanguage($baseLanguage)];
            }
        }

        $normalizedCandidates = [];

        foreach ($candidates as $candidate) {
            $trimmed = trim($candidate);
            if ($trimmed === '') {
                continue;
            }

            $underscored = str_replace('-', '_', $trimmed);
            $hyphenatedCandidate = str_replace('_', '-', $underscored);

            $normalizedCandidates[] = $trimmed;
            $normalizedCandidates[] = $underscored;
            $normalizedCandidates[] = $hyphenatedCandidate;
        }

        return array_values(array_unique($normalizedCandidates));
    }

    /**
     * @return array<int, string>
     */
    public static function adminLocaleVariantsForBaseLanguage(string $baseLanguage): array
    {
        $baseLanguage = trim($baseLanguage);

        if ($baseLanguage === '') {
            return [];
        }

        $localizationClass = 'Moox\Localization\Models\Localization';

        if (! class_exists($localizationClass)) {
            return [];
        }

        /** @var class-string<Model> $localizationClass */
        $model = new $localizationClass;

        if (! Schema::hasTable($model->getTable())) {
            return [];
        }

        /** @var class-string<Model> $localizationClass */
        return $localizationClass::query()
            ->where('is_active_admin', true)
            ->whereHas('language', fn (Builder $languageQuery): Builder => $languageQuery->where('alpha2', $baseLanguage))
            ->pluck('locale_variant')
            ->filter()
            ->map(fn ($value): string => (string) $value)
            ->all();
    }
}
