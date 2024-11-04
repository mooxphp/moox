<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class UrlType extends AbstractType
{
    protected array $availableFields = [
        'TextInput',
        'Hidden',
    ];

    protected string $defaultField = 'TextInput';

    protected string $databaseType = 'string';
}
