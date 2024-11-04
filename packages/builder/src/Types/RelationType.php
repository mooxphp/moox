<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class RelationType extends AbstractType
{
    protected array $availableFields = [
        'Select',
        'MultiSelect',
        'Repeater',
    ];

    protected string $defaultField = 'Select';

    protected string $databaseType = 'foreignId';
}
