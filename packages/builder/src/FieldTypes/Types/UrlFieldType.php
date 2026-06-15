<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\FieldType;

class UrlFieldType extends FieldType
{
    public static function key(): string
    {
        return 'url';
    }

    public function capabilities(): array
    {
        return [
            Placeholder::class,
            DefaultValue::class,
        ];
    }

    protected function additionalRules(FieldDefinition $field): array
    {
        return array_merge(['url'], parent::additionalRules($field));
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = TextInput::make($field->name)
            ->label($field->label)
            ->url();

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
