<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum ForeignDisposition: string
{
    case Ignore = 'ignore';
    case Forward = 'forward';

    public static function fromConfig(?string $value = null): self
    {
        $raw = $value ?? config('e-billing.foreign.disposition', self::Ignore->value);

        return self::tryFrom(is_string($raw) ? strtolower(trim($raw)) : '') ?? self::Ignore;
    }
}
