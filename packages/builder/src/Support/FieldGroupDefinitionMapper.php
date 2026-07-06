<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\FieldGroupPersistence;

final class FieldGroupDefinitionMapper
{
    public function __construct(
        protected FieldGroupPersistence $fieldGroupPersistence,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected BuilderLocaleResolver $localeResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceData(
        FieldGroupDefinition $definition,
        bool $active,
        int $sort,
    ): array {
        return [
            'name' => $definition->name,
            'slug' => $definition->slug,
            'active' => $active,
            'sort' => $sort,
            'placement' => $definition->placement,
            'settings' => $definition->settings,
            'target_entities' => $this->fieldGroupPersistence->entitiesFromLocationRules(
                $definition->locationRules,
            ),
            'fields' => $this->mapFieldsToRows($definition->fields),
        ];
    }

    /**
     * @return list<string>
     */
    public function collectLocales(FieldGroupDefinition $definition): array
    {
        $locales = array_keys($definition->translations);

        foreach ($definition->fields as $field) {
            $locales = array_merge($locales, $this->collectFieldLocales($field));
        }

        $default = $this->localeResolver->defaultLocale();
        $locales[] = $default;

        $unique = array_values(array_unique(array_filter(
            $locales,
            static fn (string $locale): bool => $locale !== '',
        )));

        usort($unique, static fn (string $a, string $b): int => match (true) {
            $a === $default && $b !== $default => -1,
            $b === $default && $a !== $default => 1,
            default => strcmp($a, $b),
        });

        return $unique;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FieldDefinition>  $fields
     * @return list<array<string, mixed>>
     */
    protected function mapFieldsToRows($fields): array
    {
        return $fields
            ->map(fn (FieldDefinition $field): array => $this->fieldToRow($field))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function fieldToRow(FieldDefinition $field): array
    {
        $row = [
            'name' => $field->name,
            'label' => $field->label,
            'type' => $field->type,
            'required' => (bool) ($field->validation['required'] ?? false),
            'validation' => $field->validation,
            'config' => $field->config,
            'settings' => $field->settings,
            'sort' => $field->sort,
            'options' => collect($field->options)
                ->values()
                ->map(fn (array $option, int $index): array => [
                    'label' => $option['label'],
                    'value' => $option['value'],
                    'sort' => $index,
                ])
                ->all(),
        ];

        if ($field->type === 'flexible_content') {
            $row['layouts'] = $field->layouts()
                ->map(fn (FieldDefinition $layout): array => [
                    'name' => $layout->name,
                    'label' => $layout->label,
                    'sort' => $layout->sort,
                    'children' => $this->mapFieldsToRows($layout->children),
                ])
                ->values()
                ->all();
        } elseif ($this->fieldTypeRegistry->get($field->type)->hasSubFields()) {
            $row['children'] = $this->mapFieldsToRows($field->children);
        }

        return $row;
    }

    /**
     * @return list<string>
     */
    protected function collectFieldLocales(FieldDefinition $field): array
    {
        $locales = array_keys($field->translations);

        foreach ($field->options as $option) {
            $locales = array_merge($locales, array_keys($option['translations'] ?? []));
        }

        foreach ($field->children as $child) {
            $locales = array_merge($locales, $this->collectFieldLocales($child));
        }

        return $locales;
    }
}
