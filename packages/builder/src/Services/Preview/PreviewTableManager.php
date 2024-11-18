<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Preview;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PreviewTableManager
{
    public function createTable(string $entityName, array $blocks): void
    {
        $tableName = Str::plural(Str::snake($entityName));
        $this->dropTableIfExists($tableName);

        Schema::create($tableName, function (Blueprint $table) use ($blocks) {
            $table->id();

            foreach ($blocks as $block) {
                $this->addBlockFields($table, $block);
            }

            $table->timestamps();
        });
    }

    public function dropTable(string $entityName): void
    {
        $this->dropTableIfExists(Str::plural(Str::snake($entityName)));
    }

    protected function dropTableIfExists(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }
    }

    protected function addBlockFields(Blueprint $table, object $block): void
    {
        $migrations = $block->getMigrations()['fields'] ?? [];
        foreach ($migrations as $field) {
            $this->addField($table, $field);
        }
    }

    protected function addField(Blueprint $table, array $field): void
    {
        $method = $field['type'];
        $name = $field['name'];
        $params = $field['parameters'] ?? [];

        $column = $table->$method($name, ...$params);

        if (! empty($field['modifiers'])) {
            foreach ($field['modifiers'] as $modifier => $args) {
                $column->$modifier(...$args);
            }
        }
    }
}
