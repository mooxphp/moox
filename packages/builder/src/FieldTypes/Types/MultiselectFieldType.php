<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Concerns\BuildsOptionComponents;
use Moox\Builder\FieldTypes\FieldType;

class MultiselectFieldType extends FieldType
{
    use BuildsOptionComponents;

    public static function key(): string
    {
        return 'multiselect';
    }

    public function hasOptions(): bool
    {
        return true;
    }

    public function capabilities(): array
    {
        return [
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        if ($raw === null) {
            return [];
        }

        return is_array($raw) ? $raw : [$raw];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = Select::make($field->name)
            ->label($field->label)
            ->multiple();

        $component = $this->applyOptions($component, $field);

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
