<?php

declare(strict_types=1);

namespace Moox\Builder\Data;

use Illuminate\Support\Collection;
use Moox\Builder\Models\Field;

readonly class FieldDefinition
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $validation
     * @param  list<array{label: string, value: string}>  $options
     * @param  Collection<int, FieldDefinition>  $children
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public int $sort = 0,
        public array $config = [],
        public array $validation = [],
        public array $options = [],
        public Collection $children = new Collection,
    ) {}

    public static function fromModel(Field $field): self
    {
        $options = $field->relationLoaded('options')
            ? $field->options->map(fn ($option): array => [
                'label' => $option->label,
                'value' => $option->value,
            ])->values()->all()
            : [];

        $children = $field->relationLoaded('children')
            ? $field->children
                ->map(fn (Field $child): self => self::fromModel($child))
                ->sortBy(fn (self $child): int => $child->sort)
                ->values()
            : new Collection;

        return new self(
            name: $field->name,
            label: $field->label,
            type: $field->type,
            sort: (int) $field->sort,
            config: $field->config ?? [],
            validation: $field->validation ?? [],
            options: $options,
            children: $children,
        );
    }

    /**
     * @return Collection<int, FieldDefinition>
     */
    public function layouts(): Collection
    {
        return $this->children
            ->filter(fn (self $child): bool => $child->type === 'flexible_layout')
            ->sortBy(fn (self $child): int => $child->sort)
            ->values();
    }

    /**
     * @return array{name: string, label: string, type: string, sort: int, config: array<string, mixed>, validation: array<string, mixed>, options: list<array{label: string, value: string}>, children: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'sort' => $this->sort,
            'config' => $this->config,
            'validation' => $this->validation,
            'options' => $this->options,
            'children' => $this->children
                ->map(fn (self $child): array => $child->toArray())
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array{name: string, label: string, type: string, sort?: int, config?: array<string, mixed>, validation?: array<string, mixed>, options?: list<array{label: string, value: string}>, children?: list<array<string, mixed>>}  $data
     */
    public static function fromArray(array $data): self
    {
        $children = collect($data['children'] ?? [])
            ->map(fn (array $child): self => self::fromArray($child))
            ->sortBy(fn (self $child): int => $child->sort)
            ->values();

        return new self(
            name: $data['name'],
            label: $data['label'],
            type: $data['type'],
            sort: (int) ($data['sort'] ?? 0),
            config: $data['config'] ?? [],
            validation: $data['validation'] ?? [],
            options: $data['options'] ?? [],
            children: $children,
        );
    }
}
