<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Generators\AbstractGenerator;
use RuntimeException;

class EntityGenerator extends AbstractService
{
    public function execute(): void
    {
        $generators = $this->getGenerators();

        foreach ($generators as $generator) {
            $generatorClass = config("builder.generators.{$generator}.class");
            if (! $generatorClass || ! class_exists($generatorClass)) {
                continue;
            }

            if (! is_subclass_of($generatorClass, AbstractGenerator::class)) {
                throw new RuntimeException("Generator class {$generatorClass} must extend ".AbstractGenerator::class);
            }

            /** @var AbstractGenerator $generatorInstance */
            $generatorInstance = new $generatorClass(
                $this->context,
                $this->blocks,
                $this->features
            );
            $generatorInstance->generate();
        }
    }

    protected function getGenerators(): array
    {
        return ['model', 'migration', 'resource', 'plugin'];
    }
}
