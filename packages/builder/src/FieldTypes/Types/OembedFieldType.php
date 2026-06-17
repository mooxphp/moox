<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\FieldType;

class OembedFieldType extends FieldType
{
    public static function key(): string
    {
        return 'oembed';
    }

    public function capabilities(): array
    {
        return [
            Placeholder::class,
            DefaultValue::class,
            HelperText::class,
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
            ->url()
            ->helperText(__('builder::builder.oembed.helper'));

        return $this->applyCapabilitiesAndValidation($component, $field);
    }
}
