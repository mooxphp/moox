<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

final class VatIdNormalizer
{
    public static function normalize(?string $vatId): ?string
    {
        if ($vatId === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim($vatId));

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        return $normalized;
    }
}
