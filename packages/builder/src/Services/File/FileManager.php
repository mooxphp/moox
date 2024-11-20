<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class FileManager
{
    public function __construct(
        private readonly FileOperations $fileOperations,
        private readonly FileFormatter $fileFormatter
    ) {}

    public function deleteFiles(int $entityId, string $buildContext): void
    {
        $build = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->where('is_active', true)
            ->first();

        if (! $build || empty($build->files)) {
            return;
        }

        $files = json_decode($build->files, true);
        foreach ($files as $file) {
            $path = $file['path'] ?? null;
            if ($path) {
                $this->fileOperations->deleteFile($path);
                $this->removeEmptyDirectories(dirname($path));
            }
        }
    }

    public function cleanupBeforeRegeneration(int $entityId, string $buildContext): void
    {
        $this->deleteFiles($entityId, $buildContext);
    }

    public function writeAndFormatFiles(array $files): void
    {
        try {
            $this->validateFiles($files);
            foreach ($files as $path => $content) {
                $this->ensureDirectoryExists(dirname($path));
                $this->writeFile($path, $content);
            }
        } catch (\Exception $e) {
            throw new RuntimeException("File operation failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function formatFiles(array $files): void
    {
        if (empty($files)) {
            return;
        }

        if (isset($files[0])) {
            $paths = $files;
        } else {
            $paths = array_keys($files);
        }

        $this->fileFormatter->formatFiles($paths);
    }

    public function findMigrationFiles(string $path): array
    {
        if (! is_dir($path)) {
            throw new RuntimeException("Migration directory not found: {$path}");
        }

        $finder = new Finder;
        $finder->files()->in($path)->name('*_*.php')->sortByName();

        $migrations = [];
        foreach ($finder as $file) {
            $migrations[] = [
                'name' => $file->getBasename('.php'),
                'path' => $file->getRealPath(),
                'content' => file_get_contents($file->getRealPath()),
            ];
        }

        return $migrations;
    }

    protected function removeEmptyDirectories(string $path): void
    {
        if (empty($path) || $path === '.' || $path === '/') {
            return;
        }

        if (is_dir($path) && count(scandir($path)) === 2) {
            rmdir($path);
            $this->removeEmptyDirectories(dirname($path));
        }
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (! File::exists($directory)) {
            if (! File::makeDirectory($directory, 0755, true)) {
                throw new RuntimeException("Failed to create directory: {$directory}");
            }
        }
    }

    protected function writeFile(string $path, string $content): void
    {
        if (! File::put($path, $content)) {
            throw new RuntimeException("Failed to write file: {$path}");
        }
    }

    protected function validateFiles(array $files): void
    {
        if (empty($files)) {
            throw new RuntimeException('No files provided for operation');
        }

        foreach ($files as $path => $content) {
            if (! is_string($path)) {
                throw new RuntimeException('File path must be a string');
            }
            if (! is_string($content)) {
                throw new RuntimeException('File content must be a string');
            }
            if (empty(trim($path))) {
                throw new RuntimeException('File path cannot be empty');
            }
            if (File::exists($path) && ! is_writable($path)) {
                throw new RuntimeException("File not writable: {$path}");
            }
        }
    }
}
