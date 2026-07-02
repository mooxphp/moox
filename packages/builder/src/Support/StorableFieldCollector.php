<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Registry\FieldTypeRegistry;

final class StorableFieldCollector
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return Collection<int, FieldDefinition>
     */
    public function definitionsFromList(Collection $fields): Collection
    {
        return $fields->flatMap(fn (FieldDefinition $field): Collection => $this->definitionsFor($field))->values();
    }

    /**
     * @return Collection<int, FieldDefinition>
     */
    public function definitionsFor(FieldDefinition $field): Collection
    {
        if (in_array($field->type, ['tab', 'section'], true)) {
            return $this->definitionsFromList($field->children);
        }

        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if ($fieldType->isLayoutMarker() || ! $fieldType->storesValue()) {
            return collect();
        }

        if ($fieldType->hasSubFields()) {
            return collect([$field]);
        }

        return collect([$field]);
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return list<string>
     */
    public function namesFromList(Collection $fields): array
    {
        return $this->definitionsFromList($fields)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{name: string, path: string}>
     */
    public function pathsFromRows(array $rows, string $prefix = 'fields'): array
    {
        $entries = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = (string) ($row['type'] ?? '');
            $name = (string) ($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $path = "{$prefix}.{$index}.name";

            if (in_array($type, ['tab', 'section'], true)) {
                $entries = array_merge(
                    $entries,
                    $this->pathsFromRows($row['children'] ?? [], "{$prefix}.{$index}.children"),
                );

                continue;
            }

            if ($this->isCompoundStorageType($type)) {
                $entries[] = ['name' => $name, 'path' => $path];

                continue;
            }

            if (in_array($type, ['message', 'flexible_layout'], true)) {
                continue;
            }

            $entries[] = ['name' => $name, 'path' => $path];
        }

        return $entries;
    }

    protected function isCompoundStorageType(string $type): bool
    {
        return in_array($type, ['group', 'repeater', 'flexible_content'], true);
    }
}
