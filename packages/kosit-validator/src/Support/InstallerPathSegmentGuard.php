<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RuntimeException;

/**
 * Ensures configured KoSIT subdirectory names are single safe path segments.
 */
final class InstallerPathSegmentGuard
{
    public static function assertValid(string $segment, string $configKey): string
    {
        $segment = trim($segment);

        if ($segment === '') {
            throw new RuntimeException("KoSIT path segment [{$configKey}] must not be empty.");
        }

        if ($segment === '.' || $segment === '..') {
            throw new RuntimeException("KoSIT path segment [{$configKey}] must not be [{$segment}].");
        }

        if (str_contains($segment, '/') || str_contains($segment, '\\') || str_contains($segment, "\0")) {
            throw new RuntimeException("KoSIT path segment [{$configKey}] must be a single directory name.");
        }

        return $segment;
    }
}
