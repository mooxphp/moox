<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;

class EntityDeleter
{
    public function delete(string $name, string $buildContext, bool $force = false): array
    {
        $entity = $this->findEntity($name, $buildContext);

        if (! $entity) {
            return ['status' => 'not_found'];
        }

        $latestBuild = $this->getLatestBuild($entity->id);

        if ($latestBuild) {
            $this->deactivateBuild($latestBuild->id);
        }

        $this->softDeleteEntity($entity->id);

        return [
            'entity' => $entity,
            'build' => $latestBuild,
            'status' => 'deleted',
        ];
    }

    protected function findEntity(string $name, string $buildContext): ?object
    {
        return DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $buildContext)
            ->whereNull('deleted_at')
            ->first();
    }

    protected function getLatestBuild(int $entityId): ?object
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    protected function deactivateBuild(int $buildId): void
    {
        DB::table('builder_entity_builds')
            ->where('id', $buildId)
            ->update(['is_active' => false]);
    }

    protected function softDeleteEntity(int $entityId): void
    {
        DB::table('builder_entities')
            ->where('id', $entityId)
            ->update(['deleted_at' => now()]);
    }
}
