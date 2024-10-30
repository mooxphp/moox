<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Actions;

use Moox\Builder\Builder\Generators\MigrationGenerator;
use Moox\Builder\Builder\Generators\ModelGenerator;
use Moox\Builder\Builder\Generators\PluginGenerator;
use Moox\Builder\Builder\Generators\ResourceGenerator;

class GenerateEntity
{
    protected string $entityName;

    protected string $entityNamespace;

    protected string $entityPath;

    protected array $blocks;

    protected array $features;

    public function __construct(
        string $entityName,
        string $entityNamespace,
        string $entityPath,
        array $blocks,
        array $features
    ) {
        $this->entityName = $entityName;
        $this->entityNamespace = $entityNamespace;
        $this->entityPath = $entityPath;
        $this->blocks = $blocks;
        $this->features = $features;
    }

    public function execute(): void
    {
        (new MigrationGenerator($this->entityName))->generate();
        (new ModelGenerator($this->entityName, $this->entityNamespace, $this->entityPath, $this->blocks, $this->features))->generate();
        (new ResourceGenerator($this->entityName, $this->entityNamespace, $this->entityPath, $this->blocks, $this->features))->generate();
        (new PluginGenerator($this->entityName, $this->entityNamespace, $this->entityPath))->generate();
    }
}
