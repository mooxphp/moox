<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Moox\Builder\Services\Block\BlockReconstructor;
use Moox\Builder\Services\ContextAwareService;
use RuntimeException;

class BuildManager extends ContextAwareService
{
    public function __construct(
        private readonly BuildRecorder $buildRecorder,
        private readonly BlockReconstructor $blockReconstructor,
        private readonly BuildStateManager $buildStateManager
    ) {}

    public function execute(): void
    {
        $this->ensureContextIsSet();
    }

    public function recordBuild(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        $this->ensureContextIsSet();
        $this->validateContext($buildContext);
        $this->validateFiles($files);
        $this->validateEntityExists($entityId);

        if ($this->hasConflictingProductionBuild($entityId, $buildContext)) {
            throw new RuntimeException('Entity already has an active build in a different production context');
        }

        $this->buildRecorder->record($entityId, $buildContext, $blocks, $files);
        $this->buildStateManager->setContext($this->context);
        $this->buildStateManager->execute();
        $this->buildStateManager->updateState($files, $blocks);
    }

    public function reconstructFromLatest(int $entityId, ?string $buildContext = null): array
    {
        $latestBuild = $this->buildRecorder->getLatestBuild($entityId, $buildContext);

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
        if (! $this->buildRecorder->validateEntityExists($entityId)) {
            throw new RuntimeException("Entity {$entityId} not found");
        }
    }

    protected function hasConflictingProductionBuild(int $entityId, string $newContext): bool
    {
        return $this->buildRecorder->hasConflictingProductionBuild($entityId, $newContext);
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
