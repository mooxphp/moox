<?php

declare(strict_types=1);

namespace Moox\Builder\Compiler;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Types\GroupFieldType;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\OptionValueRules;
use Moox\Builder\Support\StorableFieldCollector;

class SchemaCompiler
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected CustomFieldsManager $customFieldsManager,
        protected StorableFieldCollector $storableFieldCollector,
    ) {}

    /**
     * @param  Collection<int, FieldGroupDefinition>  $fieldGroups
     * @param  class-string|null  $resourceClass
     * @return list<Section>
     */
    public function compile(Collection $fieldGroups, ?string $resourceClass = null): array
    {
        $entity = $resourceClass !== null
            ? $this->customFieldsManager->locationContextForResource($resourceClass)->entity
            : null;

        $storableFields = $entity !== null
            ? $this->storableFieldCollector->definitionsFromList(
                $fieldGroups->flatMap(fn (FieldGroupDefinition $group): Collection => $group->fields),
            )
            : collect();

        $sections = [];

        foreach ($fieldGroups as $group) {
            $components = $this->compileRootFields(
                $group->fields->sortBy(fn (FieldDefinition $field): int => $field->sort)->values(),
                $entity,
                $storableFields,
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
     * @return list<Component>
     */
    public function compileSubFields(Collection $fields, ?string $entity = null, ?Collection $storableFields = null): array
    {
        $storableFields ??= collect();

        return $fields
            ->sortBy(fn (FieldDefinition $field): int => $field->sort)
            ->values()
            ->map(fn (FieldDefinition $field): Component => $this->compileField($field, $entity, $storableFields))
            ->all();
    }

    public function buildGroupComponent(FieldDefinition $field, ?string $entity = null, ?Collection $storableFields = null): Component
    {
        $storableFields ??= collect();

        $component = \Filament\Schemas\Components\Fieldset::make($field->label)
            ->schema($this->compileSubFields($field->children, $entity, $storableFields))
            ->statePath($field->name)
            ->columns(1)
            ->columnSpanFull();

        if ($entity === null) {
            return $component;
        }

        $defaultValue = app(DefaultValue::class);

        return $component
            ->afterStateHydrated(function (Component $component, mixed $state, ?Model $record) use ($field, $entity, $storableFields, $defaultValue): void {
                $storedValue = null;

                if ($record?->exists) {
                    $values = $this->customFieldsManager->loadCachedValues(
                        $entity,
                        $record,
                        $storableFields,
                    );

                    if (array_key_exists($field->name, $values)) {
                        $storedValue = (new GroupFieldType)->normalizeForForm($values[$field->name]);
                    }
                }

                $flat = is_array($storedValue ?? $state) ? ($storedValue ?? $state) : [];

                if (array_is_list($flat) && isset($flat[0]) && is_array($flat[0])) {
                    $flat = $flat[0];
                }

                $component->state($defaultValue->mergeIntoData($field->children, $flat));
            })
            ->afterStateUpdated(function (Component $component) use ($field, $defaultValue): void {
                $state = $component->getState();

                if (! is_array($state)) {
                    return;
                }

                if (array_is_list($state) && isset($state[0]) && is_array($state[0])) {
                    $state = $state[0];
                }

                $merged = $defaultValue->mergeIntoData($field->children, $state);

                if ($merged != $state) {
                    $component->state($merged);
                }
            });
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  Collection<int, FieldDefinition>  $storableFields
     * @return list<Component>
     */
    protected function compileRootFields(Collection $fields, ?string $entity, Collection $storableFields): array
    {
        $components = [];
        $sorted = $fields->sortBy(fn (FieldDefinition $field): int => $field->sort)->values();
        $index = 0;

        while ($index < $sorted->count()) {
            $field = $sorted[$index];

            if ($field->type === 'tab') {
                $tabPanels = [];

                while ($index < $sorted->count() && $sorted[$index]->type === 'tab') {
                    $tabField = $sorted[$index];

                    if ($tabField->children->isNotEmpty()) {
                        $tabPanels[] = [
                            'label' => $tabField->label,
                            'fields' => $tabField->children,
                        ];
                    }

                    $index++;
                }

                if ($tabPanels !== []) {
                    $components[] = Tabs::make('custom_fields_tabs')
                        ->tabs(collect($tabPanels)->map(
                            fn (array $panel): Tab => Tab::make($panel['label'])
                                ->schema($this->compileSubFields($panel['fields'], $entity, $storableFields)),
                        )->all());
                }

                continue;
            }

            $components[] = $this->compileField($field, $entity, $storableFields);
            $index++;
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

        if ($field->type === 'tab') {
            $rules = [];

            foreach ($field->children as $child) {
                $rules = array_merge($rules, $this->rulesForFieldTree($child, $prefix));
            }

            return $rules;
        }

        if ($fieldType->hasSubFields()) {
            if ($field->type === 'flexible_content') {
                return $rules;
            }

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
     * @param  Collection<int, FieldDefinition>  $storableFields
     */
    protected function compileField(FieldDefinition $field, ?string $entity = null, ?Collection $storableFields = null): Component
    {
        $storableFields ??= collect();

        if ($field->type === 'group') {
            return $this->buildGroupComponent($field, $entity, $storableFields);
        }

        $fieldType = $this->fieldTypeRegistry->get($field->type);
        $component = $fieldType->formComponent($field);

        if (! $fieldType->storesValue() || $entity === null) {
            return $component;
        }

        $defaultValue = app(DefaultValue::class);

        return $component
            ->afterStateHydrated(function (Component $component, mixed $state, ?Model $record) use ($field, $entity, $fieldType, $storableFields, $defaultValue): void {
                if ($field->type === 'password') {
                    return;
                }

                $storedValue = null;

                if ($record?->exists) {
                    $values = $this->customFieldsManager->loadCachedValues(
                        $entity,
                        $record,
                        $storableFields,
                    );

                    if (array_key_exists($field->name, $values)) {
                        $storedValue = $values[$field->name];

                        if ($fieldType->hasSubFields() && method_exists($fieldType, 'normalizeForForm')) {
                            $storedValue = $fieldType->normalizeForForm($storedValue);
                        }
                    }
                }

                if ($fieldType->hasSubFields()) {
                    $valueToApply = $storedValue ?? $state;

                    if (is_array($valueToApply) && $valueToApply !== []) {
                        $this->applyCompoundState(
                            $component,
                            $field,
                            $defaultValue,
                            $defaultValue->normalizeCompoundState($valueToApply),
                            force: true,
                        );
                    }

                    return;
                }

                $valueToApply = $storedValue ?? $state;

                if ($defaultValue->shouldApplyDefault($valueToApply, $field->type)) {
                    $default = $defaultValue->resolveForField($field);

                    if ($default !== null) {
                        $component->state($default);
                    }
                } elseif ($storedValue !== null) {
                    $component->state($storedValue);
                }
            })
            ->afterStateUpdated(function (Component $component) use ($field, $fieldType, $defaultValue): void {
                if (! $fieldType->hasSubFields()) {
                    return;
                }

                $state = $component->getState();

                if (! is_array($state)) {
                    return;
                }

                if ($state === []) {
                    return;
                }

                $this->applyCompoundState(
                    $component,
                    $field,
                    $defaultValue,
                    $defaultValue->normalizeCompoundState($state),
                );
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $state
     */
    protected function applyCompoundState(
        Component $component,
        FieldDefinition $field,
        DefaultValue $defaultValue,
        array $state,
        bool $force = false,
    ): void {
        $merged = $defaultValue->mergeCompoundDefaults($field, $state);

        if (! $force && $merged == $state) {
            return;
        }

        $component->state($merged);

        if ($component instanceof Builder || $component instanceof Repeater) {
            $component->hydrateItems();
        }
    }
}
