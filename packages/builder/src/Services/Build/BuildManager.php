<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Block\BlockReconstructor;
use Moox\Builder\Services\ContextAwareService;
use RuntimeException;

class BuildManager extends ContextAwareService
{
    public function __construct(
        private readonly BuildRecorder $buildRecorder,
        private readonly BlockReconstructor $blockReconstructor,
        private readonly BuildStateManager $buildStateManager
    ) {
        parent::__construct();
    }

    public function execute(): void
    {
        $this->ensureContextIsSet();
    }

    public function recordBuild(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        $this->ensureContextIsSet();
        $this->validateContext($buildContext);
        $this->validateEntityExists($entityId);
        $this->validateFiles($files);

        if ($buildContext !== 'preview' && $this->hasConflictingProductionBuild($entityId, $buildContext)) {
            throw new RuntimeException('Entity already has an active build in a different production context');
        }

        $this->deactivateBuildsForContext($entityId, $buildContext);
        $this->buildRecorder->record($entityId, $buildContext, $blocks, $files);

        $this->buildStateManager->setContext($this->context);
        $this->buildStateManager->execute();
        $this->buildStateManager->updateState($files, $blocks);
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

    protected function validateFiles(array $files): void
    {
        if (empty($files)) {
            throw new RuntimeException('No files provided for build recording');
        }

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
