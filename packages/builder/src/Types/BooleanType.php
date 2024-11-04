<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class BooleanType extends AbstractType
{
    protected array $availableFields = [
        'Toggle',
        'Checkbox',
        'Radio',
        'Select',
    ];

    protected string $defaultField = 'Toggle';

    protected string $databaseType = 'boolean';
}
