<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Build\BuildManager;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Services\Preview\PreviewTableManager;
use RuntimeException;

class EntityCreator extends AbstractEntityService
{
    public function __construct(
        private readonly EntityGenerator $entityGenerator,
        private readonly BuildManager $buildManager,
        private readonly FileManager $fileManager,
        private readonly PreviewTableManager $previewTableManager
    ) {}

    public function execute(): void
    {
        if (! isset($this->context)) {
            throw new RuntimeException('Context not set');
        }

        $entityId = $this->createOrUpdateEntity();
        $this->fileManager->cleanupBeforeRegeneration($entityId, $this->context->getContext());

        if ($this->context->getContext() === 'preview') {
            $this->previewTableManager->createTable($this->context->getEntityName(), $this->blocks);
        }

        $this->entityGenerator->setContext($this->context);
        $this->entityGenerator->setBlocks($this->blocks);
        $this->entityGenerator->execute();

        $generatedFiles = $this->entityGenerator->getGeneratedFiles();

        $this->buildManager->recordBuild(
            $entityId,
            $this->context->getContext(),
            $this->blocks,
            $generatedFiles
        );
    }

    protected function createOrUpdateEntity(): int
    {
        $name = $this->context->getEntityName();
        $existingEntity = DB::table('builder_entities')
            ->where('name', $name)
            ->first();

        if ($existingEntity) {
            return $existingEntity->id;
        }

        return DB::table('builder_entities')->insertGetId([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
