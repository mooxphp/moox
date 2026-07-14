<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Forms\Components\BuilderMediaPicker;

class GalleryFiles extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.min_files')
                ->label(__('builder::builder.capabilities.min_files'))
                ->helperText(__('builder::builder.capabilities.min_files_helper'))
                ->numeric()
                ->minValue(1),
            TextInput::make('config.max_files')
                ->label(__('builder::builder.capabilities.max_files'))
                ->helperText(__('builder::builder.capabilities.max_files_helper'))
                ->numeric()
                ->minValue(1),
        ];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        if (! $component instanceof BuilderMediaPicker) {
            return $component;
        }

        $min = $this->normalizeFileLimit($field->config['min_files'] ?? null);

        if ($min !== null) {
            $component->minFiles($min);
        } elseif (($field->validation['required'] ?? false) === true) {
            $component->minFiles(1);
        }

        $max = $this->normalizeFileLimit($field->config['max_files'] ?? null);

        if ($max !== null) {
            $component->maxFiles($max);
        }

        return $component;
    }

    protected function normalizeFileLimit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $limit = (int) $value;

        return $limit > 0 ? $limit : null;
    }
}
