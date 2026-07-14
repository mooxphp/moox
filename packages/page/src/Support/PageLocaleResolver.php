<?php

declare(strict_types=1);

namespace Moox\Page\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Moox\Localization\Models\Localization;

final class PageLocaleResolver
{
    /**
     * @return list<string>
     */
    public function candidates(): array
    {
        return array_values(array_filter([
            request()->query('lang'),
            request()->input('lang'),
            session('locale'),
            request()->cookie('switch_locale'),
        ], fn (mixed $value): bool => is_string($value) && $value !== ''));
    }

    public function resolve(): string
    {
        $localizations = $this->frontendLocalizations();

        foreach ($this->candidates() as $candidate) {
            $resolved = $this->matchCandidate($candidate, $localizations);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        $default = $localizations->firstWhere('is_default', true);

        if (is_array($default)) {
            return $default['locale_variant'] ?? app()->getLocale();
        }

        return app()->getLocale();
    }

    /**
     * @return Collection<int, array{locale_variant: string, is_default: bool, alpha2: ?string}>
     */
    private function frontendLocalizations(): Collection
    {
        $ttl = (int) config('page.cache.locale_ttl', 3600);

        if (! config('page.cache.enabled', false)) {
            return $this->localizationRows($this->loadFrontendLocalizations());
        }

        $cached = Cache::get('page.localizations.frontend');

        if ($this->isValidLocalizationCache($cached)) {
            return collect($cached);
        }

        if ($cached !== null) {
            Cache::forget('page.localizations.frontend');
        }

        $rows = $this->localizationRows($this->loadFrontendLocalizations())->all();

        Cache::put('page.localizations.frontend', $rows, $ttl);

        return collect($rows);
    }

    /**
     * @param  Collection<int, Localization>  $localizations
     * @return Collection<int, array{locale_variant: string, is_default: bool, alpha2: ?string}>
     */
    private function localizationRows(Collection $localizations): Collection
    {
        return $localizations->map(fn (Localization $localization): array => [
            'locale_variant' => $localization->locale_variant,
            'is_default' => (bool) $localization->is_default,
            'alpha2' => $localization->language?->alpha2,
        ])->values();
    }

    /**
     * @return Collection<int, Localization>
     */
    private function loadFrontendLocalizations(): Collection
    {
        return Localization::query()
            ->with('language')
            ->where('is_active_frontend', true)
            ->orderByDesc('is_default')
            ->get();
    }

    /**
     * @param  Collection<int, array{locale_variant: string, is_default: bool, alpha2: ?string}>  $localizations
     */
    private function matchCandidate(string $locale, Collection $localizations): ?string
    {
        $exact = $localizations->firstWhere('locale_variant', $locale);

        if (is_array($exact)) {
            return $exact['locale_variant'];
        }

        $byAlpha2 = $localizations->first(
            fn (array $localization): bool => ($localization['alpha2'] ?? null) === $locale
        );

        return is_array($byAlpha2) ? $byAlpha2['locale_variant'] : null;
    }

    private function isValidLocalizationCache(mixed $cached): bool
    {
        if (! is_array($cached) || $cached === []) {
            return false;
        }

        foreach ($cached as $row) {
            if (! is_array($row)) {
                return false;
            }

            if (! isset($row['locale_variant']) || ! is_string($row['locale_variant'])) {
                return false;
            }
        }

        return true;
    }
}
