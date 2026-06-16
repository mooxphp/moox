<?php

declare(strict_types=1);

namespace Moox\Builder\Compiler;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\OptionValueRules;

class SchemaCompiler
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected CustomFieldsManager $customFieldsManager,
    ) {}

    /**
     * @param  Collection<int, FieldGroupDefinition>  $fieldGroups
     * @param  class-string|null  $resourceClass
     * @return list<Section>
     */
    public function compile(Collection $fieldGroups, ?string $resourceClass = null): array
    {
        $sections = [];

        foreach ($fieldGroups as $group) {
            $components = $group->fields
                ->sortBy(fn (FieldDefinition $field): int => $field->sort)
                ->values()
                ->map(fn (FieldDefinition $field): Component => $this->compileField($field, $resourceClass))
                ->all();

            if ($components === []) {
                continue;
            }

            $sections[] = Section::make($group->name)
                ->schema($components);
        }

        return $sections;
    }

    /**
     * @param  Collection<int, FieldGroupDefinition>  $fieldGroups
     * @return array<string, list<string>>
     */
    public function rules(Collection $fieldGroups): array
    {
        $rules = [];

        foreach ($fieldGroups as $group) {
            foreach ($group->fields as $field) {
                $fieldRules = $this->rulesForField($field);
                if ($fieldRules !== []) {
                    $rules[$field->name] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    protected function rulesForField(FieldDefinition $field): array
    {
        $fieldType = $this->fieldTypeRegistry->get($field->type);
        $rules = [];

        foreach ($fieldType->capabilities() as $capabilityClass) {
            $rules = array_merge($rules, app($capabilityClass)->rules($field));
        }

        if (($field->validation['required'] ?? false) === true) {
            $rules[] = 'required';
        }

        $rules = array_merge($rules, $field->validation['rules'] ?? []);

        if ($field->type === 'email') {
            $rules[] = 'email';
        }

        if ($field->type === 'url') {
            $rules[] = 'url';
        }

        if ($fieldType->hasOptions()) {
            $rules = array_merge($rules, OptionValueRules::forField($field));
        }

        return array_values(array_unique($rules, SORT_REGULAR));
    }

    /**
     * @param  class-string|null  $resourceClass
     */
    protected function compileField(FieldDefinition $field, ?string $resourceClass = null): Component
    {
        $fieldType = $this->fieldTypeRegistry->get($field->type);
        $component = $fieldType->formComponent($field);
        $entity = $resourceClass !== null
            ? $this->customFieldsManager->locationContextForResource($resourceClass)->entity
            : null;

        return $component->afterStateHydrated(function (Component $component, mixed $state, ?Model $record) use ($field, $entity): void {
            if ($field->type === 'password' || $record === null || $entity === null) {
                return;
            }

            $values = $this->customFieldsManager->loadValues(
                $entity,
                $record,
                collect([$field]),
            );

            if (array_key_exists($field->name, $values)) {
                $component->state($values[$field->name]);
            }
        });
    }
}
