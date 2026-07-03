<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\RelationSettings;
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\Support\RelationTargetResolver;
use Moox\Builder\Support\RelationValueRules;

class RelationFieldType extends FieldType
{
    public static function key(): string
    {
        return 'relation';
    }

    public function capabilities(): array
    {
        return [
            RelationSettings::class,
            HelperText::class,
        ];
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        if ($field === null) {
            return $raw;
        }

        if ($raw === null || $raw === '') {
            return $this->isMultiple($field) ? [] : null;
        }

        if ($this->isMultiple($field)) {
            return is_array($raw) ? array_values($raw) : [$raw];
        }

        if (is_array($raw)) {
            return $raw === [] ? null : reset($raw);
        }

        return $raw;
    }

    public function persistValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return $this->castValue($value, $field);
    }

    public function presentValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        if ($field === null) {
            return $value;
        }

        $entity = $this->relatedEntity($field);

        if ($entity === null) {
            return $value;
        }

        $ids = RelationValueRules::normalizeIds($value, $this->isMultiple($field));

        if ($ids === []) {
            return $this->isMultiple($field) ? [] : null;
        }

        $resolved = app(RelationTargetResolver::class)->resolve($entity, $ids);

        if ($this->isMultiple($field)) {
            return $resolved;
        }

        return $resolved[0] ?? null;
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $entity = $this->relatedEntity($field);
        $multiple = $this->isMultiple($field);
        $resolver = app(RelationTargetResolver::class);

        $component = Select::make($field->name)
            ->label($field->label)
            ->native(false)
            ->searchable();

        if ($multiple) {
            $component->multiple();
        }

        if ($entity !== null) {
            $component
                // Preloaded suggestions so the picker shows records immediately
                // (ACF-like) instead of an empty box until the user types.
                ->options(fn (): array => $resolver->search($entity, ''))
                ->preload()
                ->getSearchResultsUsing(
                    fn (string $search): array => $resolver->search($entity, $search),
                )
                ->getOptionLabelUsing(
                    function (mixed $value) use ($entity, $resolver): ?string {
                        if (! filled($value)) {
                            return null;
                        }

                        $labels = $resolver->labelsFor($entity, [$value]);

                        return $labels[$value]
                            ?? $labels[(int) $value]
                            ?? $labels[(string) $value]
                            ?? null;
                    },
                );

            if ($multiple) {
                $component->getOptionLabelsUsing(
                    function (array $values) use ($entity, $resolver): array {
                        $labels = $resolver->labelsFor($entity, $values);
                        $options = [];

                        foreach ($values as $value) {
                            $label = $labels[$value]
                                ?? $labels[(int) $value]
                                ?? $labels[(string) $value]
                                ?? null;

                            if ($label !== null) {
                                $options[$value] = $label;
                            }
                        }

                        return $options;
                    },
                );
            }
        }

        return $this->applyCapabilitiesAndValidation($component, $field);
    }

    protected function isMultiple(FieldDefinition $field): bool
    {
        return RelationValueRules::isMultiple($field);
    }

    protected function relatedEntity(FieldDefinition $field): ?string
    {
        return RelationValueRules::relatedEntity($field);
    }
}
