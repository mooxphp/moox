<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Moox\Builder\Data\FieldDefinition;

class MaxValue extends Capability
{
    public function builderFields(): array
    {
        return [
            $this->maxValueField(),
        ];
    }

    public function builderFieldsFor(string $fieldType): array
    {
        if ($fieldType === 'range') {
            return [
                $this->maxValueField()
                    ->helperText(__('builder::builder.capabilities.range_max_helper'))
                    ->rules(fn (Get $get): array => $this->rangeMaxRules($get))
                    ->validationAttribute(__('builder::builder.capabilities.max_value'))
                    ->validationMessages([
                        'gt' => __('builder::builder.validation.range_max_gt_min'),
                    ])
                    ->live(onBlur: true),
            ];
        }

        return $this->builderFields();
    }

    protected function maxValueField(): TextInput
    {
        return TextInput::make('config.max')
            ->label(__('builder::builder.capabilities.max_value'))
            ->numeric();
    }

    /**
     * @return list<string>
     */
    protected function rangeMaxRules(Get $get): array
    {
        $rules = ['nullable', 'numeric'];

        $min = $get('config.min');

        if ($min !== null && $min !== '' && is_numeric($min)) {
            $rules[] = 'gt:'.$min;
        }

        return $rules;
    }

    public function rules(FieldDefinition $field): array
    {
        if (! isset($field->config['max'])) {
            return [];
        }

        return ['max:'.$field->config['max']];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (isset($field->config['max']) && ($component instanceof TextInput || $component instanceof Slider)) {
            $component->maxValue($field->config['max']);
        }

        return $component;
    }
}
