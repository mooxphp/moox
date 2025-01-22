<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Override;
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
        $this->validateBuildContext($buildContext);
        $this->validateEntityExists($entityId);
        $this->validateBlocks($blocks);
        $this->validateFiles($files);

        // TODO: This was a check to prevent conflicts in the production contexts, but it also prevents the preview from being used.
        // if ($this->hasConflictingProductionBuild($entityId, $buildContext)) {
        //     throw new RuntimeException('Entity already has an active build in a different production context');
        // }

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

    protected function validateBuildContext(string $context): void
    {
        if (! in_array($context, ['preview', 'app', 'package'])) {
            throw new RuntimeException('Invalid build context');
        }
    }

    protected function validateEntityExists(int $entityId): void
    {
        if (! $this->buildRecorder->validateEntityExists($entityId)) {
            throw new RuntimeException(sprintf('Entity %d not found', $entityId));
        }
    }

    protected function hasConflictingProductionBuild(int $entityId, string $newContext): bool
    {
        return $this->buildRecorder->hasConflictingProductionBuild($entityId, $newContext);
    }

    protected function validateFiles(array $files): void
    {
        if ($files === []) {
            throw new RuntimeException('No files provided for build recording');
        }

        foreach ($files as $type => $typeFiles) {
            if (! is_array($typeFiles)) {
                throw new RuntimeException('Invalid file structure for type: ' . $type);
            }

            foreach ($typeFiles as $path => $content) {
                if (! is_string($path)) {
                    throw new RuntimeException('Invalid path in type ' . $type);
                }

                if (! is_string($content)) {
                    throw new RuntimeException(sprintf('Invalid content for path %s in type %s', $path, $type));
                }
            }
        }
    }

    protected function validateBlocks(array $blocks): void
    {
        if ($blocks === []) {
            throw new RuntimeException('Blocks array cannot be empty');
        }

        foreach ($blocks as $block) {
            if (! method_exists($block, 'getOptions') || ! method_exists($block, 'getMigrations')) {
                throw new RuntimeException('Invalid block object: missing required methods');
            }

            if (! method_exists($block, 'getTitle') || ! method_exists($block, 'getDescription')) {
                throw new RuntimeException('Invalid block object: missing title or description methods');
            }
        }
    }

    #[Override]
    protected function validateContextConfig(): void
    {
        $config = $this->context->getConfig();
        $contextType = $this->context->getContextType();

        if (! isset($config['base_path'], $config['base_namespace'], $config['generators'])) {
            throw new RuntimeException(
                'Missing required configuration for context ' . $contextType
            );
        }

        foreach ($config['generators'] as $type => $genConfig) {
            if (! isset($genConfig['path'], $genConfig['namespace'])) {
                throw new RuntimeException(
                    sprintf('Invalid generator configuration for %s in context %s', $type, $contextType)
                );
            }
        }

        if ($contextType === 'package') {
            $this->validatePackageConfig($config);
        }
    }
}
