<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Traits;

use Illuminate\Support\Facades\Log;

trait ConfigScannerTrait
{
    /**
     * Scans a directory and returns all files from the specified path.
     *
     * @param  string  $path  The directory path to scan.
     * @return array The list of files with full paths.
     */
    protected function getAllFilesFromDirectory(string $path): array
    {
        Log::warning($path);
        if (! is_dir($path)) {
            return [];
        }

        $output = array_filter(array_diff(scandir($path), ['.', '..']), function ($file) use ($path) {
            return is_file($path.DIRECTORY_SEPARATOR.$file);
        });

        Log::warning($output);

        return $output;
    }
}
