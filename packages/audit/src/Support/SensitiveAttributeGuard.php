<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Support\Str;

/**
 * Masks sensitive attribute values before they are persisted or shown.
 *
 * Keys matching `audit.mask_attributes` keep their presence in the diff
 * (so "password changed" remains visible) but values are replaced with {@see self::MASK}.
 * Completely omitted fields belong in per-model `hidden_attributes`.
 */
final class SensitiveAttributeGuard
{
    public const MASK = '******';

    public static function shouldMaskKey(string $key): bool
    {
        $keyLower = Str::lower($key);
        $patterns = config('audit.mask_attributes', []);

        if (! is_array($patterns) || $patterns === []) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            $patternLower = Str::lower($pattern);

            if ($keyLower === $patternLower || str_contains($keyLower, $patternLower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function maskValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (! is_string($key) || $value === null || ! self::shouldMaskKey($key)) {
                continue;
            }

            $values[$key] = self::MASK;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public static function maskChanges(array $changes): array
    {
        if (isset($changes['attributes']) && is_array($changes['attributes'])) {
            $changes['attributes'] = self::maskValues($changes['attributes']);
        }

        if (isset($changes['old']) && is_array($changes['old'])) {
            $changes['old'] = self::maskValues($changes['old']);
        }

        return $changes;
    }
}
