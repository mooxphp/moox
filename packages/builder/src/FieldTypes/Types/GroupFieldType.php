<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

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
        return app(SchemaCompiler::class)->buildGroupComponent($field);
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
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
            return [];
        }

        if (array_is_list($stored) && isset($stored[0]) && is_array($stored[0])) {
            return $stored[0];
        }

        return $stored;
    }
}
