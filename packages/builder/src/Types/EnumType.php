<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class EnumType extends AbstractType
{
    protected array $availableFields = [
        'Select',
        'Radio',
        'ToggleButtons',
    ];

    protected string $defaultField = 'Select';

    protected string $databaseType = 'string';
}
