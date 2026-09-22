<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Zugferd\Contracts\ZugferdItemAttribute;
use Moox\Zugferd\Contracts\ZugferdItemClassification;
use Moox\Zugferd\Data\ItemAttribute;
use Moox\Zugferd\Data\ItemClassification;

/**
 * Maps generic invoice-line product facts to EN 16931 carriers (BG-32 / BT-158).
 * Host columns (material, weights, …) are read here; Moox Zugferd contracts stay free of host names.
 */
final class LineItemAttributeMapper
{
    /**
     * @return list<ZugferdItemAttribute>
     */
    public static function attributes(
        ?string $material,
        ?float $weightKgNet,
        ?float $weightKgTotal,
        ?string $materialTestCertificate,
    ): array {
        $attributes = [];

        $material = self::trim($material);
        if ($material !== null) {
            $attributes[] = new ItemAttribute('Material designation', $material);
        }

        if ($weightKgNet !== null) {
            $attributes[] = new ItemAttribute('Net weight', self::formatKg($weightKgNet));
        }

        if ($weightKgTotal !== null) {
            $attributes[] = new ItemAttribute('Gross weight', self::formatKg($weightKgTotal));
        }

        $certificate = self::trim($materialTestCertificate);
        if ($certificate !== null) {
            $attributes[] = new ItemAttribute('Material test certificate', $certificate);
        }

        return $attributes;
    }

    /**
     * @return list<ZugferdItemClassification>
     */
    public static function classifications(?string $customsTariffNumber): array
    {
        $code = self::trim($customsTariffNumber);
        if ($code === null) {
            return [];
        }

        return [new ItemClassification($code, 'HS')];
    }

    private static function formatKg(float $kg): string
    {
        $formatted = rtrim(rtrim(number_format($kg, 3, '.', ''), '0'), '.');

        return $formatted.' kg';
    }

    private static function trim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
