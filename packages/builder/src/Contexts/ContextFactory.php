<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class ContextFactory
{
    public static function create(
        string $context,
        string $entityName,
        array $config = []
    ): BuildContext {
        $contexts = config('builder.contexts', []);

        if (! isset($contexts[$context])) {
            throw new InvalidArgumentException("Invalid context type: {$context}");
        }

        $contextConfig = array_merge($contexts[$context], $config);

        return new BuildContext(
            $context,
            $entityName,
            $contextConfig
        );
    }
}
