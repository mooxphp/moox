<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Slider;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class Step extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.step')
                ->label(__('builder::builder.capabilities.step'))
                ->numeric(),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (isset($field->config['step']) && ($component instanceof TextInput || $component instanceof Slider)) {
            $component->step($field->config['step']);
        }

        return $component;
    }
}
