<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Support\FieldRelationTree;
use Moox\Builder\Support\FieldValidationRules;
use Moox\Builder\Support\LocationConstraintOptions;
use Moox\Builder\Support\StorableFieldCollector;

class FieldGroupValidator
{
    public function __construct(
        protected FieldGroupPersistence $fieldGroupPersistence,
        protected StorableFieldCollector $storableFieldCollector,
        protected LocationConstraintOptions $locationConstraintOptions,
        protected FieldValidationRules $fieldValidationRules,
        protected ClonedFieldGroupResolver $clonedFieldGroupResolver,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validate(FieldGroup $group, array $data): void
    {
        $locationRules = $this->fieldGroupPersistence->resolveLocationRules($data);
        $entities = $this->fieldGroupPersistence->entitiesFromLocationRules($locationRules);
        $fieldRows = $data['fields'] ?? [];
        $groupSlug = (string) ($data['slug'] ?? $group->slug ?? '');
        $messages = array_merge(
            $this->rangeBoundsMessages($fieldRows),
            $this->validationRuleMessages($fieldRows),
            $this->cloneConfigMessages($groupSlug, $fieldRows),
            $this->locationConstraintMessages(
                is_array($data['location_constraints'] ?? null) ? $data['location_constraints'] : [],
                $data['target_entities'] ?? [],
            ),
        );

        if ($entities === [] || $fieldRows === []) {
            if ($messages !== []) {
                throw ValidationException::withMessages($messages);
            }

            return;
        }

        $entries = $this->storableFieldCollector->pathsFromRows($fieldRows);

        $internalMessages = $this->internalDuplicateMessages($entries);
        $externalMessages = $entities === [] ? [] : $this->externalConflictMessages($group, $entities, $entries);

        $messages = array_merge(
            $messages,
            $internalMessages,
            $externalMessages,
        );

        if ($externalMessages !== []) {
            $summary = collect($externalMessages)->flatten()->first();

            if (is_string($summary)) {
                $messages['target_entities'] = [$summary];
            }
        }

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

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, list<string>>
     */
    protected function rangeBoundsMessages(array $rows, string $prefix = 'fields'): array
    {
        $messages = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $basePath = "{$prefix}.{$index}";
            $type = (string) ($row['type'] ?? '');

            if ($type === 'range' && $this->rangeMaxIsNotGreaterThanMin(
                $row['config']['min'] ?? null,
                $row['config']['max'] ?? null,
            )) {
                $messages["{$basePath}.config.max"] = [
                    __('builder::builder.validation.range_max_gt_min'),
                ];
            }

            if ($type === 'range') {
                $default = $row['config']['default'] ?? null;

                if ($default !== null && $default !== '' && is_numeric($default) && ! app(DefaultValue::class)->rangeDefaultIsValid($default + 0, $row['config'] ?? [])) {
                    $messages["{$basePath}.config.default"] = [
                        __('builder::builder.validation.range_default_step'),
                    ];
                }
            }

            if ($type === 'tab') {
                $messages = array_merge(
                    $messages,
                    $this->rangeBoundsMessages($row['children'] ?? [], "{$basePath}.children"),
                );

                continue;
            }

            if ($type === 'flexible_content') {
                foreach ($row['layouts'] ?? [] as $layoutIndex => $layout) {
                    if (! is_array($layout)) {
                        continue;
                    }

                    $messages = array_merge(
                        $messages,
                        $this->rangeBoundsMessages(
                            $layout['children'] ?? [],
                            "{$basePath}.layouts.{$layoutIndex}.children",
                        ),
                    );
                }

                continue;
            }

            if (in_array($type, ['group', 'repeater'], true)) {
                $messages = array_merge(
                    $messages,
                    $this->rangeBoundsMessages($row['children'] ?? [], "{$basePath}.children"),
                );
            }
        }

        return $messages;
    }

    protected function rangeMaxIsNotGreaterThanMin(mixed $min, mixed $max): bool
    {
        if ($min === null || $min === '' || $max === null || $max === '') {
            return false;
        }

        if (! is_numeric($min) || ! is_numeric($max)) {
            return false;
        }

        return $max + 0 <= $min + 0;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, list<string>>
     */
    protected function validationRuleMessages(array $rows, string $prefix = 'fields'): array
    {
        $messages = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $basePath = "{$prefix}.{$index}";
            $type = (string) ($row['type'] ?? '');
            $validation = is_array($row['validation'] ?? null) ? $row['validation'] : [];
            $availableRules = $this->fieldValidationRules->availableRulesForType($type);

            foreach (($validation['rule_rows'] ?? []) as $ruleIndex => $ruleRow) {
                if (! is_array($ruleRow)) {
                    continue;
                }

                $rule = trim((string) ($ruleRow['rule'] ?? ''));
                $rulePath = "{$basePath}.validation.rule_rows.{$ruleIndex}";

                if ($rule === '' || ! array_key_exists($rule, $availableRules)) {
                    $messages["{$rulePath}.rule"] = [__('builder::builder.validation.invalid_option')];

                    continue;
                }

                if (! $this->fieldValidationRules->ruleNeedsValue($rule)) {
                    continue;
                }

                $value = trim((string) ($ruleRow['value'] ?? ''));

                if ($value === '') {
                    $messages["{$rulePath}.value"] = [__('builder::builder.validation.validation_rule_value_required')];

                    continue;
                }

                if ($this->fieldValidationRules->ruleExpectsNumericValue($type, $rule) && ! is_numeric($value)) {
                    $messages["{$rulePath}.value"] = [__('builder::builder.validation.validation_rule_value_numeric')];
                }
            }

            foreach ($this->fieldValidationRules->rawRulesFromText($validation['raw_rules'] ?? null) as $rawRuleIndex => $rawRule) {
                $message = $this->fieldValidationRules->validateRuleExpression($type, $rawRule);

                if ($message !== null) {
                    $messages["{$basePath}.validation.raw_rules"] = [$message];

                    break;
                }
            }

            if ($type === 'tab') {
                $messages = array_merge(
                    $messages,
                    $this->validationRuleMessages($row['children'] ?? [], "{$basePath}.children"),
                );

                continue;
            }

            if ($type === 'flexible_content') {
                foreach (($row['layouts'] ?? []) as $layoutIndex => $layout) {
                    if (! is_array($layout)) {
                        continue;
                    }

                    $messages = array_merge(
                        $messages,
                        $this->validationRuleMessages(
                            $layout['children'] ?? [],
                            "{$basePath}.layouts.{$layoutIndex}.children",
                        ),
                    );
                }

                continue;
            }

            if (in_array($type, ['group', 'repeater'], true)) {
                $messages = array_merge(
                    $messages,
                    $this->validationRuleMessages($row['children'] ?? [], "{$basePath}.children"),
                );
            }
        }

        return $messages;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, list<string>>
     */
    protected function locationConstraintMessages(array $rows, mixed $entities): array
    {
        $messages = [];
        $availableParams = array_keys($this->locationConstraintOptions->availableParamOptionsForEntities($entities));
        $availableOperators = ['==', '!=', 'in', 'not in'];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $param = (string) ($row['param'] ?? '');
            $operator = (string) ($row['operator'] ?? '==');
            $basePath = "location_constraints.{$index}";

            if (! in_array($param, $availableParams, true)) {
                $messages["{$basePath}.param"] = [__('builder::builder.validation.invalid_option')];

                continue;
            }

            if (! in_array($operator, $availableOperators, true)) {
                $messages["{$basePath}.operator"] = [__('builder::builder.validation.invalid_option')];
            }

            if ($param === 'taxonomy') {
                $taxonomy = (string) ($row['taxonomy'] ?? '');

                if ($taxonomy === '' || ! array_key_exists($taxonomy, $this->locationConstraintOptions->taxonomyKeysForEntities($entities))) {
                    $messages["{$basePath}.taxonomy"] = [__('builder::builder.validation.invalid_option')];

                    continue;
                }

                $valueMessages = $this->invalidConstraintValueMessages(
                    Arr::wrap($row['value'] ?? null),
                    array_keys($this->locationConstraintOptions->termOptionsForTaxonomy($taxonomy, $entities)),
                );

                if ($valueMessages !== []) {
                    $messages["{$basePath}.value"] = $valueMessages;
                }

                continue;
            }

            if ($param === 'record_type') {
                $valueMessages = $this->invalidConstraintValueMessages(
                    Arr::wrap($row['value'] ?? null),
                    array_keys($this->locationConstraintOptions->recordTypeOptionsForEntities($entities)),
                );

                if ($valueMessages !== []) {
                    $messages["{$basePath}.value"] = $valueMessages;
                }

                continue;
            }

            if ($param === 'record_status') {
                $valueMessages = $this->invalidConstraintValueMessages(
                    Arr::wrap($row['value'] ?? null),
                    array_keys($this->locationConstraintOptions->recordStatusOptionsForEntities($entities)),
                );

                if ($valueMessages !== []) {
                    $messages["{$basePath}.value"] = $valueMessages;
                }

                continue;
            }

            if ($param === 'user_role') {
                if (! $this->locationConstraintOptions->supportsUserRoles()) {
                    $messages["{$basePath}.value"] = [
                        $this->locationConstraintOptions->userRoleUnavailableReason()
                            ?? __('builder::builder.validation.invalid_option'),
                    ];

                    continue;
                }

                $valueMessages = $this->invalidConstraintValueMessages(
                    Arr::wrap($row['value'] ?? null),
                    array_keys($this->locationConstraintOptions->roleOptions()),
                );

                if ($valueMessages !== []) {
                    $messages["{$basePath}.value"] = $valueMessages;
                }
            }
        }

        return $messages;
    }

    /**
     * @param  list<mixed>  $values
     * @param  list<string|int>  $allowed
     * @return list<string>
     */
    protected function invalidConstraintValueMessages(array $values, array $allowed): array
    {
        $normalizedValues = array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), $values),
            static fn (string $value): bool => $value !== '',
        ));

        if ($normalizedValues === []) {
            return [];
        }

        $allowedLookup = array_fill_keys(array_map(static fn (mixed $value): string => (string) $value, $allowed), true);

        foreach ($normalizedValues as $value) {
            if (! isset($allowedLookup[$value])) {
                return [__('builder::builder.validation.invalid_option')];
            }
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, list<string>>
     */
    protected function cloneConfigMessages(string $groupSlug, array $rows, string $prefix = 'fields'): array
    {
        $messages = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $basePath = "{$prefix}.{$index}";
            $type = (string) ($row['type'] ?? '');

            if ($type === 'tab') {
                $messages = array_merge(
                    $messages,
                    $this->cloneConfigMessages($groupSlug, $row['children'] ?? [], "{$basePath}.children"),
                );

                continue;
            }

            if ($type === 'flexible_content') {
                foreach ($row['layouts'] ?? [] as $layoutIndex => $layout) {
                    if (! is_array($layout)) {
                        continue;
                    }

                    $messages = array_merge(
                        $messages,
                        $this->cloneConfigMessages(
                            $groupSlug,
                            $layout['children'] ?? [],
                            "{$basePath}.layouts.{$layoutIndex}.children",
                        ),
                    );
                }

                continue;
            }

            if (in_array($type, ['group', 'repeater'], true)) {
                $messages = array_merge(
                    $messages,
                    $this->cloneConfigMessages($groupSlug, $row['children'] ?? [], "{$basePath}.children"),
                );

                continue;
            }

            if ($type !== 'clone') {
                continue;
            }

            $targetSlug = trim((string) (is_array($row['config'] ?? null) ? ($row['config']['field_group_slug'] ?? '') : ''));

            if ($targetSlug === '') {
                $messages["{$basePath}.config.field_group_slug"] = [
                    __('builder::builder.validation.clone_field_group_required'),
                ];

                continue;
            }

            if ($groupSlug !== '' && $targetSlug === $groupSlug) {
                $messages["{$basePath}.config.field_group_slug"] = [
                    __('builder::builder.validation.clone_field_group_self'),
                ];

                continue;
            }

            if (! $this->clonedFieldGroupResolver->isActiveSlug($targetSlug)) {
                $messages["{$basePath}.config.field_group_slug"] = [
                    __('builder::builder.validation.clone_field_group_missing', ['slug' => $targetSlug]),
                ];
            }
        }

        return $messages;
    }
}
