<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Moox\Builder\Types\AbstractType;
use Moox\Builder\Types\ArrayType;
use Moox\Builder\Types\BooleanType;
use Moox\Builder\Types\DateTimeType;
use Moox\Builder\Types\EnumType;
use Moox\Builder\Types\FileType;
use Moox\Builder\Types\ImageType;
use Moox\Builder\Types\NumericType;
use Moox\Builder\Types\PasswordType;
use Moox\Builder\Types\RelationType;
use Moox\Builder\Types\StringType;
use Moox\Builder\Types\TextType;
use Moox\Builder\Types\UrlType;

class MigrationAnalyzer
{
    private const SYSTEM_COLUMNS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    private array $columnTypeMap = [
        'string' => StringType::class,
        'text' => TextType::class,
        'mediumText' => TextType::class,
        'longText' => TextType::class,
        'json' => ArrayType::class,
        'jsonb' => ArrayType::class,
        'boolean' => BooleanType::class,
        'integer' => NumericType::class,
        'bigInteger' => NumericType::class,
        'decimal' => NumericType::class,
        'float' => NumericType::class,
        'date' => DateTimeType::class,
        'datetime' => DateTimeType::class,
        'timestamp' => DateTimeType::class,
    ];

    private array $specialNamePatterns = [
        'password' => PasswordType::class,
        'url' => UrlType::class,
        'link' => UrlType::class,
        'image' => ImageType::class,
        'photo' => ImageType::class,
        'picture' => ImageType::class,
        'file' => FileType::class,
        'attachment' => FileType::class,
        'document' => FileType::class,
    ];

    public function analyzeBlueprint(Blueprint $blueprint): array
    {
        $columns = [];
        $relationships = [];

        foreach ($blueprint->getColumns() as $column) {
            if (in_array($column->getName(), self::SYSTEM_COLUMNS)) {
                continue;
            }

            if ($this->isRelationship($column)) {
                $relationships[] = $this->analyzeRelationship($column);

                continue;
            }

            $columns[$column->getName()] = $this->analyzeColumn($column);
        }

        return [
            'columns' => $columns,
            'relationships' => $relationships,
        ];
    }

    private function analyzeColumn(ColumnDefinition $column): AbstractType
    {
        foreach ($this->specialNamePatterns as $pattern => $typeClass) {
            if (str_contains($column->getName(), $pattern)) {
                return new $typeClass;
            }
        }

        if (! empty($column->getAttributes()['allowed'] ?? [])) {
            return new EnumType;
        }

        $baseType = $column->getType();
        $typeClass = $this->columnTypeMap[$baseType] ?? StringType::class;

        return new $typeClass;
    }

    private function isRelationship(ColumnDefinition $column): bool
    {
        return str_ends_with($column->getName(), '_id')
            || ($column->getAttributes()['foreign'] ?? false);
    }

    private function analyzeRelationship(ColumnDefinition $column): array
    {
        $name = str_replace('_id', '', $column->getName());

        return [
            'name' => $name,
            'type' => new RelationType,
            'nullable' => $column->getNullable(),
            'multiple' => false,
            'related_table' => $column->getAttributes()['on'] ?? $name.'s',
        ];
    }
}
