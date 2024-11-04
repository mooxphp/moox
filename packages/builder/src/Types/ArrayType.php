<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class ArrayType extends AbstractType
{
    protected array $availableFields = [
        'MultiSelect',
        'CheckboxList',
        'TagsInput',
        'Repeater',
    ];

    protected string $defaultField = 'MultiSelect';

    protected string $databaseType = 'json';
}
