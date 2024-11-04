<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class DateTimeType extends AbstractType
{
    protected array $availableFields = [
        'DateTimePicker',
        'DatePicker',
        'TimePicker',
    ];

    protected string $defaultField = 'DateTimePicker';

    protected string $databaseType = 'datetime';
}
