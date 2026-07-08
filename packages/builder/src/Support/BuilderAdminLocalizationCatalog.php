<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Collection;
use Moox\Localization\Models\Localization;

/**
 * Request-scoped catalog of admin localizations for builder language switchers.
 *
 * Loads active admin localizations once and avoids {@see Localization} display
 * accessors that trigger extra static-data queries per locale.
 */
final class BuilderAdminLocalizationCatalog
{
    /** @var Collection<int, Localization>|null */
    private ?Collection $adminLocalizations = null;

    public function __construct(
        protected BuilderLocaleResolver $localeResolver,
    ) {}

    public function isAvailable(): bool
    {
        return class_exists(Localization::class) && $this->localeResolver->localizationsTableExists();
    }

    /**
     * @return Collection<int, Localization>
     */
    public function adminLocalizations(): Collection
    {
        if ($this->adminLocalizations !== null) {
            return $this->adminLocalizations;
        }

        if (! $this->isAvailable()) {
            return $this->adminLocalizations = collect();
        }

        return $this->adminLocalizations = Localization::query()
            ->with('language')
            ->where('is_active_admin', true)
            ->orderBy('language_id')
            ->orderBy('locale_variant')
            ->get();
    }

    public function find(string $localeVariant): ?Localization
    {
        if ($localeVariant === '') {
            return null;
        }

        return $this->adminLocalizations()->firstWhere('locale_variant', $localeVariant);
    }

    public function isAllowedAdminLocale(string $localeVariant): bool
    {
        if (! $this->isAvailable()) {
            return true;
        }

        return $this->find($localeVariant) !== null;
    }

    public function labelFor(?Localization $localization, ?string $fallbackLocale = null): string
    {
        if ($localization === null) {
            return $fallbackLocale ?? '';
        }

        if (filled($localization->title)) {
            return (string) $localization->title;
        }

        $language = $localization->relationLoaded('language') ? $localization->language : null;

        if ($language !== null && filled($language->common_name)) {
            return (string) $language->common_name;
        }

        return (string) ($localization->locale_variant ?? $fallbackLocale ?? '');
    }

    public function flagFor(?Localization $localization, ?string $fallbackLocale = null): string
    {
        if ($localization === null) {
            return 'flag-'.strtolower((string) $fallbackLocale);
        }

        $language = $localization->relationLoaded('language') ? $localization->language : null;

        if ($language !== null && filled($language->flag_icon)) {
            if (! $localization->use_country_icon) {
                return (string) $language->flag_icon;
            }

            $countryCode = $this->countryCodeFromLocaleVariant((string) $localization->locale_variant);

            if ($countryCode !== '') {
                return 'flag-'.$countryCode;
            }

            return (string) $language->flag_icon;
        }

        $countryCode = $this->countryCodeFromLocaleVariant((string) $localization->locale_variant);

        return $countryCode !== '' ? 'flag-'.$countryCode : 'flag-'.strtolower((string) $fallbackLocale);
    }

    protected function countryCodeFromLocaleVariant(string $localeVariant): string
    {
        if (! str_contains($localeVariant, '_')) {
            return '';
        }

        $parts = explode('_', $localeVariant, 2);

        return strtolower($parts[1] ?? '');
    }
}
