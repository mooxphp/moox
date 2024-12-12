<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity\Pages;

class ListPageGenerator extends AbstractPageGenerator
{
    protected function getPageType(): string
    {
        return 'List';
    }

    protected function getClassName(): string
    {
        return $this->getPageType().$this->context->getPluralName();
    }
}
