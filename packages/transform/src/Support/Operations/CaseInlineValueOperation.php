<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

final class CaseInlineValueOperation implements InlineValueOperation
{
    public function supports(string $operationSegment): bool
    {
        return in_array(strtolower($operationSegment), ['upper', 'uppercase', 'lower', 'lowercase'], true);
    }

    public function apply(
        mixed $value,
        string $operationSegment,
        string $destinationField,
        array &$warnings,
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        return match (strtolower($operationSegment)) {
            'upper', 'uppercase' => strtoupper($value),
            'lower', 'lowercase' => strtolower($value),
            default => $value,
        };
    }
}
