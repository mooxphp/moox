<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

use Illuminate\Support\Facades\DB;

class FileManager
{
    public function recordFiles(int $entityId, string $buildContext, array $files): void
    {
        $formattedFiles = $this->formatFiles($files);

        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->update([
                'files' => json_encode($formattedFiles),
                'updated_at' => now(),
            ]);
    }

    protected function formatFiles(array $files): array
    {
        if (empty($files)) {
            return [];
        }

        // Handle preview context (array of paths)
        if (is_numeric(array_key_first($files))) {
            return array_map(
                fn ($path) => ['path' => $this->normalizePath((string) $path)],
                $files
            );
        }

        // Handle production context (path => content pairs)
        return array_map(
            fn ($path, $content) => [
                'path' => $this->normalizePath((string) $path),
                'content' => $content,
            ],
            array_keys($files),
            $files
        );
    }

    protected function normalizePath(string $path): string
    {
        return str_replace(['\\', '//'], '/', $path);
    }
}
