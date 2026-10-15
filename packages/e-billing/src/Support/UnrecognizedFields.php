<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * Text a parser found where a field is printed but could not read (fully) as that field's value, by field
 * name, as stored in bill_data (`unrecognized_fields` on the invoice and on each line).
 */
final class UnrecognizedFields
{
    /**
     * Keeps entries with a field name and a printed text only.
     *
     * @return array<string, string>
     */
    public static function normalize(mixed $raw): array
    {
        return is_array($raw)
            ? array_filter(
                $raw,
                static fn (mixed $text, mixed $field): bool => is_string($field) && is_string($text),
                ARRAY_FILTER_USE_BOTH,
            )
            : [];
    }
}
