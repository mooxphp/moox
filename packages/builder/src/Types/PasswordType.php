<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class PasswordType extends AbstractType
{
    protected array $availableFields = [
        'TextInput',
    ];

    protected string $defaultField = 'TextInput';

    protected string $databaseType = 'string';
}
