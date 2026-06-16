<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class MaxLength extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.maxLength')
                ->label(__('builder::builder.capabilities.max_length'))
                ->numeric()
                ->minValue(1),
        ];
    }

    public function rules(FieldDefinition $field): array
    {
        if (! isset($field->config['maxLength'])) {
            return [];
        }

        return ['max:'.$field->config['maxLength']];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! isset($field->config['maxLength'])) {
            return $component;
        }

        if ($component instanceof TextInput || $component instanceof Textarea || method_exists($component, 'maxLength')) {
            $component->maxLength((int) $field->config['maxLength']);
        }

        return $component;
    }
}
