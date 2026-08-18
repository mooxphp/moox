<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use DateTimeInterface;
use Moox\Zugferd\Support\FlexibleDateParser;

final class DeliveryDateNormalizer
{
    public static function normalize(?string $value): ?string
    {
        $parsed = FlexibleDateParser::parse($value);

        return $parsed?->format('Y-m-d');
    }

    public static function fromLine(mixed $line): ?string
    {
        if (! is_object($line) || ! isset($line->delivery_date)) {
            return null;
        }

        return self::fromScalar($line->delivery_date);
    }

    public static function fromScalar(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return null;
    }
}
