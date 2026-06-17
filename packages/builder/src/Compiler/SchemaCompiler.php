<?php

declare(strict_types=1);

namespace Moox\Builder\Compiler;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
            $components = $this->compileRootFields(
                $group->fields->sortBy(fn (FieldDefinition $field): int => $field->sort)->values(),
                $resourceClass,
            );

            if ($components === []) {
                continue;
            }

            $sections[] = Section::make($group->name)
                ->schema($components);
        }

        return $sections;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  class-string|null  $resourceClass
     * @return list<Component>
     */
    public function compileSubFields(Collection $fields, ?string $resourceClass): array
    {
        return $fields
            ->sortBy(fn (FieldDefinition $field): int => $field->sort)
            ->values()
            ->map(fn (FieldDefinition $field): Component => $this->compileField($field, $resourceClass))
            ->all();
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  class-string|null  $resourceClass
     * @return list<Component>
     */
    protected function compileRootFields(Collection $fields, ?string $resourceClass): array
    {
        $components = [];
        $beforeTabs = [];
        $tabPanels = [];
        $tabPanelIndex = null;

        foreach ($fields as $field) {
            if ($field->type === 'tab') {
                $tabPanels[] = ['label' => $field->label, 'fields' => []];
                $tabPanelIndex = count($tabPanels) - 1;

                continue;
            }

            if ($tabPanelIndex !== null) {
                $tabPanels[$tabPanelIndex]['fields'][] = $field;

                continue;
            }

            $beforeTabs[] = $field;
        }

        foreach ($beforeTabs as $field) {
            $components[] = $this->compileField($field, $resourceClass);
        }

        if ($tabPanels !== []) {
            $components[] = Tabs::make('custom_fields_tabs')
                ->tabs(collect($tabPanels)->map(
                    fn (array $panel): Tab => Tab::make($panel['label'])
                        ->schema($this->compileSubFields(collect($panel['fields']), $resourceClass)),
                )->all());
        }

        return $components;
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
                $rules = array_merge($rules, $this->rulesForFieldTree($field));
            }
        }

        return $rules;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rulesForFieldTree(FieldDefinition $field, string $prefix = ''): array
    {
        $rules = [];
        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if ($fieldType->isLayoutMarker()) {
            return [];
        }

        if ($fieldType->hasSubFields()) {
            $childPrefix = $prefix === '' ? $field->name : "{$prefix}.{$field->name}";

            if ($field->type === 'repeater') {
                $childPrefix .= '.*';
            }

            foreach ($field->children as $child) {
                $rules = array_merge($rules, $this->rulesForFieldTree($child, $childPrefix));
            }

            return $rules;
        }

        $fieldRules = $this->rulesForField($field);

        if ($fieldRules !== []) {
            $key = $prefix === '' ? $field->name : "{$prefix}.{$field->name}";
            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    protected function rulesForField(FieldDefinition $field): array
    {
        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if (! $fieldType->storesValue()) {
            return [];
        }

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

        if (in_array($field->type, ['url', 'oembed'], true)) {
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

        if (! $fieldType->storesValue() || $entity === null) {
            return $component;
        }

        return $component->afterStateHydrated(function (Component $component, mixed $state, ?Model $record) use ($field, $entity, $fieldType): void {
            if ($record === null) {
                return;
            }

            if ($field->type === 'password') {
                return;
            }

            $values = $this->customFieldsManager->loadValues(
                $entity,
                $record,
                collect([$field]),
            );

            if (! array_key_exists($field->name, $values)) {
                return;
            }

            $value = $values[$field->name];

            if ($fieldType->hasSubFields() && method_exists($fieldType, 'normalizeForForm')) {
                $value = $fieldType->normalizeForForm($value);
            }

            $component->state($value);
        });
    }
}
