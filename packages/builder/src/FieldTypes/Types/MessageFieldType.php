<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\MessageBody;
use Moox\Builder\FieldTypes\FieldType;

class MessageFieldType extends FieldType
{
    public static function key(): string
    {
        return 'message';
    }

    public function storesValue(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [
            MessageBody::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        return Placeholder::make($field->name)
            ->label($field->label)
            ->content($field->config['message'] ?? '');
    }
}
