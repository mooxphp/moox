<?php

declare(strict_types=1);

namespace Moox\Definition\Projection;

final class UnsupportedProjection extends \RuntimeException
{
    public static function field(string $layer, string $field, string $type): self
    {
        return new self("Field [{$field}] of type [{$type}] is not supported by the {$layer} projection.");
    }

    public static function relation(string $layer, string $relation, string $type): self
    {
        return new self("Relation [{$relation}] of type [{$type}] is not supported by the {$layer} projection.");
    }

    public static function generator(string $layer, string $field, string $generator): self
    {
        return new self("Generator [{$generator}] for field [{$field}] is not supported by the {$layer} projection.");
    }
}
