<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\Log;
use Moox\Builder\Services\ContextAwareService;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Traits\ValidatesEntity;
use RuntimeException;

class EntityGenerator extends ContextAwareService
{
    use ValidatesEntity;

    protected array $generators = [];

    protected array $generatedFiles = [];

    protected array $generatedData = [];

    protected array $blocks = [];

    protected array $originalBlocks = [];

    protected array $generationResult = [];

    public function __construct(
        private readonly FileManager $fileManager,
        array $blocks = []
    ) {
        $this->blocks = $blocks;
    }

    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
        $this->originalBlocks = $blocks;
        $this->reinitializeGenerators();
    }

    public function execute(): void
    {
        Log::info('EntityGenerator: Starting execution');
        $this->initializeGenerators();
        $this->generateFiles();
        $this->prepareGenerationResult();
        Log::info('EntityGenerator: Execution completed', [
            'generatedFiles' => $this->generatedFiles,
            'generatorCount' => count($this->generators),
        ]);
    }

    protected function initializeGenerators(): void
    {
        $this->validateContext();
        $contextType = $this->context->getContextType();
        $contextConfig = config("builder.contexts.{$contextType}");

        Log::info('EntityGenerator: Initializing generators', [
            'contextType' => $contextType,
            'hasGeneratorConfig' => isset($contextConfig['generators']),
        ]);

        if (! isset($contextConfig['generators'])) {
            throw new RuntimeException("No generators configured for context {$contextType}");
        }

        foreach ($contextConfig['generators'] as $name => $config) {
            if (! isset($config['generator'])) {
                throw new RuntimeException("Generator class not specified for {$name}");
            }

            $generatorClass = $config['generator'];
            Log::info('EntityGenerator: Creating generator', [
                'name' => $name,
                'class' => $generatorClass,
            ]);

            $this->generators[$name] = new $generatorClass(
                $this->context,
                $this->fileManager,
                $this->blocks
            );
        }
    }

    protected function reinitializeGenerators(): void
    {
        if (! empty($this->generators)) {
            $this->initializeGenerators();
        }
    }

    protected function generateFiles(): void
    {
        Log::info('EntityGenerator: Starting file generation', [
            'generatorCount' => count($this->generators),
        ]);

        foreach ($this->generators as $name => $generator) {
            Log::info('EntityGenerator: Generating files with generator', [
                'generator' => get_class($generator),
            ]);

            $generator->generate();
            $this->generatedFiles = array_merge(
                $this->generatedFiles,
                $generator->getGeneratedFiles()
            );
        }

        Log::info('EntityGenerator: File generation completed', [
            'totalFiles' => count($this->generatedFiles),
        ]);
    }

    protected function prepareGenerationResult(): void
    {
        $this->generationResult = [
            'files' => $this->generatedFiles,
            'blocks' => $this->blocks,
        ];
    }

    public function getGenerationResult(): array
    {
        return $this->generationResult;
    }
}
