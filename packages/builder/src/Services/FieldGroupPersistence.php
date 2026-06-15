<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldOption;

class FieldGroupPersistence
{
    public function __construct(
        protected FieldValuePurger $fieldValuePurger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function sync(FieldGroup $group, array $data): void
    {
        app(FieldGroupValidator::class)->validate($group, $data);

        $group->fill(Arr::only($data, [
            'name',
            'slug',
            'active',
            'sort',
            'placement',
            'settings',
        ]));

        $group->location_rules = $this->resolveLocationRules($data);
        $group->save();

        $this->syncFields($group, $data['fields'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<array{param: string, operator: string, value: mixed}>>
     */
    public function resolveLocationRules(array $data): array
    {
        if (array_key_exists('target_entities', $data)) {
            return $this->locationRulesFromEntities($data['target_entities'] ?? []);
        }

        return $this->normalizeLocationRules($data['location_rules'] ?? []);
    }

    /**
     * @param  list<string>|string|null  $entities
     * @return list<list<array{param: string, operator: string, value: mixed}>>
     */
    public function locationRulesFromEntities(array|string|null $entities): array
    {
        $entities = is_array($entities) ? $entities : (filled($entities) ? [(string) $entities] : []);

        $rules = [];

        foreach ($entities as $entity) {
            if (blank($entity)) {
                continue;
            }

            $rules[] = [[
                'param' => 'entity',
                'operator' => '==',
                'value' => (string) $entity,
            ]];
        }

        return $rules;
    }

    /**
     * @param  list<list<array{param: string, operator: string, value: mixed}>>  $rules
     * @return list<string>
     */
    public function entitiesFromLocationRules(array $rules): array
    {
        $entities = [];

        foreach ($rules as $andGroup) {
            foreach ($andGroup as $rule) {
                if (($rule['param'] ?? null) !== 'entity') {
                    continue;
                }

                if (($rule['operator'] ?? '==') !== '==') {
                    continue;
                }

                if (filled($rule['value'] ?? null)) {
                    $entities[] = (string) $rule['value'];
                }
            }
        }

        return array_values(array_unique($entities));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<list<array{param: string, operator: string, value: mixed}>>
     */
    public function normalizeLocationRules(array $rows): array
    {
        $rules = [];

        foreach ($rows as $row) {
            if (blank($row['param'] ?? null)) {
                continue;
            }

            $rules[] = [[
                'param' => (string) $row['param'],
                'operator' => (string) ($row['operator'] ?? '=='),
                'value' => $row['value'] ?? null,
            ]];
        }

        return $rules;
    }

    /**
     * @param  list<list<array{param: string, operator: string, value: mixed}>>  $rules
     * @return list<array{param: string, operator: string, value: mixed}>
     */
    public function flattenLocationRulesForForm(array $rules): array
    {
        $rows = [];

        foreach ($rules as $andGroup) {
            foreach ($andGroup as $rule) {
                $rows[] = $rule;
            }
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function syncFields(FieldGroup $group, array $rows): void
    {
        $existing = $group->fields()->with('options')->get()->keyBy('id');
        $retainedIds = [];

        foreach ($rows as $index => $row) {
            if (blank($row['name'] ?? null) || blank($row['type'] ?? null)) {
                continue;
            }

            $fieldId = $row['id'] ?? null;
            $field = $fieldId ? $existing->get((int) $fieldId) : new Field;

            if (! $field instanceof Field) {
                $field = new Field;
            }

            $previousName = $field->exists ? $field->name : null;

            $field->fill([
                'field_group_id' => $group->getKey(),
                'name' => (string) $row['name'],
                'label' => (string) ($row['label'] ?? $row['name']),
                'type' => (string) $row['type'],
                'config' => $row['config'] ?? [],
                'validation' => [
                    'required' => (bool) ($row['required'] ?? false),
                    'rules' => $row['validation']['rules'] ?? [],
                ],
                'sort' => $index,
            ]);

            $field->save();

            if ($previousName !== null && $previousName !== $field->name) {
                $entities = $this->entitiesFromLocationRules($group->location_rules ?? []);
                $this->fieldValuePurger->purgeForFieldName($previousName, $entities);
            }

            $retainedIds[] = $field->getKey();

            $this->syncOptions($field, $row['options'] ?? []);
        }

        $group->fields()
            ->whereNotIn('id', $retainedIds)
            ->each(fn (Field $field) => $field->delete());
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function syncOptions(Field $field, array $rows): void
    {
        $existing = $field->options()->get()->keyBy('id');
        $retainedIds = [];

        foreach ($rows as $index => $row) {
            if (blank($row['value'] ?? null)) {
                continue;
            }

            $optionId = $row['id'] ?? null;
            $option = $optionId ? $existing->get((int) $optionId) : new FieldOption;

            if (! $option instanceof FieldOption) {
                $option = new FieldOption;
            }

            $option->fill([
                'field_id' => $field->getKey(),
                'label' => (string) ($row['label'] ?? $row['value']),
                'value' => (string) $row['value'],
                'sort' => (int) ($row['sort'] ?? $index),
            ]);

            $option->save();
            $retainedIds[] = $option->getKey();
        }

        $field->options()
            ->whereNotIn('id', $retainedIds)
            ->each(fn (FieldOption $option) => $option->delete());
    }

    public function slugFromName(string $name): string
    {
        return Str::slug($name);
    }
}
