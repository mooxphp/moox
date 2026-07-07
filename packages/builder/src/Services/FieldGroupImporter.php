<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldOption;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\DefinitionTranslator;
use Moox\Builder\Support\FieldGroupDefinitionMapper;
use Moox\Builder\Support\FieldGroupExportSchema;
use Moox\Builder\Support\FieldRelationTree;

class FieldGroupImporter
{
    public function __construct(
        protected FieldGroupPersistence $fieldGroupPersistence,
        protected FieldGroupDefinitionMapper $definitionMapper,
        protected DefinitionTranslator $definitionTranslator,
        protected BuilderLocaleResolver $localeResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(array $payload, bool $replaceExisting = false, ?string $slugOverride = null): FieldGroup
    {
        $this->assertValidPayload($payload);

        /** @var array<string, mixed> $groupData */
        $groupData = $payload['group'];

        if (array_key_exists('location_rules', $groupData) && ! array_key_exists('locationRules', $groupData)) {
            $groupData['locationRules'] = $groupData['location_rules'];
        }

        if (filled($slugOverride)) {
            $groupData['slug'] = $slugOverride;

            if (! $replaceExisting) {
                // A copied group must not inherit the same entity assignment, otherwise
                // field-key uniqueness validation blocks the import immediately.
                $groupData['locationRules'] = [];
            }
        }

        $groupData = $this->canonicalizeGroupData($groupData);

        $definition = FieldGroupDefinition::fromArray($groupData);
        $active = (bool) ($groupData['active'] ?? true);
        $sort = (int) ($groupData['sort'] ?? 0);

        $existing = FieldGroup::query()
            ->where('slug', $definition->slug)
            ->first();

        if ($existing instanceof FieldGroup && ! $replaceExisting) {
            $this->validationError(__('builder::builder.field_group.import_slug_exists', [
                'slug' => $definition->slug,
            ]));
        }

        $group = $existing ?? new FieldGroup;
        $defaultLocale = $this->localeResolver->defaultLocale();

        $this->localeResolver->using($defaultLocale, function () use ($group, $definition, $active, $sort, $defaultLocale): void {
            $localized = $this->definitionTranslator->localizeGroup($definition, $defaultLocale);
            $data = $this->definitionMapper->toPersistenceData($localized, $active, $sort);

            try {
                $this->fieldGroupPersistence->sync(
                    $group->exists ? $group->fresh() ?? $group : $group,
                    $data,
                );
            } catch (ValidationException $exception) {
                $this->validationError(
                    collect($exception->errors())->flatten()->first()
                        ?? __('builder::builder.field_group.import_failed'),
                );
            }
        });

        $group = FieldGroup::query()->where('slug', $definition->slug)->firstOrFail();
        $group->load(FieldRelationTree::eagerLoadForDefinition());

        foreach ($this->definitionMapper->collectLocales($definition) as $locale) {
            if ($locale === $defaultLocale) {
                continue;
            }

            $this->syncTranslationsOnly($group, $definition, $locale);
        }

        return $group->fresh(FieldRelationTree::eagerLoadForDefinition()) ?? $group;
    }

    public function importFromJson(string $json, bool $replaceExisting = false, ?string $slugOverride = null): FieldGroup
    {
        $payload = $this->parsePayloadFromJson($json);

        if (filled($slugOverride) && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugOverride)) {
            $this->validationError(__('builder::builder.field_group.import_invalid_slug'));
        }

        return $this->import($payload, $replaceExisting, $slugOverride);
    }

    public function slugFromJson(string $json): ?string
    {
        try {
            $payload = $this->parsePayloadFromJson($json);
        } catch (ValidationException) {
            return null;
        }

        $slug = $payload['group']['slug'] ?? null;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function slugIsTaken(string $slug): bool
    {
        return FieldGroup::query()->where('slug', $slug)->exists();
    }

    public function duplicateSlug(string $slug): string
    {
        $candidates = [$slug.'-copy'];

        for ($index = 2; $index <= 50; $index++) {
            $candidates[] = $slug.'-'.$index;
        }

        foreach ($candidates as $candidate) {
            if (! $this->slugIsTaken($candidate)) {
                return $candidate;
            }
        }

        return $slug.'-'.substr(uniqid(), -6);
    }

    /**
     * @return array<string, mixed>
     */
    public function parsePayloadFromJson(string $json): array
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->validationError(__('builder::builder.field_group.import_invalid_json', [
                'message' => $exception->getMessage(),
            ]));
        }

        if (! is_array($payload)) {
            $this->validationError(__('builder::builder.field_group.import_invalid_json', [
                'message' => 'root must be an object',
            ]));
        }

