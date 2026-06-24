<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Carbon\CarbonInterface;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Moox\Builder\Data\FieldDefinition;

class DefaultValue extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.default')
                ->label(__('builder::builder.capabilities.default_value')),
        ];
    }

    public function builderFieldsFor(string $fieldType): array
    {
        return match ($fieldType) {
            'toggle' => [
                Toggle::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->helperText(__('builder::builder.capabilities.default_value_toggle_helper'))
                    ->inline(false)
                    ->default(false),
            ],
            'color' => [
                ColorPicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->hex(),
            ],
            'date' => $this->temporalDefaultFields(
                DatePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false),
                'date',
            ),
            'datetime' => $this->temporalDefaultFields(
                DateTimePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false),
                'datetime',
            ),
            'time' => $this->temporalDefaultFields(
                TimePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false)
                    ->seconds(false),
                'time',
            ),
            'select', 'radio', 'button_group' => [
                Select::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->helperText(__('builder::builder.capabilities.default_value_option_helper'))
                    ->options(fn (Get $get): array => $this->optionChoices($get('options')))
                    ->searchable()
                    ->native(false),
            ],
            'multiselect', 'checkbox_list' => [
                Select::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->helperText(__('builder::builder.capabilities.default_value_multi_option_helper'))
                    ->options(fn (Get $get): array => $this->optionChoices($get('options')))
                    ->multiple()
                    ->searchable()
                    ->native(false),
            ],
            'number', 'range' => [
                TextInput::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->rules(['nullable', 'numeric'])
                    ->validationAttribute(__('builder::builder.capabilities.default_value'))
                    ->live(onBlur: true),
            ],
            'textarea', 'rich_text' => [
                Textarea::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->rows(3),
            ],
            'email' => [
                TextInput::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->rules(['nullable', 'email'])
                    ->validationAttribute(__('builder::builder.capabilities.default_value'))
                    ->validationMessages([
                        'email' => __('builder::builder.validation.invalid_email_default'),
                    ])
                    ->live(onBlur: true),
            ],
            default => [
                TextInput::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value')),
            ],
        };
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        $default = $this->resolveForField($field);

        if ($default === null) {
            return $component;
        }

        $component->default($default);

        return $component;
    }

    public function resolveForField(FieldDefinition $field): mixed
    {
        if (in_array($field->type, ['date', 'datetime', 'time'], true)) {
            return $this->resolveTemporalDefault($field);
        }

        if (! array_key_exists('default', $field->config)) {
            return null;
        }

        $default = $field->config['default'];

        if ($default === null || $default === '') {
            return null;
        }

        if ($field->type === 'toggle') {
            return $this->resolveBooleanDefault($default);
        }

        if (in_array($field->type, ['number', 'range'], true) && is_numeric($default)) {
            return $default + 0;
        }

        if (in_array($field->type, ['multiselect', 'checkbox_list'], true)) {
            return $this->resolveArrayDefault($default);
        }

        if (in_array($field->type, ['select', 'radio', 'button_group'], true)) {
            return (string) $default;
        }

        if ($field->type === 'color') {
            return $this->normalizeColorDefault($default);
        }

        return $default;
    }

    protected function normalizeColorDefault(mixed $default): ?string
    {
        if (! is_string($default)) {
            return null;
        }

        $color = trim($default);

        if ($color === '') {
            return null;
        }

        if (! str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        return $color;
    }

    public function shouldApplyDefault(mixed $state, string $type): bool
    {
        if ($type === 'toggle') {
            return $state === null;
        }

        if (in_array($type, ['date', 'datetime', 'time'], true)) {
            if ($state === null || $state === '') {
                return true;
            }

            if ($state instanceof CarbonInterface) {
                return false;
            }

            if (is_string($state)) {
                try {
                    Carbon::parse($state);

                    return false;
                } catch (\Throwable) {
                    return true;
                }
            }

            return true;
        }

        if (is_array($state)) {
            return $state === [];
        }

        return $state === null || $state === '';
    }

    /**
     * @param  list<array{label?: string, value?: string}>|null  $options
     * @return array<string, string>
     */
    protected function optionChoices(?array $options): array
    {
        if ($options === null) {
            return [];
        }

        $choices = [];

        foreach ($options as $option) {
            $value = $option['value'] ?? null;

            if (blank($value)) {
                continue;
            }

            $choices[(string) $value] = filled($option['label'] ?? null)
                ? (string) $option['label']
                : (string) $value;
        }

        return $choices;
    }

    protected function resolveBooleanDefault(mixed $default): bool
    {
        if (is_bool($default)) {
            return $default;
        }

        return filter_var($default, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<Component>
     */
    protected function temporalDefaultFields(Component $picker, string $type): array
    {
        if (in_array($type, ['date', 'datetime'], true) && $picker instanceof DateTimePicker) {
            $picker = $this->configureTemporalAdminPicker($picker, $type);
        }

        return [
            Toggle::make('config.defaultNow')
                ->label(__('builder::builder.capabilities.default_value_now'))
                ->helperText(match ($type) {
                    'date' => __('builder::builder.capabilities.default_value_now_date_helper'),
                    'datetime' => __('builder::builder.capabilities.default_value_now_datetime_helper'),
                    default => __('builder::builder.capabilities.default_value_now_time_helper'),
                })
                ->inline(false)
                ->live(),
            $picker->hidden(fn (Get $get): bool => (bool) $get('config.defaultNow')),
        ];
    }

    protected function configureTemporalAdminPicker(DateTimePicker $picker, string $type): DateTimePicker
    {
        $picker = $picker
            ->displayFormat(fn (Get $get): string => $this->resolvedDisplayFormat($get, $type))
            ->format(fn (Get $get): string => $this->resolvedStorageFormat($get, $type))
            ->key(fn (Get $get): string => 'default-picker-'.$this->temporalPickerKeySuffix($get, $type));

        if ($type === 'datetime') {
            $picker = $picker->seconds(
                fn (Get $get): bool => str_contains($this->resolvedDisplayFormat($get, $type), 'H:i:s'),
            );
        }

        return $picker;
    }

    protected function temporalPickerKeySuffix(Get $get, string $type): string
    {
        return md5($type.':'.$this->resolvedDisplayFormat($get, $type));
    }

    protected function resolvedDisplayFormat(Get $get, string $type): string
    {
        return filled($get('config.displayFormat'))
            ? (string) $get('config.displayFormat')
            : DisplayFormat::defaultFor($type);
    }

    protected function resolvedStorageFormat(Get $get, string $type): string
    {
        if ($type === 'datetime') {
            return str_contains($this->resolvedDisplayFormat($get, $type), 'H:i:s')
                ? 'Y-m-d H:i:s'
                : 'Y-m-d H:i';
        }

        return 'Y-m-d';
    }

    protected function resolveTemporalDefault(FieldDefinition $field): CarbonInterface|string|null
    {
        if (($field->config['defaultNow'] ?? false) === true) {
            return match ($field->type) {
                'date' => now()->startOfDay(),
                'datetime' => now(),
                'time' => now()->format('H:i'),
                default => null,
            };
        }

        if (! array_key_exists('default', $field->config)) {
            return null;
        }

        $default = $field->config['default'];

        if ($default === null || $default === '') {
            return null;
        }

        return $this->parseTemporalDefault($field->type, $default);
    }

    protected function parseTemporalDefault(string $type, mixed $default): CarbonInterface|string|null
    {
        if ($default instanceof CarbonInterface) {
            return $type === 'time'
                ? $default->format('H:i')
                : $default;
        }

        if (! is_string($default)) {
            return null;
        }

        if ($type === 'time') {
            return $default;
        }

        try {
            return Carbon::parse($default);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveArrayDefault(mixed $default): array
    {
        if ($default === null || $default === '') {
            return [];
        }

        if (is_string($default)) {
            $decoded = json_decode($default, true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn (mixed $value): bool => $value !== null && $value !== ''));
            }

            return [$default];
        }

        if (! is_array($default)) {
            return [(string) $default];
        }

        return array_values(array_filter($default, fn (mixed $value): bool => $value !== null && $value !== ''));
    }
}
