<?php

declare(strict_types=1);

namespace Moox\Builder\Storage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;

interface ValueStore
{
    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function load(string $entity, Model $record, Collection $fields): array;

    /**
     * @param  array<string, mixed>  $values
     * @param  Collection<int, FieldDefinition>  $fields
     */
    public function save(string $entity, Model $record, array $values, Collection $fields): void;
}
