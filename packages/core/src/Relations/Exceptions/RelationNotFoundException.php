<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Exceptions;

use InvalidArgumentException;

class RelationNotFoundException extends InvalidArgumentException
{
    public static function forKey(string $resource, string $key): self
    {
        return new self(sprintf('Relation [%s] is not defined for resource [%s].', $key, $resource));
    }
}
