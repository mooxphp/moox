<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Services\File\FileManager;
use RuntimeException;

class EntityGenerator extends AbstractEntityService
{
    protected array $generators = [];

    protected array $generatedFiles = [];

    public function __construct(
        private readonly FileManager $fileManager
    ) {
        parent::__construct();
    }

    public function execute(): void
    {
        $this->ensureContextIsSet();
        $this->initializeGenerators();
        $this->runGenerators();
        $this->handleGeneratedFiles();
    }

    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    protected function initializeGenerators(): void
    {
        $contextConfig = $this->context->getConfig();
        $generators = $contextConfig['generators'] ?? [];

        if (empty($generators)) {
            throw new RuntimeException('No generators configured for context: '.$this->context->getContextType());
        }

        foreach ($generators as $generatorConfig) {
            $generatorClass = $generatorConfig['generator'] ?? null;

            if (! $generatorClass || ! is_string($generatorClass)) {
                throw new RuntimeException('Generator class not specified in config');
            }

            if (! class_exists($generatorClass)) {
                throw new RuntimeException("Generator class not found: {$generatorClass}");
            }

            $generator = new $generatorClass(
                $this->context,
                $this->fileManager,
                $this->blocks
            );
            $this->generators[] = $generator;
        }
    }

    protected function runGenerators(): void
    {
        foreach ($this->generators as $generator) {
            $generator->generate();
            $this->mergeGeneratedFiles($generator->getGeneratedFiles());
        }
    }

    protected function handleGeneratedFiles(): void
    {
        if (! empty($this->generatedFiles)) {
            $this->fileManager->writeAndFormatFiles($this->generatedFiles);
        }
    }

    protected function mergeGeneratedFiles(array $files): void
    {
        $this->generatedFiles = array_merge($this->generatedFiles, $files);
    }
}
