<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\File\FileManager;
use RuntimeException;

class EntityDeleter extends AbstractEntityService
{
    public function __construct(
        private readonly FileManager $fileManager
    ) {}

    public function execute(): void
    {
        $this->ensureContextIsSet();
        $entity = $this->findEntity($this->context->getEntityName());

        if ($entity === null) {
            throw new RuntimeException('Entity not found');
        }

        $this->deleteEntityFiles($entity->id);
        $this->softDeleteEntity($entity->id);
    }

    protected function deleteEntityFiles(int $entityId): void
    {
        $this->fileManager->deleteFiles($entityId, $this->context->getContextType());
    }

    protected function softDeleteEntity(int $entityId): void
    {
        DB::table('builder_entities')
            ->where('id', $entityId)
            ->update(['deleted_at' => now()]);
    }

    protected function findEntity(string $name): ?object
    {
        return DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $this->context->getContextType())
            ->whereNull('deleted_at')
            ->first();
    }
}
