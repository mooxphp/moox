<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Builder\PresetRegistry;

class EntityService
{
    public function create(string $name, string $buildContext, string $presetName): array
    {
        $existingEntity = $this->findEntity($name, $buildContext);

        if ($existingEntity) {
            return ['entity' => $existingEntity, 'status' => 'exists'];
        }

        $entityId = DB::table('builder_entities')->insertGetId([
            'singular' => $name,
            'plural' => Str::plural($name),
            'preset' => $presetName,
            'build_context' => $buildContext,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'entity' => DB::table('builder_entities')->find($entityId),
            'status' => 'created',
        ];
    }

    public function rebuild(int $entityId, string $presetName): void
    {
        DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->delete();

        $preset = PresetRegistry::getPreset($presetName);
        foreach ($preset->getBlocks() as $index => $block) {
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

    public function recordBuild(int $entityId, string $buildContext, array $data, array $files): void
    {
        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->update(['is_active' => false]);

        DB::table('builder_entity_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $buildContext,
            'data' => json_encode($data),
            'files' => json_encode($files),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function delete(string $name, string $buildContext, bool $force = false): array
    {
        $entity = $this->findEntity($name, $buildContext);

        if (! $entity) {
            return ['status' => 'not_found'];
        }

        $latestBuild = $this->getLatestBuild($entity->id);

        if ($latestBuild) {
            DB::table('builder_entity_builds')
                ->where('id', $latestBuild->id)
                ->update(['is_active' => false]);
        }

        DB::table('builder_entities')
            ->where('id', $entity->id)
            ->update(['deleted_at' => now()]);

        return [
            'entity' => $entity,
            'build' => $latestBuild,
            'status' => 'deleted',
        ];
    }

    public function findEntity(string $name, string $buildContext): ?object
    {
        return DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $buildContext)
            ->whereNull('deleted_at')
            ->first();
    }

    public function getLatestBuild(int $entityId): ?object
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function cleanupPreviewFiles(object $build): void
    {
        $files = json_decode($build->files, true);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function dropPreviewTable(string $name): void
    {
        $tableName = Str::plural(Str::snake($name));
        if (Schema::hasTable($tableName)) {
            Schema::dropIfExists($tableName);
        }
    }
}
