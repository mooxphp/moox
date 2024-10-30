<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Traits;

trait HandlesPluralization
{
    protected function getPluralModelName(): string
    {
        $name = $this->entityName;
        if (substr($name, -1) === 'y') {
            return substr($name, 0, -1).'ies';
        }
        if (substr($name, -1) === 's') {
            return $name.'es';
        }

        return $name.'s';
    }
}
