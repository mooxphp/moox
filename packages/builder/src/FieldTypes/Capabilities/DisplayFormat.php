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
        return [
            Select::make('config.displayFormat')
                ->label(__('builder::builder.capabilities.display_format'))
                ->helperText(__('builder::builder.capabilities.display_format_helper'))
                ->options($this->formatOptions())
                ->native(false),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if ($component instanceof DateTimePicker && filled($field->config['displayFormat'] ?? null)) {
            $component->displayFormat($field->config['displayFormat']);
        }

        return $component;
    }

    /**
     * @return array<string, string>
     */
    protected function formatOptions(): array
    {
        return [
            'd.m.Y' => __('builder::builder.capabilities.display_format_dmy'),
            'd/m/Y' => __('builder::builder.capabilities.display_format_dmy_slash'),
            'Y-m-d' => __('builder::builder.capabilities.display_format_ymd'),
            'm/d/Y' => __('builder::builder.capabilities.display_format_mdy'),
            'd.m.Y H:i' => __('builder::builder.capabilities.display_format_dmy_hi'),
            'd.m.Y H:i:s' => __('builder::builder.capabilities.display_format_dmy_his'),
        ];
    }
}
