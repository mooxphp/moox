<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Exceptions;

use RuntimeException;

class RelationResourceNotSetException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Current resource is not set. Call RelationService::forResource() first.');
    }
}
