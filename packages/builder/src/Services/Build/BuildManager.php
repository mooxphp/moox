<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Block\BlockReconstructor;
use RuntimeException;

class BuildManager
{
    public function __construct(
        private readonly BuildRecorder $buildRecorder,
        private readonly BlockReconstructor $blockReconstructor
    ) {}

    public function recordBuild(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        $this->validateContext($buildContext);
        $this->validateEntityExists($entityId);

        if ($buildContext !== 'preview' && $this->hasConflictingProductionBuild($entityId, $buildContext)) {
            throw new RuntimeException('Entity already has an active build in a different production context');
        }

        $this->deactivateBuildsForContext($entityId, $buildContext);
        $this->buildRecorder->record($entityId, $buildContext, $blocks, $files);
    }

    public function reconstructFromLatest(int $entityId, ?string $buildContext = null): array
    {
        $query = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true);

        if ($buildContext) {
            $query->where('build_context', $buildContext);
        }

        $latestBuild = $query->orderBy('created_at', 'desc')->first();

        if (! $latestBuild) {
            throw new RuntimeException("No active build found for entity {$entityId}");
        }

        return $this->blockReconstructor->reconstruct($latestBuild);
    }

    protected function validateContext(string $context): void
    {
        if (! in_array($context, ['preview', 'app', 'package'])) {
            throw new RuntimeException('Invalid build context');
        }
    }

    protected function validateEntityExists(int $entityId): void
    {
        if (! DB::table('builder_entities')->where('id', $entityId)->exists()) {
            throw new RuntimeException("Entity {$entityId} not found");
        }
    }

    protected function hasConflictingProductionBuild(int $entityId, string $newContext): bool
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->where('build_context', '!=', $newContext)
            ->where('build_context', '!=', 'preview')
            ->exists();
    }

    protected function deactivateBuildsForContext(int $entityId, string $buildContext): void
    {
        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->update(['is_active' => false]);
    }
}
