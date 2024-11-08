<?php

namespace Moox\Builder\Generators\Entity;

class TranslationGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        // generate the translation file
        // into /resources/lang/en/entities/{{ Entity }}.php
        // or /resources/lang/en/previews/{{ Entity }}.php
        // or package/resources/lang/en/entities/{{ Entity }}.php
    }

    protected function getGeneratorType(): string
    {
        return 'translation';
    }
}
