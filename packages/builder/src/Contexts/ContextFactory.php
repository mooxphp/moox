<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class ContextFactory
{
    public static function create(
        string $contextType,
        string $entityName,
        ?string $packageNamespace = null
    ): BuildContext {
        $contexts = config('builder.contexts', []);

        if (! isset($contexts[$contextType])) {
            throw new InvalidArgumentException("Invalid context type: {$contextType}");
        }

        return new BuildContext(
            contextType: $contextType,
            entityName: $entityName,
            packageNamespace: $packageNamespace
        );
    }
}
