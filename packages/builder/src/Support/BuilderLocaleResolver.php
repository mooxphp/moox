<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;

final class BuilderLocaleResolver
{
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
        if (is_string($locale) && $locale !== '') {
            return $locale;
        }

        $requestLocale = request()->query('lang') ?? request()->input('lang');

        if (is_string($requestLocale) && $requestLocale !== '') {
            return $requestLocale;
        }

        return $this->defaultLocale();
    }

    public function defaultLocale(): string
    {
        if (class_exists(Localization::class) && Schema::hasTable('localizations')) {
            $localization = Localization::query()
                ->where('is_default', true)
                ->first();

            if ($localization !== null && filled($localization->getAttribute('locale_variant'))) {
                return (string) $localization->getAttribute('locale_variant');
            }
        }

        return (string) config('builder.default_locale', config('app.locale', 'en_US'));
    }

    public function adminDefaultLocale(): string
    {
        if (class_exists(Localization::class) && Schema::hasTable('localizations')) {
            $defaultLocale = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->first();

            if ($defaultLocale !== null) {
                $variant = $defaultLocale->getAttribute('locale_variant');

                if (filled($variant)) {
                    return (string) $variant;
                }
            }

            $firstActiveLocale = Localization::query()
                ->where('is_active_admin', true)
                ->first();

            if ($firstActiveLocale !== null) {
                $variant = $firstActiveLocale->getAttribute('locale_variant');

                if (filled($variant)) {
                    return (string) $variant;
                }
            }
        }

        return $this->defaultLocale();
    }

    /**
     * @return list<string>
     */
    public function fallbackChain(?string $locale = null): array
    {
        $chain = [
            $this->current($locale),
            $this->defaultLocale(),
            'en_US',
        ];

        return array_values(array_unique(array_filter(
            $chain,
            static fn (string $value): bool => $value !== '',
        )));
    }
}
