<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class RepeaterItems extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.min_items')
                ->label(__('builder::builder.capabilities.min_items'))
                ->helperText(__('builder::builder.capabilities.min_items_helper'))
                ->numeric()
                ->minValue(1),
            TextInput::make('config.max_items')
                ->label(__('builder::builder.capabilities.max_items'))
                ->helperText(__('builder::builder.capabilities.max_items_helper'))
                ->numeric()
                ->minValue(1),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! $component instanceof Repeater && ! $component instanceof Builder) {
            return $component;
        }

        $min = $this->resolveMinItems($field);

        if ($min !== null) {
            $component->minItems($min);
        } elseif (($field->validation['required'] ?? false) === true) {
            $component->minItems(1);
        }

        $max = $this->resolveMaxItems($field);

        if ($max !== null) {
            $component->maxItems($max);
        }

        return $component;
    }

    protected function resolveMinItems(FieldDefinition $field): ?int
    {
        return $this->normalizeItemLimit($field->config['min_items'] ?? null);
    }

    protected function resolveMaxItems(FieldDefinition $field): ?int
    {
        return $this->normalizeItemLimit($field->config['max_items'] ?? null);
    }

    protected function normalizeItemLimit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $limit = (int) $value;

        return $limit > 0 ? $limit : null;
    }
}
