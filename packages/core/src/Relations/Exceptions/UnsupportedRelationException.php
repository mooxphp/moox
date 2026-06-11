<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Exceptions;

use InvalidArgumentException;
use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationPerspective;
use Moox\Core\Relations\Enums\RelationPresentation;

class UnsupportedRelationException extends InvalidArgumentException
{
    public static function forCombination(
        string $resource,
        string $key,
        RelationKind $kind,
        RelationPerspective $perspective,
        RelationPresentation $presentation,
    ): self {
        return new self(sprintf(
            'Relation [%s] on resource [%s] is not supported for kind [%s], perspective [%s], presentation [%s].',
            $key,
            $resource,
            $kind->value,
            $perspective->value,
            $presentation->value,
        ));
    }
}
