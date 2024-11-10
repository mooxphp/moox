<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;

class MigrationFinder
{
    public function __construct(
        private readonly Migrator $migrator
    ) {}

    public function findMigrationForTable(string $tableName): ?string
    {
        $possiblePaths = [
            database_path('migrations'),
            base_path('packages/builder/database/migrations'),
        ];

        foreach ($possiblePaths as $path) {
            $files = File::glob($path.'/*_create_'.$tableName.'_table.php');
            if (! empty($files)) {
                return $files[0];
            }
        }

        return null;
    }

    public function extractBlueprintFromFile(string $filePath): ?Blueprint
    {
        $content = File::getContent($filePath);

        if (preg_match('/Schema::create\([\'"](.+?)[\'"]\s*,\s*function\s*\(Blueprint\s+\$table\)\s*{(.+?)}\);/s', $content, $matches)) {
            $blueprint = new Blueprint($matches[1]);

            // Create a temporary file to evaluate the schema
            $tempFile = tempnam(sys_get_temp_dir(), 'migration_');
            file_put_contents($tempFile, '<?php
                $table = new \Illuminate\Database\Schema\Blueprint("'.$matches[1].'");
                '.$matches[2].'
                return $table;
            ');

            $blueprint = require $tempFile;
            unlink($tempFile);

            return $blueprint;
        }

        return null;
    }
}
