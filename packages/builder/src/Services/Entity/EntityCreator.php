<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Build\BuildManager;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Services\Preview\PreviewTableManager;

class EntityCreator extends AbstractEntityService
{
    public function __construct(
        private readonly EntityGenerator $entityGenerator,
        private readonly BuildManager $buildManager,
        private readonly FileManager $fileManager,
        private readonly PreviewTableManager $previewTableManager
    ) {
        parent::__construct();
    }

    public function execute(): void
    {
        $this->ensureContextIsSet();
        $entityId = $this->createOrUpdateEntity();
        $contextType = $this->context->getContextType();
        $this->fileManager->cleanupBeforeRegeneration($entityId, $contextType);

        if ($contextType === 'preview') {
            $this->previewTableManager->createTable($this->context->getEntityName(), $this->blocks);
        }

        $this->entityGenerator->setContext($this->context);
        $this->entityGenerator->setBlocks($this->blocks);
        $this->entityGenerator->execute();

        $this->buildManager->recordBuild(
            $entityId,
            $contextType,
            $this->blocks,
            $this->entityGenerator->getGeneratedFiles()
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
