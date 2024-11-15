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
        return $this->format($files);
    }

    protected function format(array $files): array
    {
        $formatted = [];

        foreach ($files as $path => $content) {
            $formatted[$this->normalizePath($path)] = $content;
        }

        return $formatted;
    }

    protected function normalizePath(string $path): string
    {
        return str_replace(['\\', '//'], '/', $path);
    }
}
