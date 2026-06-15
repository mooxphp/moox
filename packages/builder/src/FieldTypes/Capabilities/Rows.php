<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class Rows extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.rows')
                ->label(__('builder::builder.capabilities.rows'))
                ->numeric()
                ->default(3)
                ->minValue(1),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if ($component instanceof Textarea && isset($field->config['rows'])) {
            $component->rows((int) $field->config['rows']);
        }

        return $component;
    }
}
