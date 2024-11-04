<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use InvalidArgumentException;

class ContextFactory
{
    public static function create(string $contextType, string $entityName): BuildContext
    {
        $contexts = config('builder.contexts', []);
        $contextConfig = $contexts[$contextType] ?? null;

        if (! $contextConfig || ! isset($contextConfig['class'])) {
            throw new InvalidArgumentException("Invalid context type: {$contextType}");
        }

        $contextClass = $contextConfig['class'];

        if (! class_exists($contextClass) || ! is_subclass_of($contextClass, BuildContext::class)) {
            throw new InvalidArgumentException("Invalid context class: {$contextClass}");
        }

        return new $contextClass(
            entityName: $entityName,
            basePath: $contextConfig['base_path'] ?? '',
            baseNamespace: $contextConfig['base_namespace'] ?? '',
            paths: $contextConfig['paths'] ?? []
        );
    }
}
