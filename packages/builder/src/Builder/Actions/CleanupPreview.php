<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CleanupPreview
{
    public function __construct(
        private readonly string $entityName,
        private readonly string $entityPath,
    ) {}

    public function execute(): void
    {
        $this->dropEntityTable();
        $this->removePreviewFiles();
        $this->removeEntityFiles();
    }

    private function dropEntityTable(): void
    {
        $tableName = strtolower($this->entityName.'s');
        if (Schema::hasTable($tableName)) {
            Schema::dropIfExists($tableName);
        }
    }

    private function removePreviewFiles(): void
    {
        $previewPath = app_path('Preview');

        if (File::exists($previewPath)) {
            File::deleteDirectory($previewPath);
        }
    }

    private function removeEntityFiles(): void
    {
        $paths = [
            $this->entityPath.'/Models/'.$this->entityName.'.php',
            $this->entityPath.'/Resources/'.$this->entityName.'Resource.php',
            $this->entityPath.'/Plugins/'.$this->entityName.'Plugin.php',
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $migrationPattern = database_path('migrations/*create_'.strtolower($this->entityName).'s_table.php');
        $migrations = glob($migrationPattern);
        foreach ($migrations as $migration) {
            File::delete($migration);
        }
    }
}
