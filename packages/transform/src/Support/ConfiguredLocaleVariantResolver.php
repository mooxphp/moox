<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Support\Facades\Config;
use Moox\Transform\Contracts\LocaleVariantResolver;

final class ConfiguredLocaleVariantResolver implements LocaleVariantResolver
{
    public function resolve(mixed $languageKey): string
    {
        $resolver = $this->resolveConfiguredResolver();

        if ($resolver instanceof LocaleVariantResolver) {
            return $resolver->resolve($languageKey);
        }

        if (is_string($languageKey) && $languageKey !== '') {
            return $languageKey;
        }

        return (string) Config::get('transform.default_locale', app()->getLocale());
    }

    private function resolveConfiguredResolver(): ?LocaleVariantResolver
    {
        $class = Config::get('transform.locale_variant_resolver');

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return null;
        }

        $resolver = app($class);

        return $resolver instanceof LocaleVariantResolver ? $resolver : null;
    }
}
