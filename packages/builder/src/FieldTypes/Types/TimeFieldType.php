<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\FieldType;

class TimeFieldType extends FieldType
{
    public static function key(): string
    {
        return 'time';
    }

    public function capabilities(): array
    {
        return [
            DefaultValue::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = TimePicker::make($field->name)
            ->label($field->label);

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
