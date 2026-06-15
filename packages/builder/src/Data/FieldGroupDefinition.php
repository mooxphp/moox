<?php

declare(strict_types=1);

namespace Moox\Builder\Data;

use Illuminate\Support\Collection;
use Moox\Builder\Models\FieldGroup;

readonly class FieldGroupDefinition
{
    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  list<list<array{param: string, operator: string, value: mixed}>>  $locationRules
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $placement,
        public Collection $fields,
        public array $locationRules = [],
        public array $settings = [],
    ) {}

    public static function fromModel(FieldGroup $group): self
    {
        $fields = $group->relationLoaded('fields')
            ? $group->fields
                ->map(fn ($field): FieldDefinition => FieldDefinition::fromModel($field))
                ->sortBy(fn (FieldDefinition $field): int => $field->sort)
                ->values()
            : collect();

        return new self(
            name: $group->name,
            slug: $group->slug,
            placement: $group->placement,
            fields: $fields,
            locationRules: $group->location_rules ?? [],
            settings: $group->settings ?? [],
        );
    }

    /**
     * @return array{name: string, slug: string, placement: string, fields: list<array<string, mixed>>, locationRules: list<list<array{param: string, operator: string, value: mixed}>>, settings: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'placement' => $this->placement,
            'fields' => $this->fields
                ->map(fn (FieldDefinition $field): array => $field->toArray())
                ->values()
                ->all(),
            'locationRules' => $this->locationRules,
            'settings' => $this->settings,
        ];
    }

    /**
     * @param  array{name: string, slug: string, placement: string, fields?: list<array<string, mixed>>, locationRules?: list<list<array{param: string, operator: string, value: mixed}>>, settings?: array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        $fields = collect($data['fields'] ?? [])
            ->map(fn (array $field): FieldDefinition => FieldDefinition::fromArray($field))
            ->sortBy(fn (FieldDefinition $field): int => $field->sort)
            ->values();

        return new self(
            name: $data['name'],
            slug: $data['slug'],
            placement: $data['placement'],
            fields: $fields,
            locationRules: $data['locationRules'] ?? [],
            settings: $data['settings'] ?? [],
        );
    }
}
