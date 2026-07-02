<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

interface PayloadAwareInlineValueOperation extends InlineValueOperation
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $warnings
     */
    public function applyWithPayload(
        string $operationSegment,
        mixed $value,
        string $destinationField,
        array &$warnings,
        array $payload,
    ): mixed;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hasResolvablePaths(array $payload, string $operationSegment): bool;
}
