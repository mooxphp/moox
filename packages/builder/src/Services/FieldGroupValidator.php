<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Validation\ValidationException;
use Moox\Builder\Models\FieldGroup;

class FieldGroupValidator
{
    public function __construct(
        protected FieldGroupPersistence $fieldGroupPersistence,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(FieldGroup $group, array $data): void
    {
        $locationRules = $this->fieldGroupPersistence->resolveLocationRules($data);
        $entities = $this->fieldGroupPersistence->entitiesFromLocationRules($locationRules);
        $fieldRows = $data['fields'] ?? [];

        if ($entities === [] || $fieldRows === []) {
            return;
        }

        $messages = [];

        $messages = array_merge($messages, $this->internalDuplicateMessages($fieldRows));
        $messages = array_merge($messages, $this->externalConflictMessages($group, $entities, $fieldRows));

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $fieldRows
     * @return array<string, list<string>>
     */
    protected function internalDuplicateMessages(array $fieldRows): array
    {
        $seen = [];
        $messages = [];

        foreach ($fieldRows as $index => $row) {
            $name = (string) ($row['name'] ?? '');

            if ($name === '') {
                continue;
            }

            if (isset($seen[$name])) {
                $messages["fields.{$index}.name"] = [
                    __('builder::builder.validation.duplicate_field_name_internal', ['name' => $name]),
                ];

                continue;
            }

            $seen[$name] = $index;
        }

        return $messages;
    }

    /**
     * @param  list<string>  $entities
     * @param  list<array<string, mixed>>  $fieldRows
     * @return array<string, list<string>>
     */
    protected function externalConflictMessages(FieldGroup $group, array $entities, array $fieldRows): array
    {
        $namesByIndex = [];

        foreach ($fieldRows as $index => $row) {
            if (filled($row['name'] ?? null)) {
                $namesByIndex[$index] = (string) $row['name'];
            }
        }

        if ($namesByIndex === []) {
            return [];
        }

        $otherGroups = FieldGroup::query()
            ->active()
            ->when($group->exists, fn ($query) => $query->whereKeyNot($group->getKey()))
            ->with('fields')
            ->get();

        $messages = [];

        foreach ($otherGroups as $otherGroup) {
            $otherEntities = $this->fieldGroupPersistence->entitiesFromLocationRules($otherGroup->location_rules ?? []);

            if (array_intersect($entities, $otherEntities) === []) {
                continue;
            }

            foreach ($otherGroup->fields as $otherField) {
                foreach ($namesByIndex as $index => $name) {
                    if ($name !== $otherField->name) {
                        continue;
                    }

                    $messages["fields.{$index}.name"] = [
                        __('builder::builder.validation.duplicate_field_name', [
                            'name' => $name,
                            'group' => $otherGroup->name,
                        ]),
                    ];
                }
            }
        }

        return $messages;
    }
}
