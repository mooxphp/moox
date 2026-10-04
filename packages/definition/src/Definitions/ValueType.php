<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum ValueType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Enum = 'enum';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
}
