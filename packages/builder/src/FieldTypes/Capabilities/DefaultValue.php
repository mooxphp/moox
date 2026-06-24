<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

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
                    ->label(__('builder::builder.capabilities.default_value')),
            ],
            'date' => [
                DatePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false),
            ],
            'datetime' => [
                DateTimePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false),
            ],
            'time' => [
                TimePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false),
            ],
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
                    ->numeric(),
            ],
            'textarea', 'rich_text' => [
                Textarea::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->rows(3),
            ],
            default => [
                TextInput::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value')),
            ],
        };
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! array_key_exists('default', $field->config)) {
            return $component;
        }

        $default = $field->config['default'];

        if ($default === null || $default === '') {
            return $component;
        }

        if ($component instanceof Toggle) {
            $component->default($this->resolveBooleanDefault($default));

            return $component;
        }

        if (in_array($field->type, ['number', 'range'], true) && is_numeric($default)) {
            $component->default($default + 0);

            return $component;
        }

        if (in_array($field->type, ['multiselect', 'checkbox_list'], true)) {
            $component->default($this->resolveArrayDefault($default));

            return $component;
        }

        $component->default($default);

        return $component;
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
     * @return list<string>
     */
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
