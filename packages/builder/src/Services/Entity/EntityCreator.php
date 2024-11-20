<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Build\BuildManager;
use Moox\Builder\Services\Preview\PreviewTableManager;
use RuntimeException;

class EntityCreator extends AbstractEntityService
{
    protected array $entityData = [];

    protected array $blocks = [];

    public function __construct(
        private readonly EntityGenerator $entityGenerator,
        private readonly BuildManager $buildManager,
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
        try {
            $this->ensureContextIsSet();
            $entityId = $this->createOrUpdateEntity();
            $contextType = $this->context->getContextType();

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
        } catch (RuntimeException $e) {
            throw new RuntimeException('Failed to create entity: '.$e->getMessage());
        }
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
