<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Moox\Transform\Support\Operations\Concerns\ResolvesPayloadPaths;

final class LookupModelIdInlineValueOperation implements PayloadAwareInlineValueOperation
{
    use ResolvesPayloadPaths;

    private const string PREFIX = 'lookup_id:';

    public function __construct(
        private readonly InlineLookupCache $lookupCache = new InlineLookupCache,
    ) {}

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
        $warnings[] = "Lookup operation requires source payload for destination field [{$destinationField}].";

        return null;
    }

    public function applyWithPayload(
        string $operationSegment,
        mixed $value,
        string $destinationField,
        array &$warnings,
        array $payload,
    ): mixed {
        [$modelClass, $matchColumn, $valuePath] = $this->parseLookupConfig($operationSegment);
        if ($modelClass === null || $matchColumn === null || $valuePath === null) {
            $warnings[] = "Invalid lookup_id operation for destination field [{$destinationField}].";

            return null;
        }

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            $warnings[] = "Lookup model [{$modelClass}] is not a valid Eloquent model.";

            return null;
        }

        $matchValue = $this->resolveLookupValue($payload, $valuePath, $destinationField, $warnings);
        if ($this->isEmptyLookupValue($matchValue)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $cacheKey = 'lookup_id:'.$modelClass.':'.$matchColumn.':'.json_encode($matchValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->lookupCache->remember($cacheKey, function () use ($modelClass, $matchColumn, $matchValue, &$warnings): mixed {
            $id = $modelClass::query()
                ->where($matchColumn, $matchValue)
                ->value($modelClass::query()->getModel()->getKeyName());

            if ($id === null) {
                $warnings[] = "No [{$modelClass}] record found for [{$matchColumn}={$matchValue}].";
            }

            return $id;
        });
    }

    public function hasResolvablePaths(array $payload, string $operationSegment): bool
    {
        [, , $valuePath] = $this->parseLookupConfig($operationSegment);

        if (! is_string($valuePath) || $valuePath === '') {
            return false;
        }

        $segments = array_values(array_filter(array_map('trim', explode('|', $valuePath))));
        $basePath = array_shift($segments);
        if (! is_string($basePath) || $basePath === '') {
            return false;
        }

        $registry = app(InlineOperationRegistry::class);

        return $registry->isPayloadBaseExpression($basePath)
            ? $registry->payloadBaseExpressionExists($payload, $basePath)
            : Arr::has($payload, $basePath);
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function parseLookupConfig(string $operationSegment): array
    {
        if (! str_starts_with(strtolower($operationSegment), self::PREFIX)) {
            return [null, null, null];
        }

        $config = substr($operationSegment, strlen(self::PREFIX));
        if ($config === false || $config === '') {
            return [null, null, null];
        }

        $parts = array_values(array_filter(
            array_map(trim(...), explode(',', $config)),
            static fn (string $part): bool => $part !== '',
        ));

        if (count($parts) !== 3) {
            return [null, null, null];
        }

        return [$parts[0], $parts[1], $parts[2]];
    }

    private function isEmptyLookupValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === false) {
            return true;
        }

        if (is_numeric($value) && (int) $value === 0) {
            return true;
        }

        return false;
    }

    private function resolveLookupValue(
        array $payload,
        string $valueExpression,
        string $destinationField,
        array &$warnings,
    ): mixed {
        $segments = array_values(array_filter(array_map('trim', explode('|', $valueExpression))));
        if ($segments === []) {
            return null;
        }

        $basePath = array_shift($segments);
        if (! is_string($basePath) || $basePath === '') {
            return null;
        }

        $registry = app(InlineOperationRegistry::class);
        $value = $registry->isPayloadBaseExpression($basePath)
            ? $registry->applyOperation($basePath, null, $destinationField, $warnings, $payload)
            : Arr::get($payload, $basePath);

        foreach ($segments as $operation) {
            $value = $registry->applyOperation($operation, $value, $destinationField, $warnings, $payload);
        }

        return $value;
    }
}
