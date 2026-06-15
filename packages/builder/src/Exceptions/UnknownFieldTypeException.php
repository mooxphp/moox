<?php

declare(strict_types=1);

namespace Moox\Builder\Exceptions;

use Exception;

class UnknownFieldTypeException extends Exception
{
    public static function forKey(string $key): self
    {
        return new self("Unknown builder field type [{$key}].");
    }
}
