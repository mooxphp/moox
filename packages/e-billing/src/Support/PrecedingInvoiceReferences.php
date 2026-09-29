<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * BG-3 preceding invoice references (BT-25 number, BT-26 date) as stored in bill_data and on the invoice.
 */
final class PrecedingInvoiceReferences
{
    /**
     * Keeps well-formed rows only: a non-empty number and a Y-m-d date or null.
     *
     * @return list<array{number: string, date: ?string}>
     */
    public static function normalize(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $references = [];

        foreach ($raw as $row) {
            if (! is_array($row) || ! is_string($row['number'] ?? null) || trim($row['number']) === '') {
                continue;
            }

            $date = $row['date'] ?? null;

            $references[] = [
                'number' => trim($row['number']),
                'date' => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : null,
            ];
        }

        return $references;
    }

    /**
     * The first reference, which the MoSCoW fields `preceding_invoice_number` / `_date` describe.
     *
     * @return array{number: string, date: ?string}|null
     */
    public static function first(mixed $raw): ?array
    {
        return self::normalize($raw)[0] ?? null;
    }

    /**
     * Number form used to find a referenced invoice: separators ignored, so `30641.25` matches `3064125`.
     */
    public static function comparableNumber(string $number): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $number));
    }
}
