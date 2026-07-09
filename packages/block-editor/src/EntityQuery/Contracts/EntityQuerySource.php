<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Contracts;

use Illuminate\Support\Collection;
use Moox\BlockEditor\EntityQuery\EntityQueryDefinition;

interface EntityQuerySource
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array<string, array{label: string, view: string}>
     */
    public function views(): array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function filterSchema(): array;

    /**
     * @return array<string, string>
     */
    public function sortableColumns(): array;

    public function defaultView(): string;

    public function query(EntityQueryDefinition $definition): Collection;

    /**
     * @return list<array{value: int|string, label: string}>
     */
    public function filterOptions(string $filter, string $locale): array;
}
