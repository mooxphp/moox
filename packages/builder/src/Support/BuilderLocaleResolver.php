<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;

final class BuilderLocaleResolver
{
    public const ADMIN_SESSION_KEY = 'builder.admin_locale';

    private ?string $overrideLocale = null;

    private ?bool $hasLocalizationsTable = null;

    private ?string $resolvedDefaultLocale = null;

    private ?string $resolvedAdminDefaultLocale = null;

    /** @var array<string, list<string>> */
    private array $resolvedFallbackChains = [];

    /**
     * @return list<string>
     */
    public const TRANSLATABLE_CONFIG_KEYS = [
        'helperText',
        'placeholder',
        'prefix',
        'suffix',
        'message',
    ];

    public function current(?string $locale = null): string
    {
        if ($this->overrideLocale !== null) {
            return $this->overrideLocale;
        }

        if (is_string($locale) && $locale !== '') {
            return $locale;
        }

        $requestLocale = request()->query('lang') ?? request()->input('lang');

        if (is_string($requestLocale) && $requestLocale !== '') {
            return $requestLocale;
        }

        $sessionLocale = session(self::ADMIN_SESSION_KEY);

        if (is_string($sessionLocale) && $sessionLocale !== '') {
            return $sessionLocale;
        }

        return $this->defaultLocale();
    }

    public function defaultLocale(): string
    {
        if ($this->resolvedDefaultLocale !== null) {
            return $this->resolvedDefaultLocale;
        }

        if ($this->localizationsTableExists()) {
            $localization = Localization::query()
                ->where('is_default', true)
                ->first();

            if ($localization !== null && filled($localization->getAttribute('locale_variant'))) {
                return $this->resolvedDefaultLocale = (string) $localization->getAttribute('locale_variant');
            }
        }

        return $this->resolvedDefaultLocale = (string) config('builder.default_locale', config('app.locale', 'en_US'));
    }

    public function adminDefaultLocale(): string
    {
        if ($this->resolvedAdminDefaultLocale !== null) {
            return $this->resolvedAdminDefaultLocale;
        }

        if ($this->localizationsTableExists()) {
            $defaultLocale = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->first();

            if ($defaultLocale !== null) {
                $variant = $defaultLocale->getAttribute('locale_variant');

                if (filled($variant)) {
                    return $this->resolvedAdminDefaultLocale = (string) $variant;
                }
            }

            $firstActiveLocale = Localization::query()
                ->where('is_active_admin', true)
                ->first();

            if ($firstActiveLocale !== null) {
                $variant = $firstActiveLocale->getAttribute('locale_variant');

                if (filled($variant)) {
                    return $this->resolvedAdminDefaultLocale = (string) $variant;
                }
            }
        }

        return $this->resolvedAdminDefaultLocale = $this->defaultLocale();
    }

    /**
     * @return list<string>
     */
    public function fallbackChain(?string $locale = null): array
    {
        $cacheKey = $this->current($locale);

        if (array_key_exists($cacheKey, $this->resolvedFallbackChains)) {
            return $this->resolvedFallbackChains[$cacheKey];
        }

        $chain = [
            $cacheKey,
            $this->defaultLocale(),
            'en_US',
        ];

        return $this->resolvedFallbackChains[$cacheKey] = array_values(array_unique(array_filter(
            $chain,
            static fn (string $value): bool => $value !== '',
        )));
    }

    public function localizationsTableExists(): bool
    {
        if ($this->hasLocalizationsTable !== null) {
            return $this->hasLocalizationsTable;
        }

        if (! class_exists(Localization::class)) {
            return $this->hasLocalizationsTable = false;
        }

        return $this->hasLocalizationsTable = Schema::hasTable('localizations');
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function using(string $locale, callable $callback): mixed
    {
        $previous = $this->overrideLocale;
        $this->overrideLocale = $locale;

        try {
            return $callback();
        } finally {
            $this->overrideLocale = $previous;
        }
    }

    public function valuesLocaleForEntity(string $entity, ?string $locale = null, ?string $modelClass = null): string
    {
        if (! app(CustomFieldsTranslatability::class)->valuesAreTranslatable($entity, $modelClass)) {
            return $this->defaultLocale();
        }

        return $this->current($locale);
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function valuesLocaleForResource(string $resourceClass, ?string $locale = null): string
    {
        $entity = $resourceClass::resolveCustomFieldsEntityIdentifier();

        if (! app(CustomFieldsTranslatability::class)->valuesAreTranslatable(
            $entity,
            $resourceClass::getModel(),
            $resourceClass,
        )) {
            return $this->defaultLocale();
        }

        return $this->current($locale);
    }

    /**
     * @return list<string>
     */
    public function valuesFallbackChainForEntity(string $entity, ?string $locale = null, ?string $modelClass = null): array
    {
        if (! app(CustomFieldsTranslatability::class)->valuesAreTranslatable($entity, $modelClass)) {
            return [$this->defaultLocale()];
        }

        return $this->fallbackChain($locale);
    }
}
