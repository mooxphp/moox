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

class SelectFieldType extends FieldType
{
    use BuildsOptionComponents;

    public static function key(): string
    {
        return 'select';
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

    public function formComponent(FieldDefinition $field): Component
    {
        $defaultValue = app(DefaultValue::class);

        $component = Select::make($field->name)
            ->label($field->label)
            ->native(false);

        $component = $this->applyOptions($component, $field);
        $component = $this->applyCapabilitiesAndValidation($component, $field);

        $component->default(static function () use ($field, $defaultValue): ?string {
            return $defaultValue->resolveForField($field);
        });

        if ($defaultValue->resolveForField($field) !== null) {
            $component->selectablePlaceholder(false);
        }

        return $component;
    }
}
