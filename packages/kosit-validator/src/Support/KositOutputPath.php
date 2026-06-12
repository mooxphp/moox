<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use Illuminate\Support\Facades\File;

/**
 * Resolves the configured KoSIT report output directory.
 */
final class KositOutputPath
{
    /**
     * Absolute filesystem path for KoSIT `-o` output (report XML/HTML).
     *
     * @param  string|null  $subdirectory  Optional segment appended (e.g. `default/2026/01/15`).
     */
    public static function resolve(?string $subdirectory = null): string
    {
        $path = config('kosit-validator.output.path');

        if (! is_string($path) || $path === '') {
            $legacy = config('kosit-validator.report_path');
            $path = is_string($legacy) ? $legacy : '';
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
