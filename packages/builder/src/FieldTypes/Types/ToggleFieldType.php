<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\FieldType;

class ToggleFieldType extends FieldType
{
    public static function key(): string
    {
        return 'toggle';
    }

    public function capabilities(): array
    {
        return [
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function castValue(mixed $raw): mixed
    {
        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = Toggle::make($field->name)
            ->label($field->label);

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
