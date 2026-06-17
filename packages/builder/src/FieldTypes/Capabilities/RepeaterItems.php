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
            TextInput::make('config.min')
                ->label(__('builder::builder.capabilities.min_items'))
                ->numeric()
                ->minValue(0),
            TextInput::make('config.max')
                ->label(__('builder::builder.capabilities.max_items'))
                ->numeric()
                ->minValue(0),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! $component instanceof Repeater && ! $component instanceof Builder) {
            return $component;
        }

        $min = $field->config['min'] ?? null;
        $max = $field->config['max'] ?? null;

        if (is_numeric($min)) {
            $component->minItems((int) $min);
        }

        if (is_numeric($max)) {
            $component->maxItems((int) $max);
        }

        return $component;
    }
}
