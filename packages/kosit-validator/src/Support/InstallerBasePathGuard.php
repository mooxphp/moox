<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RuntimeException;

/**
 * Ensures the configured KoSIT base path cannot point at an arbitrary filesystem location.
 */
final class InstallerBasePathGuard
{
    public static function assertSafe(string $basePath): void
    {
        $normalized = self::normalizePath($basePath);

        if ($normalized === '') {
            throw new RuntimeException('KoSIT base path must not be empty.');
        }

        if (config('kosit-validator.installer.allow_untrusted_base_path')) {
            return;
        }

        $storageRoot = self::normalizePath((string) config('kosit-validator.installer.storage_root'));

        if ($storageRoot === '') {
            throw new RuntimeException('KoSIT installer storage root must not be empty.');
        }

        if ($normalized !== $storageRoot && ! str_starts_with($normalized.'/', $storageRoot.'/')) {
            throw new RuntimeException(
                "KoSIT base path must be under {$storageRoot}. Got: {$basePath}. "
                .'Set KOSIT_ALLOW_UNTRUSTED_BASE_PATH=true only for local/testing.'
            );
        }
    }

    private static function normalizePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ($path === '') {
            return '';
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        if (str_starts_with($path, '/')) {
            return '/'.$normalized;
        }

        return $normalized;
    }
}
