<?php

declare(strict_types=1);

namespace Moox\Transform\Tests\Support;

use Moox\Transform\Contracts\LocaleVariantResolver;

final class FakeLocaleVariantResolver implements LocaleVariantResolver
{
    public function resolve(mixed $languageKey): string
    {
        return match ((string) $languageKey) {
            'en' => 'en_US',
            'de' => 'de_DE',
            default => (string) $languageKey,
        };
    }
}
