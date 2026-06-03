<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

interface InlineValueOperation
{
    public function supports(string $operationSegment): bool;

    /**
     * Applies one inline operation (e.g. `map:`) to the current value.
     *
     * @param  array<int, string>  $warnings
     */
    public function apply(
        mixed $value,
        string $operationSegment,
        string $destinationField,
        array &$warnings,
    ): mixed;
}
