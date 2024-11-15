<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Illuminate\Support\Facades\DB;

class BuildRecorder
{
    public function record(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        if (! is_string($buildContext)) {
            throw new \InvalidArgumentException('buildContext must be a string, got: '.gettype($buildContext));
        }

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
                // TODO: could be removed, but not sure if we need it again
                // 'useStatements' => $block->getUseStatements(),
                // ... other block data?
            ];
        }, $blocks);
    }
}
