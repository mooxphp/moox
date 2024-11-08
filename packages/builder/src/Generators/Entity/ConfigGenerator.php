<?php

namespace Moox\Builder\Generators\Entity;

class ConfigGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        // generate the config file
        // into /config/entities/{{ Entity }}.php
        // or /config/previews/{{ Entity }}.php
        // or package/config/entities/{{ Entity }}.php
    }

    protected function getGeneratorType(): string
    {
        return 'config';
    }
}
