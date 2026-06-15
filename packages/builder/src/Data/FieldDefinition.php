<?php

declare(strict_types=1);

namespace Moox\Builder\Data;

use Moox\Builder\Models\Field;

readonly class FieldDefinition
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $validation
     * @param  list<array{label: string, value: string}>  $options
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public int $sort = 0,
        public array $config = [],
        public array $validation = [],
        public array $options = [],
    ) {}

    public static function fromModel(Field $field): self
    {
        $options = $field->relationLoaded('options')
            ? $field->options->map(fn ($option): array => [
                'label' => $option->label,
                'value' => $option->value,
            ])->values()->all()
            : [];

        return new self(
            name: $field->name,
            label: $field->label,
            type: $field->type,
            sort: (int) $field->sort,
            config: $field->config ?? [],
            validation: $field->validation ?? [],
            options: $options,
        );
    }

    /**
     * @return array{name: string, label: string, type: string, config: array<string, mixed>, validation: array<string, mixed>, options: list<array{label: string, value: string}>}
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
        ];
    }

    /**
     * @param  array{name: string, label: string, type: string, config?: array<string, mixed>, validation?: array<string, mixed>, options?: list<array{label: string, value: string}>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            label: $data['label'],
            type: $data['type'],
            sort: (int) ($data['sort'] ?? 0),
            config: $data['config'] ?? [],
            validation: $data['validation'] ?? [],
            options: $data['options'] ?? [],
        );
    }
}
