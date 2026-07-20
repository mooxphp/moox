<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class RecursiveFileFinder
{
    public static function find(string $directory, string $filename): ?string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($file->getFilename() === $filename) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
