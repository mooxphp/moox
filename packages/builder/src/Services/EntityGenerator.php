<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Generators\AbstractGenerator;

class EntityGenerator extends AbstractService
{
    /** @var AbstractGenerator[] */
    protected array $generators = [];

    public function execute(): void
    {
        foreach ($this->generators as $generator) {
            $generator->generate();
        }

        foreach ($this->generators as $generator) {
            $generator->formatGeneratedFiles();
        }
    }
}
