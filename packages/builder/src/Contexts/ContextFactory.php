<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class ContextFactory
{
    public static function create(
        string $contextType,
        string $entityName,
        array $config = [],
        ?string $preset = null
    ): BuildContext {
        $contexts = config('builder.contexts', []);

        if (! isset($contexts[$contextType])) {
            throw new InvalidArgumentException("Invalid context type: {$contextType}");
        }

        $contextConfig = array_merge($contexts[$contextType], $config);

        return new BuildContext(
            $contextType,
            $contextConfig,
            [],
            $entityName,
            null,
            $preset
        );
    }
}
