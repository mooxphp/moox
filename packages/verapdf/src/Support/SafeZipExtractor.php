<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Extracts ZIP archives while rejecting path-traversal (zip-slip) entries.
 */
final class SafeZipExtractor
{
    /**
     * Extract $zipPath into $targetDir after validating every entry stays under the target.
     *
     * @throws RuntimeException When the archive cannot be opened or an entry is unsafe.
     */
    public static function extract(string $zipPath, string $targetDir): void
    {
        File::ensureDirectoryExists($targetDir);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Cannot open ZIP: {$zipPath}");
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (! is_string($name) || $name === '') {
                    throw new RuntimeException('Refusing to extract unsafe ZIP entry: (empty name)');
                }

                self::assertEntryIsSafe($zip, $i, $name, $targetDir);
            }

            if (! $zip->extractTo($targetDir)) {
                throw new RuntimeException("Cannot extract ZIP: {$zipPath}");
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Reject absolute paths, parent/current segments, and symlink entries (aligned with VeraPdfOutputPath).
     */
    private static function assertEntryIsSafe(ZipArchive $zip, int $index, string $entryName, string $targetDir): void
    {
        if (self::isSymlinkEntry($zip, $index)) {
            self::reject($entryName);
        }

        $normalized = str_replace('\\', '/', $entryName);

        self::assertPathIsRelative($entryName, $normalized);
        self::assertPathHasNoUnsafeSegments($entryName, $normalized);
        self::assertPathStaysUnderTarget($entryName, $normalized, $targetDir);
    }

    private static function assertPathIsRelative(string $entryName, string $normalized): void
    {
        if ($normalized === '' || str_starts_with($normalized, '/') || preg_match('#^[A-Za-z]:/#', $normalized) === 1) {
            self::reject($entryName);
        }
    }

    private static function assertPathHasNoUnsafeSegments(string $entryName, string $normalized): void
    {
        foreach (explode('/', rtrim($normalized, '/')) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                self::reject($entryName);
            }
        }
    }

    private static function assertPathStaysUnderTarget(string $entryName, string $normalized, string $targetDir): void
    {
        $targetRoot = rtrim(str_replace('\\', '/', $targetDir), '/');
        $destination = $targetRoot.'/'.ltrim($normalized, '/');

        if ($destination !== $targetRoot && ! str_starts_with($destination, $targetRoot.'/')) {
            self::reject($entryName);
        }
    }

    /**
     * @throws RuntimeException
     */
    private static function reject(string $entryName): never
    {
        throw new RuntimeException("Refusing to extract unsafe ZIP entry: {$entryName}");
    }

    private static function isSymlinkEntry(ZipArchive $zip, int $index): bool
    {
        $opsys = 0;
        $attr = 0;

        if ($zip->getExternalAttributesIndex($index, $opsys, $attr) !== true) {
            return false;
        }

        if ($opsys !== ZipArchive::OPSYS_UNIX) {
            return false;
        }

        $mode = ($attr >> 16) & 0o170000;

        return $mode === 0o120000;
    }
}
