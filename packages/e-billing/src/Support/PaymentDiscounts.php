<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * Early-payment discount tiers as stored in bill_data and on the invoice: days after the invoice date,
 * percent, an optional base amount (null = the amount due) and the discount amount as printed.
 *
 * @phpstan-type PaymentDiscount array{days: int, percent: float, base_amount: ?float, amount: ?float}
 */
final class PaymentDiscounts
{
    /**
     * Keeps well-formed rows only: whole days of zero or more and a positive percent.
     *
     * @return list<PaymentDiscount>
     */
    public static function normalize(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $discounts = [];

        foreach ($raw as $row) {
            if (! is_array($row)
                || filter_var($row['days'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false
                || ! is_numeric($row['percent'] ?? null)
                || (float) $row['percent'] <= 0
            ) {
                continue;
            }

            $discounts[] = [
                'days' => (int) $row['days'],
                'percent' => (float) $row['percent'],
                'base_amount' => is_numeric($row['base_amount'] ?? null) ? (float) $row['base_amount'] : null,
                'amount' => is_numeric($row['amount'] ?? null) ? (float) $row['amount'] : null,
            ];
        }

        return $discounts;
    }

    /**
     * The tiers as the converter writes them; the printed amount is only checked, never transmitted.
     *
     * @return list<array{days: int, percent: float, base_amount: ?float}>
     */
    public static function forEmission(mixed $raw): array
    {
        return array_map(
            static fn (array $discount): array => [
                'days' => $discount['days'],
                'percent' => $discount['percent'],
                'base_amount' => $discount['base_amount'],
            ],
            self::normalize($raw),
        );
    }
}
