<?php

declare(strict_types=1);

namespace Moox\Builder\Traits;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

trait HandlesMigrationFiles
{
    protected function getMigrationPattern(): string
    {
        $tableName = $this->context->getTableName();
        $migrationPath = database_path('migrations');

        return match ($this->context->getContextType()) {
            'app' => $migrationPath.'/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_create_'.$tableName.'_table.php',
            'package' => $migrationPath.'/create_'.$tableName.'_table.php.stub',
            'preview' => $migrationPath.'/preview_[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_create_'.$tableName.'_table.php',
            default => throw new InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };
    }

    protected function findMigrationFile(): ?string
    {
        $entity = DB::table('builder_entities')
            ->where('singular', $this->context->getEntityName())
            ->where('build_context', $this->context->getContextType())
            ->whereNull('deleted_at')
            ->first();

        if ($entity) {
            $latestBuild = DB::table('builder_entity_builds')
                ->where('entity_id', $entity->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestBuild) {
                $files = json_decode((string) $latestBuild->files, true);
                if (isset($files['migration']) && file_exists($files['migration'])) {
                    return $files['migration'];
                }
            }
        }

        $pattern = $this->getMigrationPattern();
        $files = glob($pattern);

        return $files === [] || $files === false ? null : $files[0];
    }
}
