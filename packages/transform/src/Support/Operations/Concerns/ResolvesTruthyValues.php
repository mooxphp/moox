<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations\Concerns;

trait ResolvesTruthyValues
{
    protected function isTruthyValue(mixed $value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, ['1', 'true', 'yes', 'ja', 'y'], true);
    }
}
