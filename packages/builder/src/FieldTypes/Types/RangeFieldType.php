<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Slider;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxValue;
use Moox\Builder\FieldTypes\Capabilities\MinValue;
use Moox\Builder\FieldTypes\Capabilities\Step;
use Moox\Builder\FieldTypes\FieldType;

class RangeFieldType extends FieldType
{
    public static function key(): string
    {
        return 'range';
    }

    public function capabilities(): array
    {
        return [
            MinValue::class,
            MaxValue::class,
            Step::class,
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function castValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return $raw;
        }

        $value = $raw + 0;

        if (abs($value - round($value)) < 1e-9) {
            return (int) round($value);
        }

        return round($value, 6);
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = Slider::make($field->name)
            ->label($field->label)
            ->tooltips(true)
            ->required(false)
            ->decimalPlaces($this->decimalPlacesForStep($field));

        return $this->applyCapabilitiesAndValidation($component, $field);
    }

    protected function decimalPlacesForStep(FieldDefinition $field): int
    {
        $step = $field->config['step'] ?? null;

        if ($step === null || $step === '') {
            return 0;
        }

        $step = (float) $step;

        if ($step === floor($step)) {
            return 0;
        }

        $fraction = explode('.', (string) $step)[1] ?? '';

        return strlen(rtrim($fraction, '0')) ?: strlen($fraction);
    }
}
