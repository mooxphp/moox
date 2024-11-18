<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

class FileOperations
{
    public function writeFile(string $path, string $content): void
    {
        $normalizedPath = $this->normalizePath($path);
        $this->ensureDirectoryExists($normalizedPath);
        file_put_contents($normalizedPath, $content);
    }

    public function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    public function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        return preg_replace('#/+#', '/', $normalized);
    }

    public function deleteFile(string $path): void
    {
        $normalizedPath = $this->normalizePath($path);
        if (file_exists($normalizedPath)) {
            unlink($normalizedPath);
        }
    }
}
