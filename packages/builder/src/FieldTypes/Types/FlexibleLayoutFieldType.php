<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\FieldType;

class FlexibleLayoutFieldType extends FieldType
{
    public static function key(): string
    {
        return 'flexible_layout';
    }

    public function isInternal(): bool
    {
        return true;
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
        throw new \LogicException('Flexible layout fields are definition-only and compiled by FlexibleContentFieldType.');
    }
}
