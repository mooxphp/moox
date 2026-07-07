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

        $matchValue = Arr::get($payload, $valuePath);
        if ($this->isEmptyLookupValue($matchValue)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $id = $modelClass::query()
            ->where($matchColumn, $matchValue)
            ->value($modelClass::query()->getModel()->getKeyName());

        if ($id === null) {
            $warnings[] = "No [{$modelClass}] record found for [{$matchColumn}={$matchValue}].";
        }

        return $id;
    }

    public function hasResolvablePaths(array $payload, string $operationSegment): bool
    {
        [, , $valuePath] = $this->parseLookupConfig($operationSegment);

        return is_string($valuePath) && Arr::has($payload, $valuePath);
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
}
