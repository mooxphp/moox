<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class BlockEditorLocale
{
    public static function resolveActive(?Request $request = null): string
    {
        $request ??= request();

        $lang = trim((string) ($request->query('lang') ?? $request->input('lang') ?? ''));
        if ($lang !== '') {
            return self::resolveTranslationLocale($lang);
        }

        $appLocale = trim((string) app()->getLocale());
        if ($appLocale !== '') {
            return self::resolveTranslationLocale($appLocale);
        }

        return self::resolveTranslationLocale((string) config('app.locale'));
    }

    /**
     * Map short locales (e.g. de, en) to stored translation locales (e.g. de_DE, en_US).
     */
    public static function resolveTranslationLocale(string $locale): string
    {
        $locale = trim(str_replace('-', '_', $locale));

        if ($locale === '') {
            return self::resolveDefaultLocaleVariant();
        }

        if (str_contains($locale, '_')) {
            return $locale;
        }

        $variants = self::localeVariantsForBaseLanguage($locale);

        if ($variants !== []) {
            return $variants[0];
        }

        $conventional = self::conventionalLocaleVariant($locale);

        return $conventional ?? $locale;
    }

    /**
     * Locale strings to match translated records (variants, base language, Moox admin locales).
     *
     * @return list<string>
     */
    public static function localeCandidates(string $locale): array
    {
        $locale = trim(str_replace('-', '_', $locale));

        if ($locale === '') {
            return [];
        }

        $hyphenated = str_replace('_', '-', $locale);
        $candidates = array_values(array_filter([$locale, $hyphenated], static fn (string $value): bool => $value !== ''));

        if (str_contains($locale, '_')) {
            $baseLanguage = explode('_', $locale, 2)[0];

            if ($baseLanguage !== '') {
                $candidates[] = $baseLanguage;
            }
        } else {
            $candidates = [...$candidates, ...self::localeVariantsForBaseLanguage($locale)];

            $conventional = self::conventionalLocaleVariant($locale);
            if ($conventional !== null) {
                $candidates[] = $conventional;
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

    public static function resolveDefaultLocaleVariant(): string
    {
        $localizationClass = 'Moox\Localization\Models\Localization';

        if (class_exists($localizationClass)) {
            /** @var class-string<Model> $localizationClass */
            $model = new $localizationClass;

            if (Schema::hasTable($model->getTable())) {
                $defaultLocale = $localizationClass::query()
                    ->where('is_default', true)
                    ->first();

                if ($defaultLocale !== null && filled($defaultLocale->locale_variant)) {
                    return (string) $defaultLocale->locale_variant;
                }
            }
        }

        return self::resolveTranslationLocale((string) config('app.locale'));
    }

    /**
     * @return list<string>
     */
    private static function localeVariantsForBaseLanguage(string $baseLanguage): array
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
            ->whereHas('language', fn (Builder $languageQuery): Builder => $languageQuery->where('alpha2', $baseLanguage))
            ->orderByDesc('is_default')
            ->orderByDesc('is_active_admin')
            ->pluck('locale_variant')
            ->filter()
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    private static function conventionalLocaleVariant(string $baseLanguage): ?string
    {
        $baseLanguage = strtolower(trim($baseLanguage));

        if ($baseLanguage === '') {
            return null;
        }

        return match ($baseLanguage) {
            'en' => 'en_US',
            default => $baseLanguage.'_'.strtoupper($baseLanguage),
        };
    }
}
