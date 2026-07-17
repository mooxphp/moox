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

                self::assertEntryIsSafe($name, $targetDir);
            }

            if (! $zip->extractTo($targetDir)) {
                throw new RuntimeException("Cannot extract ZIP: {$zipPath}");
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Reject absolute paths and parent-directory segments (aligned with VeraPdfOutputPath).
     */
    private static function assertEntryIsSafe(string $entryName, string $targetDir): void
    {
        $normalized = str_replace('\\', '/', $entryName);

        if ($normalized === '' || str_starts_with($normalized, '/') || preg_match('#^[A-Za-z]:/#', $normalized) === 1) {
            throw new RuntimeException("Refusing to extract unsafe ZIP entry: {$entryName}");
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                throw new RuntimeException("Refusing to extract unsafe ZIP entry: {$entryName}");
            }
        }

        $targetRoot = rtrim(str_replace('\\', '/', $targetDir), '/');
        $destination = $targetRoot.'/'.ltrim($normalized, '/');

        if ($destination !== $targetRoot && ! str_starts_with($destination, $targetRoot.'/')) {
            throw new RuntimeException("Refusing to extract unsafe ZIP entry: {$entryName}");
        }
    }
}
