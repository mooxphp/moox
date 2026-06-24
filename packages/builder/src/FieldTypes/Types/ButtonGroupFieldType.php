<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Concerns\BuildsOptionComponents;
use Moox\Builder\FieldTypes\FieldType;

class ButtonGroupFieldType extends FieldType
{
    use BuildsOptionComponents;

    public static function key(): string
    {
        return 'button_group';
    }

    public function hasOptions(): bool
    {
        return true;
    }

    public function capabilities(): array
    {
        return [
            DefaultValue::class,
        ];
    }

    public function castValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (string) $raw;
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $defaultValue = app(DefaultValue::class);

        $component = ToggleButtons::make($field->name)
            ->label($field->label)
            ->inline();

        $component = $this->applyOptions($component, $field);
        $component = $this->applyCapabilitiesAndValidation($component, $field);

        return $component->default(static function () use ($field, $defaultValue): ?string {
            return $defaultValue->resolveForField($field);
        });
    }
}
