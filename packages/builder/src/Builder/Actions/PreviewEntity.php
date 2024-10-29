<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Actions;

class PreviewEntity
{
    protected string $entityName;

    protected string $entityNamespace;

    protected string $entityPath;

    public function __construct(
        string $entityName,
        string $entityNamespace,
        string $entityPath
    ) {
        $this->entityName = $entityName;
        $this->entityNamespace = $entityNamespace;
        $this->entityPath = $entityPath;
    }

    public function execute(): void
    {
        // Logic to create a panel for the entity
        // Logic to publish the migration of the entity
        // Logic to run the migration of the entity
        // Logic to enable the panel
    }
}
