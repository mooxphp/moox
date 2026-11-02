<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * The configured reason code (BT-98 / BT-105) of each payload-derived allowance and charge (ADR 0014),
 * from `e-billing.allowance_charge_reason_codes`. A field configured as null carries a reason text only.
 */
final class AllowanceChargeReasonCodes
{
    /** The only payload allowance; every other field is a charge. */
    public const DISCOUNT_FIELD = 'discount_amount';

    public static function header(string $field): ?string
    {
        return self::code('header', $field);
    }

    public static function line(string $field): ?string
    {
        return self::code('line', $field);
    }

    /**
     * Header fields configured as null: their charges go out with a reason text only, on purpose.
     *
     * @return list<string>
     */
    public static function textOnlyHeaderFields(): array
    {
        $fields = config('e-billing.allowance_charge_reason_codes.header', []);

        return is_array($fields)
            ? array_map('strval', array_keys(array_filter($fields, static fn (mixed $code): bool => $code === null)))
            : [];
    }

    /**
     * Every configured code with its direction, for the fail-fast codelist check.
     *
     * @return list<array{code: string, is_charge: bool, key: string}>
     */
    public static function configured(): array
    {
        $configured = [];

        foreach (['header', 'line'] as $level) {
            $fields = config("e-billing.allowance_charge_reason_codes.{$level}", []);

            foreach (is_array($fields) ? $fields : [] as $field => $code) {
                if ($code === null) {
                    continue;
                }

                $configured[] = [
                    'code' => (string) $code,
                    'is_charge' => ! ($level === 'header' && $field === self::DISCOUNT_FIELD),
                    'key' => "allowance_charge_reason_codes.{$level}.{$field}",
                ];
            }
        }

        return $configured;
    }

    private static function code(string $level, string $field): ?string
    {
        $code = config("e-billing.allowance_charge_reason_codes.{$level}.{$field}");

        return is_string($code) && trim($code) !== '' ? trim($code) : null;
    }
}
