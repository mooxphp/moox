<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class MessageBody extends Capability
{
    public function builderFields(): array
    {
        return [
            Textarea::make('config.message')
                ->label(__('builder::builder.message.body'))
                ->helperText(__('builder::builder.message.body_helper'))
                ->rows(4)
                ->required(),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        return $component;
    }
}
