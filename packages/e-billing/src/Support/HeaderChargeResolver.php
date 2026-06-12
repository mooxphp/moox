<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Models\InvoiceAllowanceCharge;

final class HeaderChargeResolver
{
    /**
     * @var array<string, array{reason_text?: string, reason_code?: string, is_charge?: bool}>
     */
    public const FIELD_SPECS = [
        'shipping_cost' => ['reason_text' => 'Versand', 'is_charge' => true],
        'packaging_cost' => ['reason_text' => 'Verpackung', 'is_charge' => true],
        'minimum_quantity_surcharge' => ['reason_text' => 'Mindermengenzuschlag', 'is_charge' => true],
        'freight_flat_rate' => ['reason_text' => 'Frachtkostenpauschale', 'is_charge' => true],
        'discount_amount' => ['reason_code' => '95', 'is_charge' => false],
        'discount_percent' => ['reason_code' => '95', 'is_charge' => false],
    ];

    /**
     * @return array{reason_text?: string, reason_code?: string, is_charge?: bool}|null
     */
    public static function spec(string $field): ?array
    {
        return self::FIELD_SPECS[$field] ?? null;
    }

    public static function hasMatchingCharge(iterable $allowanceCharges, string $field): bool
    {
        $spec = self::spec($field);

        if ($spec === null) {
            return false;
        }

        if ($field === 'discount_percent') {
            return self::hasDiscountPercentSignal($allowanceCharges);
        }

        return self::firstMatchingCharge($allowanceCharges, $spec, $field === 'discount_amount') !== null;
    }

    public static function resolveAmount(iterable $allowanceCharges, string $field): ?float
    {
        if ($field === 'discount_percent') {
            return self::resolveDiscountPercent($allowanceCharges);
        }

        $spec = self::spec($field);

        if ($spec === null) {
            return null;
        }

        $charge = self::firstMatchingCharge($allowanceCharges, $spec, $field === 'discount_amount');

        if ($charge === null || $charge->amount === null || $charge->amount === '') {
            return null;
        }

        return (float) $charge->amount;
    }

    public static function hasDiscountPercentSignal(iterable $allowanceCharges): bool
    {
        return self::resolveDiscountPercent($allowanceCharges) !== null;
    }

    public static function resolveDiscountPercent(iterable $allowanceCharges): ?float
    {
        foreach ($allowanceCharges as $charge) {
            if (! $charge instanceof InvoiceAllowanceCharge || $charge->is_charge) {
                continue;
            }

            if ($charge->percentage !== null && $charge->percentage !== '' && (float) $charge->percentage > 0) {
                return (float) $charge->percentage;
            }

            $reasonText = (string) ($charge->reason_text ?? '');
            if (str_contains($reasonText, '%')) {
                if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/u', $reasonText, $matches) === 1) {
                    return (float) str_replace(',', '.', $matches[1]);
                }

                return 0.0;
            }
        }

        return null;
    }

    /**
     * @param  array{reason_text?: string, reason_code?: string, is_charge?: bool}  $spec
     */
    public static function matches(
        InvoiceAllowanceCharge $charge,
        array $spec,
        bool $allowDiscountTextFallback,
    ): bool {
        if (isset($spec['is_charge']) && (bool) $charge->is_charge !== $spec['is_charge']) {
            return false;
        }

        $reasonCode = $charge->reason_code;
        if (isset($spec['reason_code']) && $spec['reason_code'] !== null && $spec['reason_code'] !== '') {
            if ($reasonCode !== null && $reasonCode !== '' && $reasonCode === $spec['reason_code']) {
                return true;
            }
        }

        if (isset($spec['reason_text']) && $spec['reason_text'] !== '') {
            $expected = self::normalizeString($spec['reason_text']);
            $actual = self::normalizeString($charge->reason_text);

            if ($expected !== '' && strcasecmp($expected, $actual) === 0) {
                return true;
            }
        }

        if ($allowDiscountTextFallback && ! $charge->is_charge) {
            $text = self::normalizeString($charge->reason_text);

            if ($text !== '' && (strcasecmp($text, 'Rabatt') === 0 || str_contains($text, '%'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{reason_text?: string, reason_code?: string, is_charge?: bool}  $spec
     */
    private static function firstMatchingCharge(
        iterable $allowanceCharges,
        array $spec,
        bool $allowDiscountTextFallback,
    ): ?InvoiceAllowanceCharge {
        foreach ($allowanceCharges as $charge) {
            if ($charge instanceof InvoiceAllowanceCharge && self::matches($charge, $spec, $allowDiscountTextFallback)) {
                return $charge;
            }
        }

        return null;
    }

    private static function normalizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}
