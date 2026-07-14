<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class PrefixSuffix extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.prefix')
                ->label(__('builder::builder.capabilities.prefix'))
                ->helperText(__('builder::builder.capabilities.prefix_helper')),
            TextInput::make('config.suffix')
                ->label(__('builder::builder.capabilities.suffix'))
                ->helperText(__('builder::builder.capabilities.suffix_helper')),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! $component instanceof TextInput) {
            return $component;
        }

        if (filled($field->config['prefix'] ?? null)) {
            $component->prefix($field->config['prefix']);
        }

        if (filled($field->config['suffix'] ?? null)) {
            $component->suffix($field->config['suffix']);
        }

        return $component;
    }
}
