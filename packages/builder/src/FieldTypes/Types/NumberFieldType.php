<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxValue;
use Moox\Builder\FieldTypes\Capabilities\MinValue;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\Capabilities\PrefixSuffix;
use Moox\Builder\FieldTypes\Capabilities\Step;
use Moox\Builder\FieldTypes\FieldType;

class NumberFieldType extends FieldType
{
    public static function key(): string
    {
        return 'number';
    }

    public function capabilities(): array
    {
        return [
            MinValue::class,
            MaxValue::class,
            Step::class,
            PrefixSuffix::class,
            Placeholder::class,
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function castValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return is_numeric($raw) ? $raw + 0 : $raw;
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = TextInput::make($field->name)
            ->label($field->label)
            ->numeric();

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
