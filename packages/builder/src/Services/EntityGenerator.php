<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Generators\Entity\AbstractGenerator;
use RuntimeException;

class EntityGenerator extends AbstractService
{
    /** @var AbstractGenerator[] */
    protected array $generators = [];

    /** @var array<string, string> */
    protected array $generatedFiles = [];

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

    protected function initializeGenerators(): void
    {
        $contextConfig = $this->context->getConfig();
        $classes = $contextConfig['classes'] ?? [];

        $this->generators = array_map(
            function ($type) use ($classes) {
                $generatorClass = $classes[$type]['generator'] ?? null;
                if (! $generatorClass) {
                    throw new RuntimeException("No generator configured for type: {$type}");
                }

                return new $generatorClass($this->context, $this->blocks);
            },
            array_keys($classes)
        );
    }

    public function execute(): void
    {
        if ($this->context->getCommand()) {
            $this->context->getCommand()->error('EntityGenerator: Starting execution');
        }

        $this->initializeGenerators();

        if (empty($this->generators)) {
            throw new RuntimeException('No generators were initialized');
        }

        if ($this->context->getCommand()) {
            $this->context->getCommand()->error('EntityGenerator: Generators initialized: '.count($this->generators));
            foreach ($this->generators as $generator) {
                $this->context->getCommand()->error('Generator found: '.get_class($generator));
            }
        }

        foreach ($this->generators as $generator) {
            if ($this->context->getCommand()) {
                $this->context->getCommand()->error('Running generator: '.get_class($generator));
            }
            $generator->generate();
            if (method_exists($generator, 'getGeneratedFiles')) {
                $this->generatedFiles = array_merge(
                    $this->generatedFiles,
                    $generator->getGeneratedFiles()
                );
            }
        }

        foreach ($this->generators as $generator) {
            $generator->formatGeneratedFiles();
        }

        if ($this->context->getCommand()) {
            $this->context->getCommand()->error('EntityGenerator: Execution complete');
        }
    }

    /**
     * @return array<string, string>
     */
    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }
}
