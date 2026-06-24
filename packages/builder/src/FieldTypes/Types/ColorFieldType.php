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
        $defaultValue = app(DefaultValue::class);

        $component = ColorPicker::make($field->name)
            ->label($field->label)
            ->live()
            ->afterStateHydrated(function (ColorPicker $component, mixed $state) use ($defaultValue): void {
                $normalized = $defaultValue->normalizeColorValue($state);

                if ($normalized !== null && $normalized !== $state) {
                    $component->state($normalized);
                }
            })
            ->afterStateUpdated(function (ColorPicker $component, mixed $state) use ($defaultValue): void {
                $normalized = $defaultValue->normalizeColorValue($state);

                if ($normalized !== null && $normalized !== $state) {
                    $component->state($normalized);
                }
            });

        $component = $this->applyCapabilitiesAndValidation($component, $field);

        return $component->default(static function () use ($field, $defaultValue): ?string {
            return $defaultValue->resolveForField($field);
        });
    }
}
