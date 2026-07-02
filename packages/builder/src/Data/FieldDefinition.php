<?php

declare(strict_types=1);

namespace Moox\Builder\Data;

use Illuminate\Support\Collection;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldOption;

readonly class FieldDefinition
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $validation
     * @param  array<string, mixed>  $settings
     * @param  list<array{label: string, value: string, translations?: array<string, array{label?: string}>}>  $options
     * @param  Collection<int, FieldDefinition>  $children
     * @param  array<string, array{label?: string, config?: array<string, mixed>}>  $translations
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type,
        public int $sort = 0,
        public array $config = [],
        public array $validation = [],
        public array $settings = [],
        public array $options = [],
        public Collection $children = new Collection,
        public array $translations = [],
    ) {}

    public function showInTable(): bool
    {
        return (bool) ($this->settings['show_in_table'] ?? false);
    }

    public function isColumnSortable(): bool
    {
        return (bool) ($this->settings['sortable'] ?? true);
    }

    public function isColumnSearchable(): bool
    {
        return (bool) ($this->settings['searchable'] ?? true);
    }

    public function isColumnHiddenByDefault(): bool
    {
        return (bool) ($this->settings['hidden_by_default'] ?? true);
    }

    public function columnBadge(): bool
    {
        return (bool) ($this->settings['badge'] ?? false);
    }

    public function columnColor(): ?string
    {
        $color = $this->settings['color'] ?? null;

        return filled($color) ? (string) $color : null;
    }

    public function columnIcon(): ?string
    {
        $icon = $this->settings['icon'] ?? null;

        return filled($icon) ? (string) $icon : null;
    }

    public function columnImageShape(): ?string
    {
        $shape = $this->settings['image_shape'] ?? null;

        return in_array($shape, ['square', 'circular'], true) ? $shape : null;
    }

    public function columnImageSize(): string
    {
        $size = $this->settings['image_size'] ?? null;

        return in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    }

    /**
     * Whether the field is visible in the given context (admin, frontend, api).
     * Defaults to visible when the setting is absent.
     */
    public function isVisibleIn(string $context): bool
    {
        return (bool) ($this->settings["visible_{$context}"] ?? true);
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     */
    public function withChildren(Collection $children): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            type: $this->type,
            sort: $this->sort,
            config: $this->config,
            validation: $this->validation,
            settings: $this->settings,
            options: $this->options,
            children: $children,
            translations: $this->translations,
        );
    }

    public static function fromModel(Field $field): self
    {
        $options = $field->relationLoaded('options')
            ? $field->options->map(fn ($option): array => [
                'label' => $option->label,
                'value' => $option->value,
                'translations' => self::mapOptionTranslations($option),
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
            settings: $field->settings ?? [],
            options: $options,
            children: $children,
            translations: self::mapFieldTranslations($field),
        );
    }

    /**
     * @return array<string, array{label: string, config?: array<string, mixed>}>
     */
    protected static function mapFieldTranslations(Field $field): array
    {
        if (! $field->relationLoaded('translations')) {
            return [];
        }

        $translations = [];

        foreach ($field->translations as $translation) {
            $translations[$translation->locale] = [
                'label' => $translation->label,
                'config' => $translation->config ?? [],
            ];
        }

        return $translations;
    }

    /**
     * @return array<string, array{label: string}>
     */
    protected static function mapOptionTranslations(FieldOption $option): array
    {
        if (! $option->relationLoaded('translations')) {
            return [];
        }

        $translations = [];

        foreach ($option->translations as $translation) {
            $translations[$translation->locale] = [
                'label' => $translation->label,
            ];
        }

        return $translations;
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
            'settings' => $this->settings,
            'options' => $this->options,
            'children' => $this->children
                ->map(fn (self $child): array => $child->toArray())
                ->values()
                ->all(),
            'translations' => $this->translations,
        ];
    }

    /**
     * @param  array{name: string, label: string, type: string, sort?: int, config?: array<string, mixed>, validation?: array<string, mixed>, settings?: array<string, mixed>, options?: list<array{label: string, value: string, translations?: array<string, array{label?: string}>}>, children?: list<array<string, mixed>>, translations?: array<string, array{label?: string, config?: array<string, mixed>}>}  $data
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
            settings: $data['settings'] ?? [],
            options: $data['options'] ?? [],
            children: $children,
            translations: $data['translations'] ?? [],
        );
    }
}
