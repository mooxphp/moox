<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

use Illuminate\Support\Str;

final class TransliterateInlineValueOperation implements InlineValueOperation
{
    public function supports(string $operationSegment): bool
    {
        return strtolower($operationSegment) === 'transliterate';
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

        return Str::transliterate($value, '');
    }
}
