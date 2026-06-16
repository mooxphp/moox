<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Concerns;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

trait BuildsOptionComponents
{
    /**
     * @return array<string, string>
     */
    protected function optionsMap(FieldDefinition $field): array
    {
        $options = [];

        foreach ($field->options as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }

    protected function applyOptions(Component $component, FieldDefinition $field): Component
    {
        $options = $this->optionsMap($field);

        if ($component instanceof Select || $component instanceof Radio || $component instanceof CheckboxList || $component instanceof ToggleButtons) {
            $component->options($options);

            if ($options !== []) {
                $component->in(array_keys($options));
            }
        }

        return $component;
    }
}
