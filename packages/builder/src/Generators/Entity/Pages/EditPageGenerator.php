<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity\Pages;

class EditPageGenerator extends AbstractPageGenerator
{
    protected function getPageType(): string
    {
        return 'Edit';
    }
}
