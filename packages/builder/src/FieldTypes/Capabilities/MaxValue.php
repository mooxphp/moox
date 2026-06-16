<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Slider;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class MaxValue extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.max')
                ->label(__('builder::builder.capabilities.max_value'))
                ->numeric(),
        ];
    }

    public function rules(FieldDefinition $field): array
    {
        if (! isset($field->config['max'])) {
            return [];
        }

        return ['max:'.$field->config['max']];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (isset($field->config['max']) && ($component instanceof TextInput || $component instanceof Slider)) {
            $component->maxValue($field->config['max']);
        }

        return $component;
    }
}