        return $payload;
    }

    protected function syncTranslationsOnly(FieldGroup $group, FieldGroupDefinition $definition, string $locale): void
    {
        $groupName = $definition->translations[$locale]['name'] ?? null;

        if (is_string($groupName) && $groupName !== '') {
            $group->translateOrNew($locale)->name = $groupName;
            $group->saveTranslations();
        }

        $this->syncFieldTranslations($group->fields, $definition->fields, $locale);
    }

    /**
     * @param  Collection<int, Field>  $fields
     * @param  Collection<int, FieldDefinition>  $definitions
     */
    protected function syncFieldTranslations(Collection $fields, Collection $definitions, string $locale): void
    {
        foreach ($definitions as $definition) {
            $field = $fields->firstWhere('name', $definition->name);

            if (! $field instanceof Field) {
                continue;
            }

            $translation = $definition->translations[$locale] ?? null;

            if (is_array($translation)) {
                $row = $field->translateOrNew($locale);
                $row->label = (string) ($translation['label'] ?? $field->label);

                $config = $translation['config'] ?? null;
                $row->config = is_array($config) && $config !== [] ? $config : null;
                $field->saveTranslations();
            }

            foreach ($definition->options as $index => $optionDefinition) {
                $option = $field->options->get($index)
                    ?? $field->options->firstWhere('value', $optionDefinition['value'] ?? null);

                if (! $option instanceof FieldOption) {
                    continue;
                }

                $optionLabel = $optionDefinition['translations'][$locale]['label'] ?? null;

                if (is_string($optionLabel) && $optionLabel !== '') {
                    $option->translateOrNew($locale)->label = $optionLabel;
                    $option->saveTranslations();
                }
            }

            if ($definition->type === 'flexible_content') {
                $this->syncFieldTranslations(
                    $field->children,
                    $definition->layouts(),
                    $locale,
                );

                continue;
            }

            if ($definition->children->isNotEmpty()) {
                $this->syncFieldTranslations($field->children, $definition->children, $locale);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function assertValidPayload(array $payload): void
    {
        if (($payload['schema'] ?? null) !== FieldGroupExportSchema::SCHEMA) {
            $this->validationError(__('builder::builder.field_group.import_invalid_schema'));
        }

        if ((int) ($payload['version'] ?? 0) !== FieldGroupExportSchema::VERSION) {
            $this->validationError(__('builder::builder.field_group.import_unsupported_version'));
        }

        $group = $payload['group'] ?? null;

        if (! is_array($group)) {
            $this->validationError(__('builder::builder.field_group.import_missing_group'));
        }

        foreach (['name', 'slug', 'placement'] as $requiredKey) {
            if (blank($group[$requiredKey] ?? null)) {
                $this->validationError(__('builder::builder.field_group.import_missing_group_key', [
                    'key' => $requiredKey,
                ]));
            }
        }

        if (! is_array($group['fields'] ?? null)) {
            $this->validationError(__('builder::builder.field_group.import_missing_group_key', [
                'key' => 'fields',
            ]));
        }

        if (
            ! is_array($group['locationRules'] ?? null)
            && ! is_array($group['location_rules'] ?? null)
            && ! array_key_exists('target_entities', $group)
        ) {
            $this->validationError(__('builder::builder.field_group.import_missing_group_key', [
                'key' => 'locationRules',
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $groupData
     * @return array<string, mixed>
     */
    protected function canonicalizeGroupData(array $groupData): array
    {
        if (
            ! array_key_exists('locationRules', $groupData)
            && ! array_key_exists('location_rules', $groupData)
            && (
                array_key_exists('target_entities', $groupData)
                || array_key_exists('location_constraints', $groupData)
            )
        ) {
            $groupData['locationRules'] = $this->fieldGroupPersistence->resolveLocationRules([
                'target_entities' => $groupData['target_entities'] ?? [],
                'location_constraints' => $groupData['location_constraints'] ?? [],
            ]);
        }

        $groupData['fields'] = $this->canonicalizeFieldRows($groupData['fields'] ?? []);

        return $groupData;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function canonicalizeFieldRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map(
            fn (array $row): array => $this->canonicalizeFieldRow($row),
            array_filter($rows, 'is_array'),
        ));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function canonicalizeFieldRow(array $row): array
    {
        $validation = is_array($row['validation'] ?? null) ? $row['validation'] : [];

        if (array_key_exists('required', $row) && ! array_key_exists('required', $validation)) {
            $validation['required'] = (bool) $row['required'];
        }

        $children = $this->canonicalizeFieldRows($row['children'] ?? []);

        if ($children === [] && is_array($row['layouts'] ?? null)) {
            $children = array_map(
                fn (array $layout): array => [
                    ...$this->canonicalizeFieldRow($layout),
                    'type' => 'flexible_layout',
                ],
                $this->canonicalizeFieldRows($row['layouts']),
            );
        }

        return [
            ...$row,
            'validation' => $validation,
            'children' => $children,
        ];
    }

    /**
     * @return never
     */
    protected function validationError(string $message): void
    {
        throw ValidationException::withMessages([
            'file' => [$message],
        ]);
    }
}
