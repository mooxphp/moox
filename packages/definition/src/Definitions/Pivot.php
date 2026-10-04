<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class Pivot
{
    /**
     * @param  list<Field>  $additionalFields
     */
    public function __construct(
        private readonly string $table,
        private readonly string $foreignPivotKey,
        private readonly string $relatedPivotKey,
        private readonly array $additionalFields,
        private readonly bool $timestamps,
    ) {
    }

    public function table(): string
    {
        return $this->table;
    }

    public function foreignPivotKey(): string
    {
        return $this->foreignPivotKey;
    }

    public function relatedPivotKey(): string
    {
        return $this->relatedPivotKey;
    }

    /**
     * @return list<Field>
     */
    public function additionalFields(): array
    {
        return $this->additionalFields;
    }

    public function timestamps(): bool
    {
        return $this->timestamps;
    }
}
