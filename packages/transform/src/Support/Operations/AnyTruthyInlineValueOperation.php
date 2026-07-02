<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

use Illuminate\Support\Arr;
use Moox\Transform\Support\Operations\Concerns\ResolvesPayloadPaths;
use Moox\Transform\Support\Operations\Concerns\ResolvesTruthyValues;

final class AnyTruthyInlineValueOperation implements PayloadAwareInlineValueOperation
{
    use ResolvesPayloadPaths;
    use ResolvesTruthyValues;

    private const string PREFIX = 'any_truthy:';

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
        $warnings[] = "Any-truthy operation requires source payload for destination field [{$destinationField}].";

        return false;
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
            $warnings[] = "Empty any_truthy operation for destination field [{$destinationField}].";

            return false;
        }

        foreach ($paths as $path) {
            if ($this->isTruthyValue(Arr::get($payload, $path))) {
                return true;
            }
        }

        return false;
    }

    public function hasResolvablePaths(array $payload, string $operationSegment): bool
    {
        return $this->hasResolvablePayloadPath(
            $payload,
            $this->parsePayloadPaths($operationSegment, self::PREFIX),
        );
    }
}
