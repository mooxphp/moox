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
                ->rows(4),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        return $component;
    }
}
