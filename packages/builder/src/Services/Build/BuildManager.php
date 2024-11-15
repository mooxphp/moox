<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Block\BlockReconstructor;
use Moox\Builder\Services\File\FileManager;

class BuildManager
{
    public function __construct(
        private readonly BuildRecorder $buildRecorder,
        private readonly BlockReconstructor $blockReconstructor,
        private readonly FileManager $fileManager
    ) {}

    public function recordBuild(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        if (! is_string($buildContext)) {
            throw new \InvalidArgumentException('buildContext must be a string, got: '.gettype($buildContext));
        }

        if ($buildContext !== 'preview' && $this->hasConflictingProductionBuild($entityId, $buildContext)) {
            throw new \RuntimeException('Entity already has an active build in a different production context');
        }

        $this->deactivateBuildsForContext($entityId, $buildContext);
        $this->buildRecorder->record($entityId, $buildContext, $blocks, $files);
        $this->fileManager->recordFiles($entityId, $buildContext, $files);
    }

    protected function hasConflictingProductionBuild(int $entityId, string $newContext): bool
    {
        if ($newContext === 'preview') {
            return false;
        }

        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', '!=', $newContext)
            ->where('build_context', '!=', 'preview')
            ->where('is_active', true)
            ->exists();
    }

    public function getLatestBuild(int $entityId, ?string $buildContext = null): ?object
    {
        $query = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true);

        if ($buildContext) {
            $query->where('build_context', $buildContext);
        }

        return $query->orderBy('created_at', 'desc')->first();
    }

    public function getActiveBuildsByContext(int $entityId): array
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('build_context')
            ->map(fn ($builds) => $builds->first())
            ->all();
    }

    public function reconstructFromLatest(int $entityId, ?string $buildContext = null): array
    {
        $build = $this->getLatestBuild($entityId, $buildContext);
        if (! $build) {
            return [];
        }

        return $this->blockReconstructor->reconstruct($build);
    }

    public function deactivateBuildsForContext(int $entityId, string $buildContext): void
    {
        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->update(['is_active' => false]);
    }

    public function hasActiveBuildInContext(int $entityId, string $buildContext): bool
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->where('is_active', true)
            ->exists();
    }
}
