<?php

declare(strict_types=1);

namespace Moox\Builder\Storage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\TypedValueColumns;

class TypedValueDriver implements ValueStore
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    public function load(string $entity, Model $record, Collection $fields): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $rows = FieldValue::query()
            ->forRecord($entity, $record->getKey())
            ->whereIn('field_name', $fields->pluck('name'))
            ->get()
            ->keyBy('field_name');

        $values = [];

        foreach ($fields as $field) {
            $row = $rows->get($field->name);

            if ($row === null) {
                continue;
            }

            $raw = TypedValueColumns::read($row, $field->type);
            $values[$field->name] = $this->fieldTypeRegistry->get($field->type)->castValue($raw);
        }

        return $values;
    }

    public function save(string $entity, Model $record, array $values, Collection $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field->name, $values)) {
                continue;
            }

            $cast = $this->fieldTypeRegistry->get($field->type)->castValue($values[$field->name]);
            $columns = TypedValueColumns::attributesFor($field->type, $cast);

            FieldValue::query()->updateOrCreate(
                [
                    'entity' => $entity,
                    'record_id' => $record->getKey(),
                    'field_name' => $field->name,
                ],
                $columns,
            );
        }
    }
}
