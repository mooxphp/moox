<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Support;

use Illuminate\Support\Facades\File;

/**
 * Resolves the configured veraPDF report output directory.
 */
final class VeraPdfOutputPath
{
    /**
     * Absolute filesystem path for veraPDF report output.
     *
     * @param  string|null  $subdirectory  Optional segment appended (e.g. `2026/07/17`).
     */
    public static function resolve(?string $subdirectory = null): string
    {
        $path = config('verapdf.output.path');

        if (! is_string($path) || $path === '') {
            $path = storage_path('app/private/verapdf-reports');
        }

        $path = rtrim($path, '/\\');

        if ($subdirectory === null || trim($subdirectory) === '') {
            File::ensureDirectoryExists($path, 0775, recursive: true);

            return $path;
        }

        $resolved = $path.'/'.trim(str_replace('\\', '/', $subdirectory), '/');
        File::ensureDirectoryExists($resolved, 0775, recursive: true);

        return $resolved;
    }
}
