<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Preview;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PreviewManager
{
    public function createPreviewTable(string $entityName, array $blocks): void
    {
        $tableName = Str::plural(Str::snake($entityName));

        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }

        Schema::create($tableName, function (Blueprint $table) use ($blocks) {
            $table->id();

            foreach ($blocks as $block) {
                $migrations = $block->getMigrations()['fields'] ?? [];
                foreach ($migrations as $field) {
                    $this->addField($table, $field);
                }
            }

            $table->timestamps();
        });
    }

    public function dropPreviewTable(string $entityName): void
    {
        $tableName = Str::plural(Str::snake($entityName));

        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }
    }

    public function cleanupPreviewFiles(object $build): void
    {
        $files = json_decode($build->files ?? '[]', true);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
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
