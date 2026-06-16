<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
use Moox\Builder\FieldTypes\FieldType;

class RichTextFieldType extends FieldType
{
    public static function key(): string
    {
        return 'rich_text';
    }

    public function capabilities(): array
    {
        return [
            MaxLength::class,
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = RichEditor::make($field->name)
            ->label($field->label);

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
