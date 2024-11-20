<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Generators\Entity\MigrationGenerator;
use Moox\Builder\Generators\Entity\ModelGenerator;
use Moox\Builder\Generators\Entity\PluginGenerator;
use Moox\Builder\Generators\Entity\ResourceGenerator;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Traits\ValidatesEntity;
use RuntimeException;

class EntityGenerator
{
    use ValidatesEntity;

    protected ?BuildContext $context = null;

    protected array $generators = [];

    protected array $generatedFiles = [];

    protected array $generatedData = [];

    protected array $blocks = [];

    public function __construct(
        private readonly FileManager $fileManager,
        array $blocks = []
    ) {
        $this->blocks = $blocks;
    }

    public function setContext(BuildContext $context): void
    {
        $this->context = $context;
    }

    protected function ensureContextIsSet(): void
    {
        if (! $this->context) {
            throw new RuntimeException('Context must be set before execution');
        }
    }

    protected function initializeGenerators(): void
    {
        $this->generators = [
            new ModelGenerator($this->context, $this->fileManager, $this->blocks),
            new ResourceGenerator($this->context, $this->fileManager, $this->blocks),
            new MigrationGenerator($this->context, $this->fileManager, $this->blocks),
            new PluginGenerator($this->context, $this->fileManager, $this->blocks),
        ];
    }

    protected function runGenerators(): void
    {
        foreach ($this->generators as $generator) {
            $generator->generate();
            $this->generatedFiles = array_merge($this->generatedFiles, $generator->getGeneratedFiles());
        }
    }

    public function execute(): array
    {
        $this->ensureContextIsSet();
        $this->initializeGenerators();
        $this->runGenerators();

        return [
            'files' => $this->generatedFiles,
            'data' => $this->generatedData,
        ];
    }

    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
    }
}
