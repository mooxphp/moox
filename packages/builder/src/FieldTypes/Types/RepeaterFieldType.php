<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\RepeaterItems;
use Moox\Builder\FieldTypes\FieldType;

class RepeaterFieldType extends FieldType
{
    public static function key(): string
    {
        return 'repeater';
    }

    public function hasSubFields(): bool
    {
        return true;
    }

    public function capabilities(): array
    {
        return [
            RepeaterItems::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $compiler = app(SchemaCompiler::class);

        $component = Repeater::make($field->name)
            ->label($field->label)
            ->schema($compiler->compileSubFields($field->children, null))
            ->collapsible()
            ->defaultItems(0)
            ->reorderable();

        return $this->applyNestedValueValidation(
            $this->applyCapabilitiesAndValidation($component, $field),
            $field,
        );
    }

    public function castValue(mixed $raw): mixed
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values($raw);
    }
}
