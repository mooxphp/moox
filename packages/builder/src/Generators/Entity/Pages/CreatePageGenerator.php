<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity\Pages;

class CreatePageGenerator extends AbstractPageGenerator
{
    protected function getPageType(): string
    {
        return 'Create';
    }
}
