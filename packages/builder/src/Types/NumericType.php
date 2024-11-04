<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class NumericType extends AbstractType
{
    protected array $availableFields = [
        'TextInput',
        'Number',
        'Range',
        'Select',
    ];

    protected string $defaultField = 'Number';

    protected string $databaseType = 'integer';
}
