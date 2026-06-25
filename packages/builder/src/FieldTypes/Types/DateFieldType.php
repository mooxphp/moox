<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\DisplayFormat;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\FieldType;

class DateFieldType extends FieldType
{
    public static function key(): string
    {
        return 'date';
    }

    public function capabilities(): array
    {
        return [
            DisplayFormat::class,
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = DatePicker::make($field->name)
            ->label($field->label)
            ->native(false);

        return $this->applyCapabilitiesAndValidation($component, $field);
    }

    public function presentValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return $this->presentIsoDate($value);
    }
}
