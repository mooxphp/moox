<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Models\InvoiceAllowanceCharge;

final class HeaderChargeResolver
{
    /**
     * Header fields with their reason text; the reason code is added from
     * {@see AllowanceChargeReasonCodes} by {@see spec()}.
     *
     * @var array<string, array{reason_text?: string, is_charge: bool}>
     */
    public const FIELD_SPECS = [
        'shipping_cost' => ['reason_text' => 'Versand', 'is_charge' => true],
        'packaging_cost' => ['reason_text' => 'Verpackung', 'is_charge' => true],
        'minimum_quantity_surcharge' => ['reason_text' => 'Mindermengenzuschlag', 'is_charge' => true],
        'freight_flat_rate' => ['reason_text' => 'Frachtkostenpauschale', 'is_charge' => true],
        'certificate_cost' => ['reason_text' => 'Attestkosten', 'is_charge' => true],
        'customs_cost' => ['reason_text' => 'Zollkosten', 'is_charge' => true],
        'discount_amount' => ['is_charge' => false],
        'discount_percent' => ['is_charge' => false],
    ];

    /**
     * @return array{reason_text?: string, reason_code?: string, is_charge?: bool}|null
     */
    public static function spec(string $field): ?array
    {
        $spec = self::FIELD_SPECS[$field] ?? null;

        if ($spec === null) {
            return null;
        }

        $code = AllowanceChargeReasonCodes::header(
            $field === 'discount_percent' ? AllowanceChargeReasonCodes::DISCOUNT_FIELD : $field,
        );

        return $code !== null ? [...$spec, 'reason_code' => $code] : $spec;
    }

    /**
     * The codes configured for the header fields of one direction, upper-cased.
     *
     * @return list<string>
     */
    private static function configuredCodes(bool $isCharge): array
    {
        $codes = [];

        foreach (self::FIELD_SPECS as $field => $spec) {
            $code = AllowanceChargeReasonCodes::header($field);

            if ($spec['is_charge'] === $isCharge && $code !== null) {
                $codes[] = strtoupper($code);
            }
        }

        return $codes;
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

        // A charge field may be itemized (e.g. Versand per delivery): its amount is the sum of its rows.
        $charges = $field === 'discount_amount'
            ? array_filter([self::firstMatchingCharge($allowanceCharges, $spec, true)])
            : self::matchingCharges($allowanceCharges, $field);

        if ($charges === []) {
            return null;
        }

        return round(array_sum(array_map(
            static fn (InvoiceAllowanceCharge $charge): float => (float) $charge->amount,
            $charges,
        )), 2);
    }

    /**
     * The charges of a field charged more than once (e.g. Versand per delivery), which review shows and
     * corrects one by one below their sum; empty when the field has one charge or none.
     *
     * @return list<InvoiceAllowanceCharge>
     */
    public static function itemizedCharges(iterable $allowanceCharges, string $field): array
    {
        if ($field === 'discount_percent') {
            return [];
        }

        $charges = self::matchingCharges($allowanceCharges, $field);

        return count($charges) > 1 ? $charges : [];
    }

    /**
     * Every charge of the field, in order.
     *
     * @return list<InvoiceAllowanceCharge>
     */
    public static function matchingCharges(iterable $allowanceCharges, string $field): array
    {
        $spec = self::spec($field);

        if ($spec === null) {
            return [];
        }

        $matching = [];
        foreach ($allowanceCharges as $charge) {
            if ($charge instanceof InvoiceAllowanceCharge && self::matches($charge, $spec, $field === 'discount_amount')) {
                $matching[] = $charge;
            }
        }

        return $matching;
    }

    /**
     * The document discount (BG-20); its reason text is the discount reason (BT-97).
     */
    public static function discountCharge(iterable $allowanceCharges): ?InvoiceAllowanceCharge
    {
        return self::firstMatchingCharge($allowanceCharges, (array) self::spec('discount_amount'), true);
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
        }

        return null;
    }

    /**
     * Code first (ADR 0014): the field's configured code matches; a code configured for another header field
     * belongs to that field. An uncoded charge, or one whose code no field is configured with (e.g. a wrong-list
     * code still to be corrected), is recognised by its reason text as before.
     *
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

        $reasonCode = strtoupper(self::normalizeString($charge->reason_code));
        if ($reasonCode !== '') {
            if ($reasonCode === strtoupper($spec['reason_code'] ?? '')) {
                return true;
            }

            if (in_array($reasonCode, self::configuredCodes((bool) $charge->is_charge), true)) {
                return false;
            }
        }

        if (isset($spec['reason_text']) && $spec['reason_text'] !== '') {
            $expected = self::normalizeString($spec['reason_text']);
            $actual = self::normalizeString($charge->reason_text);

            // The label alone, or the label qualified by what the charge is for ("Versand delivery 56793.26").
            if ($expected !== '' && (strcasecmp($expected, $actual) === 0 || stripos($actual, $expected.' ') === 0)) {
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
