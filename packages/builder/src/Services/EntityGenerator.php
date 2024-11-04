<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Generators\AbstractGenerator;
use RuntimeException;

class EntityGenerator extends AbstractService
{
    /** @var AbstractGenerator[] */
    protected array $generators = [];

    public function __construct(BuildContext $context, array $blocks = [], array $features = [])
    {
        parent::__construct($context, $blocks, $features);
    }

    protected function initializeGenerators(): void
    {
        if (! empty($this->generators)) {
            return;
        }

        $presetName = $this->context->getPresetName();
        if (empty($presetName)) {
            throw new RuntimeException('No preset name set in context');
        }

        $presetConfig = config('builder.presets.'.$presetName);
        if (! $presetConfig || ! isset($presetConfig['generators'])) {
            throw new RuntimeException("Invalid preset configuration for: {$presetName}");
        }

        foreach ($presetConfig['generators'] as $type) {
            $generatorConfig = config("builder.generators.{$type}");
            if (! $generatorConfig || ! isset($generatorConfig['class'])) {
                throw new RuntimeException("Invalid generator configuration for: {$type}");
            }

            $generatorClass = $generatorConfig['class'];
            $this->generators[] = new $generatorClass($this->context, $this->blocks);
        }
    }

    public function execute(): void
    {
        $this->initializeGenerators();

        if (empty($this->generators)) {
            throw new RuntimeException('No generators were initialized');
        }

        foreach ($this->generators as $generator) {
            $generator->generate();
        }

        foreach ($this->generators as $generator) {
            $generator->formatGeneratedFiles();
        }
    }
}
