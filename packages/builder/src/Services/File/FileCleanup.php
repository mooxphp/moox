<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

use Illuminate\Support\Facades\DB;

class FileCleanup
{
    public function __construct(
        private readonly FileOperations $fileOperations
    ) {}

    public function cleanupEntityFiles(int $entityId, string $buildContext): void
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

    protected function removeEmptyDirectories(string $path): void
    {
        if (empty($path) || $path === '.' || $path === '/') {
            return;
        }

        while (! empty($path)) {
            if (is_dir($path) && count(scandir($path)) === 2) { // Only . and .. entries
                rmdir($path);
                $path = dirname($path);
            } else {
                break;
            }
        }
    }
}
