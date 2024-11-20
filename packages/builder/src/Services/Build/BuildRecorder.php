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

        DB::beginTransaction();
        try {
            $this->validateFileStructure($files);
            $blockData = $this->serializeBlocks($blocks);

            $this->deactivateBuilds($entityId, $buildContext);
            $this->recordBuild($entityId, $buildContext, $blockData, $files);
            $this->persistBlocks($entityId, $blocks);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new RuntimeException('Failed to record build: '.$e->getMessage());
        }
    }

    public function validateEntityExists(int $entityId): bool
    {
        return DB::table('builder_entities')->where('id', $entityId)->exists();
    }

    public function hasConflictingProductionBuild(int $entityId, string $newContext): bool
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->where('build_context', '!=', $newContext)
            ->where('build_context', '!=', 'preview')
            ->exists();
    }

    public function loadCurrentState(int $entityId, string $contextType): array
    {
        $build = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $contextType)
            ->where('is_active', true)
            ->first();

        if (! $build) {
            return [];
        }

        return [
            'entity_id' => $build->entity_id,
            'build_context' => $build->build_context,
            'data' => json_decode($build->data, true),
            'files' => json_decode($build->files, true),
        ];
    }

    protected function deactivateBuilds(int $entityId, string $buildContext): void
    {
        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->update(['is_active' => false]);
    }

    protected function recordBuild(int $entityId, string $buildContext, array $blockData, array $files): void
    {
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

    protected function persistBlocks(int $entityId, array $blocks): void
    {
        DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->delete();

        foreach ($blocks as $index => $block) {
            DB::table('builder_entity_blocks')->insert([
                'entity_id' => $entityId,
                'title' => $block->getTitle(),
                'description' => $block->getDescription(),
                'block_class' => get_class($block),
                'options' => json_encode($block->getOptions()),
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
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

    public function getLatestBuild(int $entityId, ?string $buildContext = null): object
    {
        $query = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true);

        if ($buildContext) {
            $query->where('build_context', $buildContext);
        }

        $build = $query->orderBy('created_at', 'desc')->first();

        if (! $build) {
            throw new RuntimeException("No active build found for entity {$entityId}");
        }

        return $build;
    }

    public function getEntityIdFromName(string $entityName): int
    {
        $entity = DB::table('builder_entities')
            ->where('singular', $entityName)
            ->first();

        if (! $entity) {
            throw new RuntimeException("Entity not found with name: {$entityName}");
        }

        return $entity->id;
    }
}
