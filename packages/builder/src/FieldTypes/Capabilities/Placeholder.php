<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class Placeholder extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.placeholder')
                ->label(__('builder::builder.capabilities.placeholder')),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! isset($field->config['placeholder'])) {
            return $component;
        }

        if ($component instanceof TextInput || $component instanceof Textarea) {
            $component->placeholder($field->config['placeholder']);
        }

        return $component;
    }
}
