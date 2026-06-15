<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class DefaultValue extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.default')
                ->label(__('builder::builder.capabilities.default_value')),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! array_key_exists('default', $field->config)) {
            return $component;
        }

        $default = $field->config['default'];

        if ($component instanceof Toggle) {
            $component->default(filter_var($default, FILTER_VALIDATE_BOOLEAN));
        } else {
            $component->default($default);
        }

        return $component;
    }
}
