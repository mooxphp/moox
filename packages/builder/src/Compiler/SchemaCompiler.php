<?php

declare(strict_types=1);

namespace Moox\Builder\Compiler;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Types\GroupFieldType;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\ConditionalLogic;
use Moox\Builder\Support\FieldWidth;
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
            $sortedFields = $group->fields->sortBy(fn (FieldDefinition $field): int => $field->sort)->values();
            $conditionalTriggers = $this->conditionalTriggerNames($sortedFields);

            $components = $this->compileRootFields(
                $sortedFields,
                $entity,
                $storableFields,
                $group->defaultColumnSpan(),
                $conditionalTriggers,
            );

            if ($components === []) {
                continue;
            }

            $groupScalarFields = $this->storableFieldCollector->definitionsFromList($group->fields);

            $section = Section::make($group->name)
                ->columns(FieldWidth::GRID_COLUMNS)
                ->schema($components);

            if ($entity !== null) {
                $section = $section->afterStateHydrated(
                    function (Component $component, mixed $state, ?Model $record) use ($groupScalarFields, $entity, $storableFields): void {
                        $this->applyScalarFieldDefaults($component, $groupScalarFields, $entity, $record, $storableFields);
                    },
                );
            }

            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return list<Component>
     */
    public function compileSubFields(Collection $fields, ?string $entity = null, ?Collection $storableFields = null, bool $insideTabs = false, ?int $defaultSpan = null, array $conditionalTriggers = []): array
    {
        $storableFields ??= collect();

        return $fields
            ->sortBy(fn (FieldDefinition $field): int => $field->sort)
            ->values()
            ->map(fn (FieldDefinition $field): Component => $this->compileField($field, $entity, $storableFields, $insideTabs, $defaultSpan, $conditionalTriggers))
            ->all();
    }

    /**
     * Visual section wrapper. Children store flat (like tabs); their defaults are
     * hydrated by the enclosing group/tab, so no own hydration is needed here.
     */
    public function buildSectionComponent(FieldDefinition $field, ?string $entity = null, ?Collection $storableFields = null, bool $insideTabs = false): Component
    {
        $storableFields ??= collect();

        return Section::make($field->label)
            ->columns(FieldWidth::GRID_COLUMNS)
            ->columnSpan($field->columnSpan())
            ->schema($this->compileSubFields($field->children, $entity, $storableFields, $insideTabs));
    }

    public function buildGroupComponent(FieldDefinition $field, ?string $entity = null, ?Collection $storableFields = null): Component
    {
        $storableFields ??= collect();

        $component = Fieldset::make($field->label)
            ->schema($this->compileSubFields($field->children, $entity, $storableFields))
            ->statePath($field->name)
            ->columns(FieldWidth::GRID_COLUMNS)
            ->columnSpan($field->columnSpan());

        if ($entity === null) {
            return $component;
        }

        $defaultValue = app(DefaultValue::class);

        return $component
            ->afterStateHydrated(function (Component $component, mixed $state, ?Model $record) use ($field, $entity, $storableFields, $defaultValue): void {
                $hasStoredValue = false;
                $storedValue = null;

                if ($record?->exists) {
                    $values = $this->customFieldsManager->loadCachedValues(
                        $entity,
                        $record,
                        $storableFields,
                    );

                    if (array_key_exists($field->name, $values)) {
                        $hasStoredValue = true;
                        $storedValue = (new GroupFieldType)->normalizeForForm($values[$field->name]);
                    }
                }

                $flat = $hasStoredValue
                    ? (is_array($storedValue) ? $storedValue : [])
                    : (is_array($state) ? $state : []);

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
    protected function compileRootFields(Collection $fields, ?string $entity, Collection $storableFields, int $defaultSpan = FieldWidth::GRID_COLUMNS, array $conditionalTriggers = []): array
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
                    $tabScalarFields = collect($tabPanels)
                        ->flatMap(fn (array $panel): Collection => $this->storableFieldCollector->definitionsFromList($panel['fields']))
                        ->values();

                    $tabs = Tabs::make('custom_fields_tabs')
                        ->columnSpanFull()
                        ->tabs(collect($tabPanels)->map(
                            fn (array $panel): Tab => Tab::make($panel['label'])
                                ->columns(FieldWidth::GRID_COLUMNS)
                                ->schema($this->compileSubFields($panel['fields'], $entity, $storableFields, insideTabs: true, defaultSpan: $defaultSpan, conditionalTriggers: $conditionalTriggers)),
                        )->all());

                    if ($entity !== null) {
                        $tabs = $tabs->afterStateHydrated(
                            function (Component $component, mixed $state, ?Model $record) use ($tabScalarFields, $entity, $storableFields): void {
                                $this->applyScalarFieldDefaults($component, $tabScalarFields, $entity, $record, $storableFields);
                            },
                        );
                    }

                    $components[] = $tabs;
                }

                continue;
            }

            $components[] = $this->compileField($field, $entity, $storableFields, defaultSpan: $defaultSpan, conditionalTriggers: $conditionalTriggers);
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

        if (in_array($field->type, ['tab', 'section'], true)) {
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
    protected function compileField(FieldDefinition $field, ?string $entity = null, ?Collection $storableFields = null, bool $insideTabs = false, ?int $defaultSpan = null, array $conditionalTriggers = []): Component
    {
        $storableFields ??= collect();

        if ($field->type === 'section') {
            return $this->buildSectionComponent($field, $entity, $storableFields, $insideTabs);
        }

        if ($field->type === 'group') {
            return $this->buildGroupComponent($field, $entity, $storableFields);
        }

        $fieldType = $this->fieldTypeRegistry->get($field->type);
        $component = $fieldType->formComponent($field);
        $component->columnSpan($field->columnSpan($defaultSpan));

        if (ConditionalLogic::isConfigured($field)) {
            $component->visible(fn (Get $get): bool => ConditionalLogic::passesForm($field, $get));

            if (($field->validation['required'] ?? false) === true) {
                $component->required(fn (Get $get): bool => ConditionalLogic::passesForm($field, $get));
            }
        }

        if (in_array($field->name, $conditionalTriggers, true) && $fieldType->storesValue()) {
            $component->live();
        }

        if ($insideTabs && $fieldType->storesValue()) {
            $component->dehydratedWhenHidden(true);
        }

        if (! $fieldType->storesValue() || $entity === null) {
            return $component;
        }

        $defaultValue = app(DefaultValue::class);

        return $component
            ->afterStateHydrated(function (Component $component, mixed $state, ?Model $record) use ($field, $entity, $fieldType, $storableFields, $defaultValue): void {
                $hasStoredValue = false;
                $storedValue = null;

                if ($record?->exists) {
                    $values = $this->customFieldsManager->loadCachedValues(
                        $entity,
                        $record,
                        $storableFields,
                    );

                    if (array_key_exists($field->name, $values)) {
                        $hasStoredValue = true;
                        $storedValue = $values[$field->name];

                        if (method_exists($fieldType, 'normalizeForForm')) {
                            $storedValue = $fieldType->normalizeForForm($storedValue);
                        }
                    }
                }

                if ($fieldType->hasSubFields()) {
                    $this->hydrateCompoundFieldState(
                        $component,
                        $field,
                        $defaultValue,
                        $hasStoredValue,
                        $storedValue,
                        $state,
                    );

                    return;
                }

                if ($hasStoredValue) {
                    $component->state(match ($field->type) {
                        'color' => $defaultValue->normalizeColorValue($storedValue) ?? $storedValue,
                        'time' => $defaultValue->normalizeTimeValue($storedValue) ?? $storedValue,
                        default => $storedValue,
                    });

                    return;
                }

                if ($defaultValue->hasConfiguredDefault($field)) {
                    $component->state($defaultValue->resolveForField($field));
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
     * @param  array<int, array<string, mixed>>|array<string, mixed>|null  $storedValue
     */
    protected function hydrateCompoundFieldState(
        Component $component,
        FieldDefinition $field,
        DefaultValue $defaultValue,
        bool $hasStoredValue,
        mixed $storedValue,
        mixed $state,
    ): void {
        if ($component instanceof Builder || $component instanceof Repeater) {
            $component->hydrateItems();
        }

        $valueToApply = $hasStoredValue ? $storedValue : $state;

        if (! is_array($valueToApply) || $valueToApply === []) {
            return;
        }

        $this->applyCompoundState(
            $component,
            $field,
            $defaultValue,
            $defaultValue->normalizeCompoundState($valueToApply),
            force: true,
        );
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

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  Collection<int, FieldDefinition>  $storableFields
     */
    protected function applyScalarFieldDefaults(
        Component $context,
        Collection $fields,
        string $entity,
        ?Model $record,
        Collection $storableFields,
    ): void {
        $defaultValue = app(DefaultValue::class);
        $stored = $record?->exists
            ? $this->customFieldsManager->loadCachedValues($entity, $record, $storableFields)
            : [];

        $root = $context->getRootContainer();

        foreach ($fields as $field) {
            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if (! $fieldType->storesValue() || $fieldType->hasSubFields()) {
                continue;
            }

            if (array_key_exists($field->name, $stored) && ! $defaultValue->shouldApplyDefault($stored[$field->name], $field->type)) {
                continue;
            }

            if (! $defaultValue->hasConfiguredDefault($field)) {
                continue;
            }

            $resolved = $defaultValue->resolveForField($field);

            if ($field->type === 'time') {
                $resolved = $defaultValue->normalizeTimeValue($resolved) ?? $resolved;
            }

            $fieldComponent = $root->getComponent($field->name);

            if ($fieldComponent === null) {
                continue;
            }

            if ($field->type !== 'toggle' && ! $defaultValue->shouldApplyDefault($fieldComponent->getState(), $field->type)) {
                continue;
            }

            $fieldComponent->state($resolved);

            if (method_exists($fieldComponent, 'partiallyRender')) {
                $fieldComponent->partiallyRender();
            }
        }
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return list<string>
     */
    protected function conditionalTriggerNames(Collection $fields): array
    {
        return $fields
            ->flatMap(fn (FieldDefinition $field): array => $field->conditionTriggers())
            ->unique()
            ->values()
            ->all();
    }
}
