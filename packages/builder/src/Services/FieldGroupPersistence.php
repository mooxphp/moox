<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Moox\Builder\FieldTypes\Capabilities\DisplayFormat;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldOption;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\ConditionalLogic;
use Moox\Builder\Support\DefinitionTranslator;
use Moox\Builder\Support\FieldRelationTree;

class FieldGroupPersistence
{
    public function __construct(
        protected FieldValuePurger $fieldValuePurger,
        protected BuilderLocaleResolver $localeResolver,
        protected DefinitionTranslator $definitionTranslator,
        protected EntityRegistry $entityRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function sync(FieldGroup $group, array $data): void
    {
        app(FieldGroupValidator::class)->validate($group, $data);

        $locale = $this->localeResolver->current();
        $isDefaultLocale = $locale === $this->localeResolver->defaultLocale();
        $name = (string) ($data['name'] ?? $group->name);

        $group->fill(Arr::only($data, [
            'slug',
            'active',
            'sort',
            'placement',
            'settings',
        ]));

        if ($isDefaultLocale || ! $group->exists || blank($group->name)) {
            $group->name = $name;
        }

        $group->location_rules = $this->resolveLocationRules($data);
        $group->save();

        $this->syncGroupTranslation($group, $name);

        $this->syncFields($group, $data['fields'] ?? []);
    }

    protected function syncGroupTranslation(FieldGroup $group, string $name): void
    {
        $locale = $this->localeResolver->current();

        $group->translateOrNew($locale)->name = $name;
        $group->saveTranslations();

        $group->syncTranslationFallbackOnMainRecord($locale, ['name' => $name]);
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
     * @return list<array<string, mixed>>
     */
    public function fieldRowsForForm(FieldGroup $group, ?string $locale = null): array
    {
        $group->load(FieldRelationTree::eagerLoadForDefinition());
        $group->load('translations');

        return $this->mapFieldRows($group->fields, $locale);
    }

    public function localizedGroupName(FieldGroup $group, ?string $locale = null): string
    {
        return $this->definitionTranslator->translatedGroupName($group, $locale);
    }

    /**
     * @param  Collection<int, Field>  $fields
     * @return list<array<string, mixed>>
     */
    protected function mapFieldRows(Collection $fields, ?string $locale = null): array
    {
        $locale = $this->localeResolver->current($locale);

        return $fields->map(function (Field $field) use ($locale): array {
            $row = [
                'id' => $field->getKey(),
                'label' => $this->definitionTranslator->translatedFieldLabel($field, $locale),
                'name' => $field->name,
                'type' => $field->type,
                'required' => (bool) ($field->validation['required'] ?? false),
                'config' => $this->definitionTranslator->translatedFieldConfig($field, $locale),
                'settings' => [
                    'show_in_table' => (bool) ($field->settings['show_in_table'] ?? false),
                    'sortable' => (bool) ($field->settings['sortable'] ?? true),
                    'searchable' => (bool) ($field->settings['searchable'] ?? true),
                    'hidden_by_default' => (bool) ($field->settings['hidden_by_default'] ?? true),
                    'badge' => (bool) ($field->settings['badge'] ?? false),
                    'color' => $field->settings['color'] ?? null,
                    'icon' => $field->settings['icon'] ?? null,
                    'image_shape' => $field->settings['image_shape'] ?? null,
                    'image_size' => $field->settings['image_size'] ?? null,
                    'visible_admin' => (bool) ($field->settings['visible_admin'] ?? true),
                    'visible_frontend' => (bool) ($field->settings['visible_frontend'] ?? true),
                    'visible_api' => (bool) ($field->settings['visible_api'] ?? true),
                    'width' => $field->settings['width'] ?? null,
                    'conditions' => ConditionalLogic::normalizeSettings($field->settings['conditions'] ?? []),
                ],
                'sort' => $field->sort,
                'options' => $field->options->map(fn (FieldOption $option): array => [
                    'id' => $option->getKey(),
                    'label' => $this->definitionTranslator->translatedOptionLabel($option, $locale),
                    'value' => $option->value,
                    'sort' => $option->sort,
                ])->values()->all(),
            ];

            if ($field->type === 'flexible_content') {
                $row['layouts'] = $this->mapLayoutRows($field->children, $locale);
            } else {
                $row['children'] = $this->mapFieldRows($field->children, $locale);
            }

            return $row;
        })->values()->all();
    }

    /**
     * @param  Collection<int, Field>  $fields
     * @return list<array<string, mixed>>
     */
    protected function mapLayoutRows(Collection $fields, ?string $locale = null): array
    {
        $locale = $this->localeResolver->current($locale);

        return $fields
            ->filter(fn (Field $field): bool => $field->type === 'flexible_layout')
            ->map(fn (Field $layout): array => [
                'id' => $layout->getKey(),
                'label' => $this->definitionTranslator->translatedFieldLabel($layout, $locale),
                'name' => $layout->name,
                'sort' => $layout->sort,
                'children' => $this->mapFieldRows($layout->children, $locale),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function syncFields(FieldGroup $group, array $rows, ?int $parentFieldId = null): void
    {
        $existingQuery = $group->fields()->with('options');

        if ($parentFieldId === null) {
            $existingQuery->whereNull('parent_field_id');
        } else {
            $existingQuery->where('parent_field_id', $parentFieldId);
        }

        $existing = $existingQuery->get()->keyBy('id');
        $retainedIds = [];

        foreach ($rows as $index => $row) {
            if (blank($row['name'] ?? null) || blank($row['type'] ?? null)) {
                continue;
            }

            $field = $this->resolveFieldForSync($existing, $row);

            $previousName = $field->exists ? $field->name : null;
            $locale = $this->localeResolver->current();
            $isDefaultLocale = $locale === $this->localeResolver->defaultLocale();
            $label = (string) ($row['label'] ?? $row['name']);
            $config = $this->filterConfigForType((string) $row['type'], $row['config'] ?? []);
            $structuralConfig = array_diff_key(
                $config,
                array_flip(BuilderLocaleResolver::TRANSLATABLE_CONFIG_KEYS),
            );

            $field->fill([
                'field_group_id' => $group->getKey(),
                'parent_field_id' => $parentFieldId,
                'name' => (string) $row['name'],
                'label' => $isDefaultLocale ? $label : ($field->label ?: $label),
                'type' => (string) $row['type'],
                // Structural config keys (e.g. related_entity, multiple) are
                // locale-independent and must always take the submitted value.
                // On a non-default locale only the translatable keys stored on
                // the main record are preserved as the default-locale fallback;
                // their localized values live in the translation row.
                'config' => $isDefaultLocale
                    ? $config
                    : array_merge(
                        array_intersect_key($field->config ?? [], array_flip(BuilderLocaleResolver::TRANSLATABLE_CONFIG_KEYS)),
                        $structuralConfig,
                    ),
                'validation' => [
                    'required' => (bool) ($row['required'] ?? false),
                    'rules' => $row['validation']['rules'] ?? [],
                ],
                'settings' => array_merge($field->settings ?? [], [
                    'show_in_table' => (bool) ($row['settings']['show_in_table'] ?? false),
                    'sortable' => (bool) ($row['settings']['sortable'] ?? true),
                    'searchable' => (bool) ($row['settings']['searchable'] ?? true),
                    'hidden_by_default' => (bool) ($row['settings']['hidden_by_default'] ?? true),
                    'badge' => (bool) ($row['settings']['badge'] ?? false),
                    'color' => filled($row['settings']['color'] ?? null) ? (string) $row['settings']['color'] : null,
                    'icon' => filled($row['settings']['icon'] ?? null) ? (string) $row['settings']['icon'] : null,
                    'image_shape' => filled($row['settings']['image_shape'] ?? null) ? (string) $row['settings']['image_shape'] : null,
                    'image_size' => filled($row['settings']['image_size'] ?? null) ? (string) $row['settings']['image_size'] : null,
                    'visible_admin' => (bool) ($row['settings']['visible_admin'] ?? true),
                    'visible_frontend' => (bool) ($row['settings']['visible_frontend'] ?? true),
                    'visible_api' => (bool) ($row['settings']['visible_api'] ?? true),
                    'width' => filled($row['settings']['width'] ?? null) ? (string) $row['settings']['width'] : null,
                    'conditions' => ConditionalLogic::normalizeSettings($row['settings']['conditions'] ?? []),
                ]),
                'sort' => $index,
            ]);

            $field->save();

            $this->syncFieldTranslation($field, $label, $config);

            $existing->put($field->getKey(), $field);

            if ($previousName !== null && $previousName !== $field->name) {
                $entities = $this->entitiesFromLocationRules($group->location_rules ?? []);

                if ($field->parent_field_id !== null) {
                    app(CompoundFieldValueMigrator::class)->renameNestedSubfield($field, $previousName, $entities);
                } else {
                    $this->fieldValuePurger->purgeForFieldName($previousName, $entities);
                }
            }

            $retainedIds[] = $field->getKey();

            $this->syncOptions($field, $row['options'] ?? []);

            if ((string) $row['type'] === 'flexible_content') {
                $layoutRows = array_map(
                    fn (array $layout): array => [
                        ...$layout,
                        'type' => 'flexible_layout',
                    ],
                    $row['layouts'] ?? [],
                );

                $this->syncFields($group, $layoutRows, $field->getKey());
            } elseif ($this->typeHasSubFields((string) $row['type'])) {
                $this->syncFields($group, $row['children'] ?? [], $field->getKey());
            } else {
                $field->children()->each(fn (Field $child) => $child->delete());
            }
        }

        $deleteQuery = $group->fields();

        if ($parentFieldId === null) {
            $deleteQuery->whereNull('parent_field_id');
        } else {
            $deleteQuery->where('parent_field_id', $parentFieldId);
        }

        $deleteQuery
            ->whereNotIn('id', $retainedIds)
            ->each(fn (Field $field) => $field->delete());
    }

    protected function typeHasSubFields(string $type): bool
    {
        return app(FieldTypeRegistry::class)->get($type)->hasSubFields();
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

            $option = $this->resolveOptionForSync($existing, $row);
            $locale = $this->localeResolver->current();
            $isDefaultLocale = $locale === $this->localeResolver->defaultLocale();
            $label = (string) ($row['label'] ?? $row['value']);

            $option->fill([
                'field_id' => $field->getKey(),
                'label' => $isDefaultLocale ? $label : ($option->label ?: $label),
                'value' => (string) $row['value'],
                'sort' => (int) ($row['sort'] ?? $index),
            ]);

            $option->save();

            $this->syncOptionTranslation($option, $label);

            $existing->put($option->getKey(), $option);
            $retainedIds[] = $option->getKey();
        }

        $field->options()
            ->whereNotIn('id', $retainedIds)
            ->each(fn (FieldOption $option) => $option->delete());
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function syncFieldTranslation(Field $field, string $label, array $config): void
    {
        $locale = $this->localeResolver->current();
        $translatableConfig = $this->definitionTranslator->extractTranslatableConfig(
            $this->filterConfigForType($field->type, $config),
        );

        $translation = $field->translateOrNew($locale);
        $translation->label = $label;
        $translation->config = $translatableConfig === [] ? null : $translatableConfig;
        $field->saveTranslations();

        $field->syncTranslationFallbackOnMainRecord($locale, [
            'label' => $label,
            'config' => $this->filterConfigForType($field->type, $config),
        ]);
    }

    protected function syncOptionTranslation(FieldOption $option, string $label): void
    {
        $locale = $this->localeResolver->current();

        $option->translateOrNew($locale)->label = $label;
        $option->saveTranslations();

        $option->syncTranslationFallbackOnMainRecord($locale, ['label' => $label]);
    }

    /**
     * @param  Collection<int, Field>  $existing
     * @param  array<string, mixed>  $row
     */
    protected function resolveFieldForSync(Collection $existing, array $row): Field
    {
        $fieldId = filled($row['id'] ?? null) ? (int) $row['id'] : null;

        if ($fieldId !== null && $existing->has($fieldId)) {
            return $existing->get($fieldId);
        }

        $name = (string) ($row['name'] ?? '');

        if ($name !== '') {
            $match = $existing->firstWhere('name', $name);

            if ($match instanceof Field) {
                return $match;
            }
        }

        return new Field;
    }

    /**
     * @param  Collection<int, FieldOption>  $existing
     * @param  array<string, mixed>  $row
     */
    protected function resolveOptionForSync(Collection $existing, array $row): FieldOption
    {
        $optionId = filled($row['id'] ?? null) ? (int) $row['id'] : null;

        if ($optionId !== null && $existing->has($optionId)) {
            return $existing->get($optionId);
        }

        $value = (string) ($row['value'] ?? '');

        if ($value !== '') {
            $match = $existing->firstWhere('value', $value);

            if ($match instanceof FieldOption) {
                return $match;
            }
        }

        return new FieldOption;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function filterConfigForType(string $type, array $config): array
    {
        if ($config === []) {
            return [];
        }

        $allowed = $this->allowedConfigKeysForType($type);

        if ($allowed === []) {
            return [];
        }

        $filtered = Arr::only($config, $allowed);

        if (in_array($type, ['date', 'datetime', 'time'], true) && ! array_key_exists('displayFormat', $filtered)) {
            $filtered['displayFormat'] = DisplayFormat::defaultFor($type);
        }

        if ($type === 'relation' && array_key_exists('related_entity', $filtered)) {
            $related = $this->sanitizeRelatedEntity($filtered['related_entity']);

            if ($related === null) {
                unset($filtered['related_entity']);
            } else {
                $filtered['related_entity'] = $related;
            }
        }

        return $filtered;
    }

    /**
     * Only persist a related entity that is an actually relatable, registered
     * resource. Prevents storing an arbitrary target string that would let the
     * relation picker query models outside the intended whitelist.
     */
    protected function sanitizeRelatedEntity(mixed $relatedEntity): ?string
    {
        if (! is_string($relatedEntity) || $relatedEntity === '') {
            return null;
        }

        $allowed = array_keys($this->entityRegistry->relatableResources());

        return in_array($relatedEntity, $allowed, true) ? $relatedEntity : null;
    }

    /**
     * @return list<string>
     */
    protected function allowedConfigKeysForType(string $type): array
    {
        try {
            $fieldType = app(FieldTypeRegistry::class)->get($type);
        } catch (\Throwable) {
            return [];
        }

        $keys = [];

        foreach ($fieldType->capabilities() as $capabilityClass) {
            $capability = app($capabilityClass);

            foreach ($capability->builderFieldsFor($type) as $component) {
                $name = $component->getName();

                if (! is_string($name) || ! str_starts_with($name, 'config.')) {
                    continue;
                }

                $keys[] = substr($name, strlen('config.'));
            }
        }

        return array_values(array_unique($keys));
    }
}
