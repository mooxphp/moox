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

    public function __construct(BuildContext $context, array $blocks = [])
    {
        parent::__construct($context, $blocks);
    }

    protected function initializeGenerators(): void
    {
        if (! empty($this->generators)) {
            return;
        }

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
