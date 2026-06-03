<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

final class MapInlineValueOperation implements InlineValueOperation
{
    public function supports(string $operationSegment): bool
    {
        return str_starts_with($operationSegment, 'map:');
    }

    public function apply(
        mixed $value,
        string $operationSegment,
        string $destinationField,
        array &$warnings,
    ): mixed {
        $mapConfig = substr($operationSegment, 4);
        if ($mapConfig === false) {
            $warnings[] = "Invalid map operation for destination field [{$destinationField}].";

            return $value;
        }

        $pairs = array_values(array_filter(array_map('trim', explode(',', $mapConfig))));
        if ($pairs === []) {
            $warnings[] = "Empty map operation for destination field [{$destinationField}].";

            return $value;
        }

        $map = [];
        $default = null;
        $hasDefault = false;

        foreach ($pairs as $pair) {
            if (! str_contains($pair, '=')) {
                $warnings[] = "Invalid map pair [{$pair}] for destination field [{$destinationField}].";

                continue;
            }

            [$rawKey, $rawMapped] = array_map('trim', explode('=', $pair, 2));
            $mappedValue = $rawMapped;

            if ($mappedValue === 'null') {
                $mappedValue = null;
            }

            if ($rawKey === '*') {
                $default = $mappedValue;
                $hasDefault = true;

                continue;
            }

            $map[$rawKey] = $mappedValue;
        }

        $lookupKey = $value === null ? 'null' : (string) $value;
        if (array_key_exists($lookupKey, $map)) {
            return $map[$lookupKey];
        }

        if ($hasDefault) {
            return $default;
        }

        $warnings[] = "No map match for value [{$lookupKey}] on destination field [{$destinationField}].";

        return $value;
    }
}
