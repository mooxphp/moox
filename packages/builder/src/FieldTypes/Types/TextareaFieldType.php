<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\Capabilities\Rows;
use Moox\Builder\FieldTypes\FieldType;

class TextareaFieldType extends FieldType
{
    public static function key(): string
    {
        return 'textarea';
    }

    public function capabilities(): array
    {
        return [
            MaxLength::class,
            Placeholder::class,
            Rows::class,
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = Textarea::make($field->name)
            ->label($field->label)
            ->rows((int) ($field->config['rows'] ?? 3));

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
