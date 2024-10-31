<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Services;

use Moox\Builder\Builder\Contexts\BuildContext;
use Moox\Builder\Builder\Generators\MigrationGenerator;
use Moox\Builder\Builder\Generators\ModelGenerator;
use Moox\Builder\Builder\Generators\PluginGenerator;
use Moox\Builder\Builder\Generators\ResourceGenerator;

class EntityGenerator extends AbstractService
{
    public function __construct(
        BuildContext $context,
        private readonly array $blocks,
        private readonly array $features
    ) {
        parent::__construct($context);
    }

    public function execute(): void
    {
        $this->generateMigration();
        $this->generateModel();
        $this->generateResource();
        $this->generatePlugin();
    }

    private function generateMigration(): void
    {
        (new MigrationGenerator($this->context))->generate();
    }

    private function generateModel(): void
    {
        (new ModelGenerator($this->context, $this->blocks, $this->features))->generate();
    }

    private function generateResource(): void
    {
        (new ResourceGenerator($this->context, $this->blocks, $this->features))->generate();
    }

    private function generatePlugin(): void
    {
        (new PluginGenerator($this->context))->generate();
    }
}
