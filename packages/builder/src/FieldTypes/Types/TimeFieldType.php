<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Carbon\CarbonInterface;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Carbon;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\DisplayFormat;
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
            DisplayFormat::class,
            DefaultValue::class,
        ];
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $storageFormat = $field !== null
            ? DisplayFormat::storageFormatForTime(DisplayFormat::resolveForField($field))
            : 'H:i';

        if ($raw instanceof CarbonInterface) {
            return $raw->format($storageFormat);
        }

        if (is_string($raw)) {
            try {
                return Carbon::parse($raw)->format($storageFormat);
            } catch (\Throwable) {
                return $raw;
            }
        }

        return $raw;
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $defaultValue = app(DefaultValue::class);

        $component = TimePicker::make($field->name)
            ->label($field->label)
            ->native(true);

        $component = $this->applyCapabilitiesAndValidation($component, $field);

        return $component->default(static function () use ($field, $defaultValue): ?CarbonInterface {
            return $defaultValue->resolveForField($field);
        });
    }
}
