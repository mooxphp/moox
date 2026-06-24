<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Moox\Builder\Data\FieldDefinition;

class MinValue extends Capability
{
    public function builderFields(): array
    {
        return [
            $this->minValueField(),
        ];
    }

    public function builderFieldsFor(string $fieldType): array
    {
        if ($fieldType === 'range') {
            return [
                $this->minValueField()
                    ->helperText(__('builder::builder.capabilities.range_min_helper'))
                    ->rules(fn (Get $get): array => $this->rangeMinRules($get))
                    ->validationAttribute(__('builder::builder.capabilities.min_value'))
                    ->validationMessages([
                        'lt' => __('builder::builder.validation.range_min_lt_max'),
                    ])
                    ->live(onBlur: true),
            ];
        }

        return $this->builderFields();
    }

    protected function minValueField(): TextInput
    {
        return TextInput::make('config.min')
            ->label(__('builder::builder.capabilities.min_value'))
            ->numeric();
    }

    /**
     * @return list<string>
     */
    protected function rangeMinRules(Get $get): array
    {
        $rules = ['nullable', 'numeric'];

        $max = $get('config.max');

        if ($max !== null && $max !== '' && is_numeric($max)) {
            $rules[] = 'lt:'.$max;
        }

        return $rules;
    }

    public function rules(FieldDefinition $field): array
    {
        if (! isset($field->config['min'])) {
            return [];
        }

        return ['min:'.$field->config['min']];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (isset($field->config['min']) && ($component instanceof TextInput || $component instanceof Slider)) {
            $component->minValue($field->config['min']);
        }

        return $component;
    }
}
