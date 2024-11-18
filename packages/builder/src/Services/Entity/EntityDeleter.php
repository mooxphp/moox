<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class EntityDeleter extends AbstractEntityService
{
    public function execute(): void
    {
        $entity = $this->findEntity($this->context->getEntityName());

        if (! $entity) {
            throw new RuntimeException('Entity not found');
        }

        $latestBuild = $this->getLatestBuild($entity->id);

        if ($latestBuild) {
            $this->deactivateBuild($latestBuild->id);
        }

        if ($this->context->getContext() === 'preview') {
            DB::table('builder_entities')
                ->where('id', $entity->id)
                ->update(['previewed' => false]);
        } else {
            DB::table('builder_entities')
                ->where('id', $entity->id)
                ->update(['build_context' => null]);
        }
    }

    protected function findEntity(string $name): ?object
    {
        return DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $this->context->getContext())
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
