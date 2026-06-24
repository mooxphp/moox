<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\FieldType;

class TabFieldType extends FieldType
{
    public static function key(): string
    {
        return 'tab';
    }

    public function storesValue(): bool
    {
        return false;
    }

    public function hasSubFields(): bool
    {
        return true;
    }

    public function formComponent(FieldDefinition $field): Component
    {
        throw new \LogicException('Tab fields are compiled by SchemaCompiler, not as standalone components.');
    }
}
