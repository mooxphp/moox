<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * The parts of a line delivered on different occasions: one entry per delivery with its date,
 * delivery note number and quantity. Empty for a line delivered at once.
 */
final class PartialDeliveries
{
    /**
     * Keeps entries that carry a date or a delivery note; values are trimmed, quantities cast to float.
     *
     * @return list<array{date: ?string, delivery_note: ?string, quantity: ?float}>
     */
    public static function normalize(mixed $deliveries): array
    {
        if (! is_array($deliveries)) {
            return [];
        }

        $normalized = [];

        foreach ($deliveries as $delivery) {
            if (! is_array($delivery)) {
                continue;
            }

            $date = self::text($delivery['date'] ?? null);
            $deliveryNote = self::text($delivery['delivery_note'] ?? null);

            if ($date === null && $deliveryNote === null) {
                continue;
            }

            $normalized[] = [
                'date' => $date,
                'delivery_note' => $deliveryNote,
                'quantity' => is_numeric($delivery['quantity'] ?? null) ? (float) $delivery['quantity'] : null,
            ];
        }

        return $normalized;
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
