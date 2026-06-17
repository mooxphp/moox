<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\FieldType;

class GroupFieldType extends FieldType
{
    public static function key(): string
    {
        return 'group';
    }

    public function hasSubFields(): bool
    {
        return true;
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $compiler = app(SchemaCompiler::class);

        $component = Repeater::make($field->name)
            ->label($field->label)
            ->schema($compiler->compileSubFields($field->children, null))
            ->minItems(1)
            ->maxItems(1)
            ->defaultItems(1)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->collapsible(false)
            ->hiddenLabel($field->label === '');

        return $this->applyNestedValueValidation($component, $field);
    }

    public function castValue(mixed $raw): mixed
    {
        if (! is_array($raw)) {
            return [];
        }

        if (array_is_list($raw) && isset($raw[0]) && is_array($raw[0])) {
            return $raw[0];
        }

        return $raw;
    }

    public function normalizeForForm(mixed $stored): array
    {
        if (! is_array($stored) || $stored === []) {
            return [[]];
        }

        if (array_is_list($stored)) {
            return $stored;
        }

        return [$stored];
    }
}
