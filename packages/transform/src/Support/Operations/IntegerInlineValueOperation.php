<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

final class IntegerInlineValueOperation implements InlineValueOperation
{
    public function supports(string $operationSegment): bool
    {
        return in_array(strtolower($operationSegment), ['integer', 'int'], true);
    }

    public function apply(
        mixed $value,
        string $operationSegment,
        string $destinationField,
        array &$warnings,
    ): mixed {
        return is_numeric($value) ? (int) $value : null;
    }
}
