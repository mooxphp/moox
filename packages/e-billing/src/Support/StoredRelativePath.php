<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use InvalidArgumentException;

final class StoredRelativePath
{
    /**
     * Reject absolute paths and `..` segments so stored disk paths cannot escape their directory.
     */
    public static function assertSafe(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if ($normalized === '' || str_contains($normalized, '..')) {
            throw new InvalidArgumentException('Stored file path is not allowed.');
        }

        if (str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            throw new InvalidArgumentException('Stored file path must be relative.');
        }

        return $normalized;
    }

    /**
     * Require `$path` to live under `$directory` on the same disk (no leading/trailing slash on the prefix).
     */
    public static function assertUnderDirectory(string $path, string $directory): string
    {
        $normalized = self::assertSafe($path);
        $prefix = trim(str_replace('\\', '/', $directory), '/');

        if ($prefix === '') {
            return $normalized;
        }

        if ($normalized !== $prefix && ! str_starts_with($normalized, $prefix.'/')) {
            throw new InvalidArgumentException('Stored file path is outside the allowed directory.');
        }

        return $normalized;
    }
}
