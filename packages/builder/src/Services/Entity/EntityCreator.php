<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Build\BuildManager;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Services\Preview\PreviewTableManager;

class EntityCreator extends AbstractEntityService
{
    protected array $entityData = [];

    protected array $blocks = [];

    public function __construct(
        private readonly EntityGenerator $entityGenerator,
        private readonly BuildManager $buildManager,
        private readonly FileManager $fileManager,
        private readonly PreviewTableManager $previewTableManager
    ) {}

    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
        $this->entityGenerator->setBlocks($blocks);
    }

    public function setEntityData(array $data): void
    {
        $this->entityData = $data;
    }

    public function execute(): void
    {
        $this->ensureContextIsSet();
        $entityId = $this->createOrUpdateEntity();
        $contextType = $this->context->getContextType();

        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $contextType)
            ->update(['is_active' => false]);

        $this->fileManager->cleanupBeforeRegeneration($entityId, $contextType);

        if ($contextType === 'preview') {
            $this->previewTableManager->createTable($this->context->getEntityName(), $this->blocks);
        }

        $this->entityGenerator->setContext($this->context);
        $generatedData = $this->entityGenerator->execute();

        $this->buildManager->setContext($this->context);
        $this->buildManager->recordBuild(
            $entityId,
            $contextType,
            $this->blocks,
            $generatedData['files'] ?? []
        );
    }

    protected function createOrUpdateEntity(): int
    {
        $name = $this->context->getEntityName();
        $existingEntity = DB::table('builder_entities')
            ->where('singular', $name)
            ->first();

        if ($existingEntity) {
            return $existingEntity->id;
        }

        return DB::table('builder_entities')->insertGetId([
            'singular' => $name,
            'plural' => $this->entityData['plural'] ?? $name,
            'description' => $this->entityData['description'] ?? null,
            'preset' => $this->entityData['preset'] ?? 'simple-item',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
