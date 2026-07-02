<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

use Illuminate\Support\Arr;
use Moox\Transform\Support\Operations\Concerns\ResolvesPayloadPaths;

final class CoalesceInlineValueOperation implements PayloadAwareInlineValueOperation
{
    use ResolvesPayloadPaths;

    private const string PREFIX = 'coalesce:';

    public function supports(string $operationSegment): bool
    {
        return str_starts_with(strtolower($operationSegment), self::PREFIX);
    }

    public function apply(
        mixed $value,
        string $operationSegment,
        string $destinationField,
        array &$warnings,
    ): mixed {
        $warnings[] = "Coalesce operation requires source payload for destination field [{$destinationField}].";

        return $value;
    }

    public function applyWithPayload(
        string $operationSegment,
        mixed $value,
        string $destinationField,
        array &$warnings,
        array $payload,
    ): mixed {
        $paths = $this->parsePayloadPaths($operationSegment, self::PREFIX);
        if ($paths === []) {
            $warnings[] = "Empty coalesce operation for destination field [{$destinationField}].";

            return null;
        }

        foreach ($paths as $path) {
            $candidate = Arr::get($payload, $path);
            if ($this->hasUsableValue($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function hasResolvablePaths(array $payload, string $operationSegment): bool
    {
        return $this->hasResolvablePayloadPath(
            $payload,
            $this->parsePayloadPaths($operationSegment, self::PREFIX),
        );
    }
}
