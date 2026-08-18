<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

final class AddressLineMerger
{
    public static function join(?string $line2, ?string $line3): ?string
    {
        $trimmedLine2 = self::trimmedNonEmpty($line2);
        $trimmedLine3 = self::trimmedNonEmpty($line3);

        if ($trimmedLine3 === null) {
            return $trimmedLine2;
        }

        if ($trimmedLine2 === null) {
            return $trimmedLine3;
        }

        return $trimmedLine2."\n".$trimmedLine3;
    }

    private static function trimmedNonEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
