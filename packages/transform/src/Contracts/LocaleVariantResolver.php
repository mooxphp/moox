<?php

declare(strict_types=1);

namespace Moox\Transform\Contracts;

interface LocaleVariantResolver
{
    public function resolve(mixed $languageKey): string;
}
