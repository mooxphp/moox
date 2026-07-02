<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

use Moox\Transform\Support\Operations\Concerns\ResolvesTruthyValues;

final class NotTruthyInlineValueOperation implements InlineValueOperation
{
    use ResolvesTruthyValues;

    public function supports(string $operationSegment): bool
    {
        return in_array(strtolower($operationSegment), ['not_truthy', 'inverted_truthy'], true);
    }

    public function apply(
        mixed $value,
        string $operationSegment,
        string $destinationField,
        array &$warnings,
    ): mixed {
        return ! $this->isTruthyValue($value);
    }
}
