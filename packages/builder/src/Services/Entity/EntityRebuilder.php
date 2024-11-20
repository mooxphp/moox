<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Services\Build\BuildManager;
use Moox\Builder\Services\File\FileManager;
use RuntimeException;

class EntityRebuilder extends AbstractEntityService
{
    protected int $entityId;

    protected array $blocks = [];

    public function __construct(
        private readonly FileManager $fileManager,
        private readonly BuildManager $buildManager,
        private readonly EntityGenerator $entityGenerator
    ) {}

    public function setEntityId(int $entityId): void
    {
        $this->entityId = $entityId;
        $this->validateEntityExists($entityId);
    }

    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
        $this->entityGenerator->setBlocks($blocks);
    }

    public function execute(): void
    {
        $this->ensureContextIsSet();
        if (! isset($this->entityId)) {
            throw new RuntimeException('Entity ID must be set');
        }

        $contextType = $this->context->getContextType();
        $this->buildManager->setContext($this->context);
        $this->fileManager->deleteFiles($this->entityId, $contextType);

        $this->entityGenerator->setContext($this->context);
        $generatedData = $this->entityGenerator->execute();

        $this->buildManager->recordBuild(
            $this->entityId,
            $contextType,
            $this->blocks,
            $generatedData['files'] ?? []
        );
    }
}
