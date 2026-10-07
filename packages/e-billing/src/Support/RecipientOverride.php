<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use InvalidArgumentException;

/**
 * One-off mail addresses an operator enters during selective redispatch (ADR 0016).
 */
final class RecipientOverride
{
    /**
     * {@see self::addresses()}, then validate each address.
     *
     * @param  array<int, mixed>|null  $addresses
     * @return list<string>|null null when nothing is left (no override)
     *
     * @throws InvalidArgumentException when an address is not a valid e-mail address
     */
    public static function normalize(?array $addresses): ?array
    {
        if ($addresses === null) {
            return null;
        }

        foreach ($addresses as $address) {
            if (! is_string($address)) {
                throw new InvalidArgumentException('Each override recipient must be an e-mail address string.');
            }
        }

        $normalized = self::addresses($addresses);

        foreach ($normalized as $address) {
            if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("{$address} is not a valid e-mail address.");
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * Trim, drop blanks and non-strings, de-duplicate (case-insensitive, first spelling wins) — without
     * validating, so it is safe on half-typed form input.
     *
     * @return list<string>
     */
    public static function addresses(mixed $addresses): array
    {
        $unique = [];

        foreach ((array) $addresses as $address) {
            $address = is_string($address) ? trim($address) : '';

            if ($address !== '') {
                $unique[strtolower($address)] ??= $address;
            }
        }

        return array_values($unique);
    }
}
