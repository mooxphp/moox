<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\Capabilities\PrefixSuffix;
use Moox\Builder\FieldTypes\FieldType;

class TextFieldType extends FieldType
{
    public static function key(): string
    {
        return 'text';
    }

    public function capabilities(): array
    {
        return [
            MaxLength::class,
            Placeholder::class,
            PrefixSuffix::class,
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = TextInput::make($field->name)
            ->label($field->label);

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
