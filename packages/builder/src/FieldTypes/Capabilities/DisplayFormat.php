<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class DisplayFormat extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.displayFormat')
                ->label(__('builder::builder.capabilities.display_format'))
                ->placeholder('d.m.Y'),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if ($component instanceof DateTimePicker && filled($field->config['displayFormat'] ?? null)) {
            $component->displayFormat($field->config['displayFormat']);
        }

        return $component;
    }
}
