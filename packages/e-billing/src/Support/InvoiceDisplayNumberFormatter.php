<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

final class InvoiceDisplayNumberFormatter
{
    public static function formatQuantity(float|int|string $value): string
    {
        $float = (float) $value;

        if (self::isWholeNumber($float)) {
            return number_format($float, 0, ',', '.');
        }

        $normalized = rtrim(rtrim(sprintf('%.10F', $float), '0'), '.');
        [$integerPart, $decimalPart] = array_pad(explode('.', $normalized), 2, '');

        $formattedInteger = number_format((float) $integerPart, 0, ',', '.');

        return $decimalPart === '' ? $formattedInteger : $formattedInteger.','.$decimalPart;
    }

    public static function formatWeight(float|int|string $value): string
    {
        return number_format((float) $value, 3, ',', '.');
    }

    private static function isWholeNumber(float $value): bool
    {
        return abs($value - round($value)) < 1e-9;
    }
}
