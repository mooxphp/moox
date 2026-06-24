<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Support\FieldRelationTree;
use Moox\Builder\Support\StorableFieldCollector;

class FieldGroupValidator
{
    public function __construct(
        protected FieldGroupPersistence $fieldGroupPersistence,
        protected StorableFieldCollector $storableFieldCollector,
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

        $entries = $this->storableFieldCollector->pathsFromRows($fieldRows);

        $messages = array_merge(
            $this->internalDuplicateMessages($entries),
            $this->externalConflictMessages($group, $entities, $entries),
        );

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  list<array{name: string, path: string}>  $entries
     * @return array<string, list<string>>
     */
    protected function internalDuplicateMessages(array $entries): array
    {
        $seen = [];
        $messages = [];

        foreach ($entries as $entry) {
            if (isset($seen[$entry['name']])) {
                $messages[$entry['path']] = [
                    __('builder::builder.validation.duplicate_field_name_internal', ['name' => $entry['name']]),
                ];

                continue;
            }

            $seen[$entry['name']] = $entry['path'];
        }

        return $messages;
    }

    /**
     * @param  list<string>  $entities
     * @param  list<array{name: string, path: string}>  $entries
     * @return array<string, list<string>>
     */
    protected function externalConflictMessages(FieldGroup $group, array $entities, array $entries): array
    {
        if ($entries === []) {
            return [];
        }

        $currentNames = [];

        foreach ($entries as $entry) {
            $currentNames[$entry['name']] = $entry['path'];
        }

        $otherGroups = FieldGroup::query()
            ->active()
            ->when($group->exists, fn ($query) => $query->whereKeyNot($group->getKey()))
            ->get();

        $messages = [];

        foreach ($otherGroups as $otherGroup) {
            $otherEntities = $this->fieldGroupPersistence->entitiesFromLocationRules($otherGroup->location_rules ?? []);

            if (array_intersect($entities, $otherEntities) === []) {
                continue;
            }

            $otherGroup->load(FieldRelationTree::eagerLoadForDefinition());
            $otherNames = $this->storableFieldCollector->namesFromList(
                FieldGroupDefinition::fromModel($otherGroup)->fields,
            );

            foreach ($otherNames as $otherName) {
                if (! isset($currentNames[$otherName])) {
                    continue;
                }

                $path = $currentNames[$otherName];

                if (isset($messages[$path])) {
                    continue;
                }

                $messages[$path] = [
                    __('builder::builder.validation.duplicate_field_name', [
                        'name' => $otherName,
                        'group' => $otherGroup->name,
                    ]),
                ];
            }
        }

        return $messages;
    }
}
