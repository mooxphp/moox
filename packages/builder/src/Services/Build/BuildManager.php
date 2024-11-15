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

        $this->buildRecorder->record($entityId, $buildContext, $blocks, $files);
        $this->fileManager->recordFiles($entityId, $buildContext, $files);
    }

    public function getLatestBuild(int $entityId): ?object
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->first();
    }

    public function reconstructFromLatest(int $entityId): array
    {
        $build = $this->getLatestBuild($entityId);
        if (! $build) {
            return [];
        }

        return $this->blockReconstructor->reconstruct($build);
    }
}
