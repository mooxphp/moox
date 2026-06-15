<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class MinValue extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.min')
                ->label(__('builder::builder.capabilities.min_value'))
                ->numeric(),
        ];
    }

    public function rules(FieldDefinition $field): array
    {
        if (! isset($field->config['min'])) {
            return [];
        }

        return ['min:'.$field->config['min']];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if ($component instanceof TextInput && isset($field->config['min'])) {
            $component->minValue($field->config['min']);
        }

        return $component;
    }
}
