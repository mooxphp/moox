<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations\Concerns;

use Illuminate\Support\Arr;

trait ResolvesPayloadPaths
{
    /**
     * @return list<string>
     */
    protected function parsePayloadPaths(string $operationSegment, string $prefix): array
    {
        if (! str_starts_with(strtolower($operationSegment), strtolower($prefix))) {
            return [];
        }

        $config = substr($operationSegment, strlen($prefix));
        if ($config === false || $config === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $config)),
            static fn (string $path): bool => $path !== '',
        ));
    }

    /**
     * @param  list<string>  $paths
     */
    protected function hasResolvablePayloadPath(array $payload, array $paths): bool
    {
        foreach ($paths as $path) {
            if (Arr::has($payload, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function hasUsableValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }
}
