<?php

declare(strict_types=1);

namespace Moox\Definition\Projection;

enum StorageKind: string
{
    case BigIncrements = 'bigIncrements';
    case UnsignedBigInteger = 'unsignedBigInteger';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
    case String = 'string';
    case LongText = 'longText';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case BigInteger = 'bigInteger';
    case Decimal = 'decimal';
    case Double = 'double';
    case Date = 'date';
    case Timestamp = 'timestamp';
    case Time = 'time';
    case Json = 'json';
}
