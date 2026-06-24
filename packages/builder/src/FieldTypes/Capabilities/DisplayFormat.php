<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

class DisplayFormat extends Capability
{
    public function builderFields(): array
    {
        return $this->builderFieldsFor('date');
    }

    public function builderFieldsFor(string $fieldType): array
    {
        return [
            Select::make('config.displayFormat')
                ->label(__('builder::builder.capabilities.display_format'))
                ->helperText(__('builder::builder.capabilities.display_format_helper'))
                ->options($this->formatOptionsFor($fieldType))
                ->default(self::defaultFor($fieldType))
                ->live()
                ->native(false),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if ($component instanceof DateTimePicker) {
            $format = self::resolveForField($field);

            $component->displayFormat($format);

            if ($field->type === 'datetime') {
                $component->seconds(str_contains($format, 'H:i:s'));
            }
        }

        return $component;
    }

    public static function defaultFor(string $fieldType): string
    {
        return match ($fieldType) {
            'datetime' => 'd.m.Y H:i',
            default => 'd.m.Y',
        };
    }

    public static function resolveForField(FieldDefinition $field): string
    {
        if (filled($field->config['displayFormat'] ?? null)) {
            return (string) $field->config['displayFormat'];
        }

        return self::defaultFor($field->type);
    }

    /**
     * @return array<string, string>
     */
    protected function formatOptionsFor(string $fieldType): array
    {
        return match ($fieldType) {
            'datetime' => $this->datetimeFormatOptions(),
            default => $this->dateFormatOptions(),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function dateFormatOptions(): array
    {
        return [
            'd.m.Y' => __('builder::builder.capabilities.display_format_dmy'),
            'd/m/Y' => __('builder::builder.capabilities.display_format_dmy_slash'),
            'Y-m-d' => __('builder::builder.capabilities.display_format_ymd'),
            'm/d/Y' => __('builder::builder.capabilities.display_format_mdy'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function datetimeFormatOptions(): array
    {
        return [
            'd.m.Y H:i' => __('builder::builder.capabilities.display_format_dmy_hi'),
            'd.m.Y H:i:s' => __('builder::builder.capabilities.display_format_dmy_his'),
            'Y-m-d H:i' => __('builder::builder.capabilities.display_format_ymd_hi'),
            'Y-m-d H:i:s' => __('builder::builder.capabilities.display_format_ymd_his'),
        ];
    }
}
