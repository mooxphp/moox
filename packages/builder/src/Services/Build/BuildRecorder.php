<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class BuildRecorder
{
    public function record(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        if (! is_string($buildContext)) {
            throw new \InvalidArgumentException('buildContext must be a string, got: '.gettype($buildContext));
        }

        $this->validateFileStructure($files);
        $blockData = $this->serializeBlocks($blocks);

        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->update(['is_active' => false]);

        DB::table('builder_entity_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $buildContext,
            'data' => json_encode($blockData),
            'files' => json_encode($files),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function serializeBlocks(array $blocks): array
    {
        return array_map(function ($block) {
            return [
                'type' => get_class($block),
                'options' => $block->getOptions(),
                'migrations' => $block->getMigrations(),
            ];
        }, $blocks);
    }

    protected function validateFileStructure(array $files): void
    {
        foreach ($files as $type => $typeFiles) {
            if (! is_array($typeFiles)) {
                throw new RuntimeException("Invalid file structure for type: {$type}");
            }
            foreach ($typeFiles as $path => $content) {
                if (! is_string($path)) {
                    throw new RuntimeException("Invalid path in type {$type}");
                }
                if (! is_string($content)) {
                    throw new RuntimeException("Invalid content for path {$path} in type {$type}");
                }
            }
        }
    }
}
