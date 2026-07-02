<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Moox\Builder\Models\FieldValue;

final class TypedValueColumns
{
    /**
     * @return list<string>
     */
    public static function valueColumns(): array
    {
        return [
            'value_string',
            'value_text',
            'value_decimal',
            'value_date',
            'value_datetime',
            'value_boolean',
            'value_json',
        ];
    }

    public static function columnForType(string $type): string
    {
        return match ($type) {
            'textarea', 'rich_text' => 'value_text',
            'number', 'range' => 'value_decimal',
            'date' => 'value_date',
            'datetime' => 'value_datetime',
            'toggle' => 'value_boolean',
            'multiselect', 'checkbox_list', 'link', 'image', 'gallery', 'file', 'group', 'repeater', 'flexible_content' => 'value_json',
            'button_group' => 'value_string',
            default => 'value_string',
        };
    }

    public static function isColumnableType(string $type): bool
    {
        if ($type === 'password') {
            return false;
        }

        return self::columnForType($type) !== 'value_json';
    }

    public static function isImageColumnType(string $type): bool
    {
        return in_array($type, ['image', 'gallery'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyColumns(): array
    {
        return array_fill_keys(self::valueColumns(), null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributesFor(string $type, mixed $value): array
    {
        $columns = self::emptyColumns();
        $column = self::columnForType($type);
        $columns[$column] = $value;

        return $columns;
    }

    public static function read(FieldValue $row, string $type): mixed
    {
        $column = self::columnForType($type);

        return $row->getAttribute($column);
    }
}
