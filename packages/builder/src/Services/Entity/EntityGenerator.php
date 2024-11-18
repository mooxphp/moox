<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Services\File\FileManager;
use RuntimeException;

class EntityGenerator extends AbstractEntityService
{
    protected array $generators = [];

    protected array $generatedFiles = [];

    public function __construct(
        private readonly FileManager $fileManager
    ) {}

    public function setContext(BuildContext $context): void
    {
        parent::setContext($context);
        $this->generators = [];
    }

    public function setBlocks(array $blocks): void
    {
        parent::setBlocks($blocks);
        $this->generators = [];
    }

    public function execute(): void
    {
        $this->initializeGenerators();
        $this->runGenerators();
        $this->handleGeneratedFiles();
    }

    protected function initializeGenerators(): void
    {
        $contextConfig = $this->context->getConfig();
        $classes = $contextConfig['classes'] ?? [];

        foreach ($classes as $type => $config) {
            $generatorClass = $config['generator'] ?? null;
            if (! $generatorClass || ! class_exists($generatorClass)) {
                throw new RuntimeException("Invalid generator for type: {$type}");
            }

            $this->generators[$type] = new $generatorClass(
                $this->context,
                $this->fileManager,
                $this->blocks
            );
        }

        if (empty($this->generators)) {
            throw new RuntimeException('No generators were initialized');
        }
    }

    protected function runGenerators(): void
    {
        foreach ($this->generators as $type => $generator) {
            $generator->generate();
            $this->mergeGeneratedFiles($type, $generator->getGeneratedFiles());
        }
    }

    protected function mergeGeneratedFiles(string $type, array $files): void
    {
        foreach ($files as $path => $content) {
            $this->generatedFiles[$path] = $content;
        }
    }

    protected function handleGeneratedFiles(): void
    {
        if (! empty($this->generatedFiles)) {
            $this->fileManager->writeAndFormatFiles($this->generatedFiles);
        }
    }

    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }
}
