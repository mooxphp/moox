<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\FieldType;

/**
 * Visual layout container. Groups the following fields into a titled, optionally
 * collapsible section. Unlike the group field it does not nest stored values —
 * its children store flat, exactly like tab fields. Compiled by SchemaCompiler.
 */
class SectionFieldType extends FieldType
{
    public static function key(): string
    {
        return 'section';
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
        throw new \LogicException('Section fields are compiled by SchemaCompiler, not as standalone components.');
    }
}
