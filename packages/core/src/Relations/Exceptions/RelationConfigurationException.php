<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Exceptions;

use InvalidArgumentException;

class RelationConfigurationException extends InvalidArgumentException
{
    public static function missing(string $resource, string $key, string $attribute): self
    {
        return new self(sprintf(
            'Relation [%s] on resource [%s] is missing required configuration [%s].',
            $key,
            $resource,
            $attribute,
        ));
    }

    public static function invalidModel(string $resource, string $key, string $model): self
    {
        return new self(sprintf(
            'Relation [%s] on resource [%s] references invalid model [%s].',
            $key,
            $resource,
            $model,
        ));
    }

    public static function invalidPresentation(string $resource, string $key, string $presentation): self
    {
        return new self(sprintf(
            'Relation [%s] on resource [%s] has invalid presentation [%s]. Allowed: tab, inline, hidden.',
            $key,
            $resource,
            $presentation,
        ));
    }
}
