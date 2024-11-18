<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

use Illuminate\Support\Facades\DB;

class FileManager
{
    public function __construct(
        private readonly FileOperations $fileOperations,
        private readonly FileFormatter $fileFormatter,
        private readonly FileCleanup $fileCleanup
    ) {}

    public function cleanupBeforeRegeneration(int $entityId, string $buildContext): void
    {
        $this->fileCleanup->cleanupEntityFiles($entityId, $buildContext);
    }

    public function writeAndFormatFiles(array $files): void
    {
        foreach ($files as $path => $content) {
            $this->fileOperations->writeFile($path, $content);
        }
        $this->fileFormatter->formatFiles(array_keys($files));
    }

    public function formatFiles(array $files): void
    {
        if (empty($files)) {
            return;
        }

        $paths = array_map(
            fn ($file) => $file['path'],
            $files
        );

        $this->fileFormatter->formatFiles($paths);
    }

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
                $this->removeEmptyParentDirectories(dirname($path));
            }
        }
    }

    protected function removeEmptyParentDirectories(string $path): void
    {
        if (empty($path) || $path === '.' || $path === '/') {
            return;
        }

        while (! empty($path)) {
            if (is_dir($path) && count(scandir($path)) === 2) {
                rmdir($path);
                $path = dirname($path);
            } else {
                break;
            }
        }
    }
}
