<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class HelperText extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.helperText')
                ->label(__('builder::builder.capabilities.helper_text')),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! filled($field->config['helperText'] ?? null)) {
            return $component;
        }

        if (method_exists($component, 'helperText')) {
            $component->helperText($field->config['helperText']);
        }

        return $component;
    }
}
