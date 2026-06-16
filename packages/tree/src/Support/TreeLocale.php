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
     * Default locale for tree URLs — matches BaseListDrafts / categories list (`locale_variant`).
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
                    ->first();

                if ($defaultLocale !== null && filled($defaultLocale->locale_variant)) {
                    return (string) $defaultLocale->locale_variant;
                }
            }
        }

        return (string) config('app.locale');
    }

    /**
     * Index URL query parameters with a guaranteed `lang` value (same default as categories list).
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    public static function indexUrlParameters(array $overrides = []): array
    {
        $parameters = self::currentQueryParameters();

        if (! isset($parameters['lang']) || $parameters['lang'] === '') {
            $parameters['lang'] = self::resolveDefaultLocale();
        }

        return array_merge($parameters, $overrides);
    }

    public static function isFullPageRequest(): bool
    {
        return request()->isMethod('GET')
            && ! request()->hasHeader('X-Livewire')
            && ! request()->hasHeader('X-Livewire-Navigate');
    }

    /**
     * Query parameters to add when `lang` is missing from the index URL.
     *
     * @return array<string, string>|null
     */
    public static function missingLangIndexParameters(): ?array
    {
        if (request()->has('lang')) {
            return null;
        }

        return self::indexUrlParameters();
    }

    public static function syncToRequest(string $lang): void
    {
        $lang = trim($lang);

        if ($lang !== '') {
            request()->merge(['lang' => $lang]);
        }
    }

    public static function syncTabToRequest(string $tab): void
    {
        $tab = trim($tab);

        if ($tab !== '') {
            request()->merge(['tab' => $tab]);
        }
    }

    /**
     * Current page query parameters for redirects (falls back to the referer on Livewire subrequests).
     *
     * @return array<string, string>
     */
    public static function currentQueryParameters(): array
    {
        $query = request()->query();

        if ($query !== []) {
            return self::normalizeQueryParameters($query);
        }

        $referer = request()->header('Referer');

        if (! is_string($referer) || $referer === '') {
            return [];
        }

        $refererQueryString = parse_url($referer, PHP_URL_QUERY);

        if (! is_string($refererQueryString) || $refererQueryString === '') {
            return [];
        }

        parse_str($refererQueryString, $refererQuery);

        return self::normalizeQueryParameters($refererQuery);
    }

    /**
     * Merge the current query string, override `lang`, and optionally `tab` / `selected`.
     *
     * @return array<string, string>
     */
    public static function languageChangeParameters(string $lang, ?string $tab = null, ?int $selectedRecordId = null): array
    {
        $parameters = self::currentQueryParameters();
        $parameters['lang'] = $lang;

        if ($tab !== null && $tab !== '') {
            $parameters['tab'] = $tab;
        }

        if (func_num_args() >= 3) {
            if ($selectedRecordId !== null && $selectedRecordId > 0) {
                $parameters['selected'] = (string) $selectedRecordId;
            } else {
                unset($parameters['selected']);
            }
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, string>
     */
    private static function normalizeQueryParameters(array $query): array
    {
        $normalized = [];

        foreach ($query as $key => $value) {
            if (! is_string($key) || $key === '' || is_array($value)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $normalized[$key] = (string) $value;
        }

        return $normalized;
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
    private static function adminLocaleVariantsForBaseLanguage(string $baseLanguage): array
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
