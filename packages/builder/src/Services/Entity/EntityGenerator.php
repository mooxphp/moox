<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Contexts\BuildContext;
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
        $this->ensureContextIsSet();
        $contextType = $this->context->getContextType();
        $contextConfig = config("builder.contexts.{$contextType}");

        if (! isset($contextConfig['generators'])) {
            throw new RuntimeException("No generators configured for context {$contextType}");
        }

        $this->generators = [];
        foreach ($contextConfig['generators'] as $type => $config) {
            if (! isset($config['generator'])) {
                continue;
            }

            $generatorClass = $config['generator'];
            $this->generators[] = new $generatorClass(
                $this->context,
                $this->fileManager,
                $this->blocks
            );
        }

        if (empty($this->generators)) {
            throw new RuntimeException("No valid generators found for context {$contextType}");
        }
    }

    protected function runGenerators(): void
    {
        foreach ($this->generators as $generator) {
            try {
                $generator->generate();
                $this->generatedFiles = array_merge($this->generatedFiles, $generator->getGeneratedFiles());
            } catch (RuntimeException $e) {
                throw new RuntimeException(
                    sprintf(
                        'Failed to run generator %s: %s',
                        get_class($generator),
                        $e->getMessage()
                    )
                );
            }
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
