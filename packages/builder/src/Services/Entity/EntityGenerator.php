<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Services\File\FileManager;
use RuntimeException;

class EntityGenerator extends AbstractEntityService
{
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

    public function execute(): array
    {
        $this->ensureContextIsSet();
        $this->initializeGenerators();
        $this->runGenerators();
        $this->handleGeneratedFiles();

        return [
            'files' => $this->generatedFiles,
            'data' => $this->generatedData,
        ];
    }

    protected function handleGeneratedFiles(): void
    {
        if (empty($this->generatedFiles)) {
            $this->generatedFiles = [];
            $this->generatedData = [];

            return;
        }

        $files = [];
        $data = [];
        foreach ($this->generatedFiles as $type => $typeFiles) {
            foreach ($typeFiles as $path => $content) {
                $files[$path] = $content;
                $data[$path] = [
                    'type' => $type,
                    'content' => $content,
                ];
            }
        }

        $this->generatedFiles = $files;
        $this->generatedData = $data;

        if (! empty($files)) {
            $this->fileManager->writeAndFormatFiles($files);
        }
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

    protected function mergeGeneratedFiles(array $files): void
    {
        foreach ($files as $type => $typeFiles) {
            if (! isset($this->generatedFiles[$type])) {
                $this->generatedFiles[$type] = [];
            }
            $this->generatedFiles[$type] = array_merge($this->generatedFiles[$type], $typeFiles);
        }
    }
}
