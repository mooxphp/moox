<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

use Moox\Transform\Support\Operations\Concerns\ResolvesTruthyValues;

final class TruthyInlineValueOperation implements InlineValueOperation
{
    use ResolvesTruthyValues;

    public function supports(string $operationSegment): bool
    {
        return strtolower($operationSegment) === 'truthy';
    }

    public function apply(
        mixed $value,
        string $operationSegment,
        string $destinationField,
        array &$warnings,
    ): mixed {
        return $this->isTruthyValue($value);
    }
}
