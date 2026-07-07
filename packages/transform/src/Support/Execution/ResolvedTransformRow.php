<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

final class ResolvedTransformRow
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $destinationClass
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $resolvedData
     * @param  array<string, mixed>  $destinationMatch
     * @param  array{references: list<array<string, mixed>>, primary_source_id: string|int|null}  $sourceContext
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly string $destinationClass,
        public readonly array $payload,
        public readonly array $resolvedData,
        public readonly array $destinationMatch,
        public readonly array $sourceContext,
        public readonly string $inputHash,
        public readonly array $warnings,
    ) {}
}
