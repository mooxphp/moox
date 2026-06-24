<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\FieldType;

class ColorFieldType extends FieldType
{
    public static function key(): string
    {
        return 'color';
    }

    public function capabilities(): array
    {
        return [
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = ColorPicker::make($field->name)
            ->label($field->label)
            ->hex();

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
