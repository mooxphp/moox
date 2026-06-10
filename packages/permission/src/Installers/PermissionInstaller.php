<?php

declare(strict_types=1);

namespace Moox\Permission\Installers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer for the Permission package (Filament Shield + Spatie Permission).
 *
 * Publishes Shield and Spatie Permission configuration and migrations,
 * then runs the permission migration.
 */
class PermissionInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'permission-setup';
    }

    public function getLabel(): string
    {
        return 'Permission (Filament Shield config + Spatie migrations)';
    }

    protected function getDefaultConfig(): array
    {
        $config = parent::getDefaultConfig();
        $config['priority'] = 15;

        return $config;
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        if (! File::exists(config_path('filament-shield.php'))) {
            return false;
        }

        if (! File::exists(config_path('permission.php'))) {
            return false;
        }

        return Schema::hasTable('permissions') && Schema::hasTable('roles');
    }

    public function install(array $assets): bool
    {
        try {
            $force = $this->config['force'] ?? false;

            note('📦 Publishing Filament Shield configuration…');
            $this->publishTag('filament-shield-config', $force);

            if (! File::exists(config_path('filament-shield.php'))) {
                error('⚠️ filament-shield.php was not published.');

                return false;
            }

            note('📦 Publishing Spatie Permission configuration…');
            $this->publishTag('permission-config', $force);

            if (! File::exists(config_path('permission.php'))) {
                error('⚠️ permission.php was not published.');

                return false;
            }

            if (! $this->hasPermissionMigration()) {
                note('📦 Publishing Spatie Permission migrations…');
                $this->publishTag('permission-migrations', $force);

                if (! $this->hasPermissionMigration()) {
                    error('⚠️ Permission migrations were not published.');

                    return false;
                }

                note('✅ Spatie Permission migrations published.');
            } else {
                note('ℹ️ Permission migrations already present, skipping publish.');
            }

            if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
                $migrationPath = $this->getPermissionMigrationPath();

                if ($migrationPath === null) {
                    error('⚠️ Permission migration file not found.');

                    return false;
                }

                note('🔄 Running Permission migrations…');
                $this->runPermissionMigration($migrationPath);
                note('✅ Permission migrations executed.');
            } else {
                note('ℹ️ Permission tables already exist, skipping migrate.');
            }

            note('✅ Filament Shield configuration is ready.');

            return true;
        } catch (\Throwable $e) {
            error('⚠️ Permission setup failed: '.$e->getMessage());

            return false;
        }
    }

    private function publishTag(string $tag, bool $force): void
    {
        $options = [
            '--tag' => $tag,
            '--force' => $force,
        ];

        if ($this->command) {
            $this->command->call('vendor:publish', $options);
        } else {
            Artisan::call('vendor:publish', $options);
        }
    }

    private function hasPermissionMigration(): bool
    {
        return $this->getPermissionMigrationPath() !== null;
    }

    private function getPermissionMigrationPath(): ?string
    {
        $migrationPath = database_path('migrations');

        if (! File::isDirectory($migrationPath)) {
            return null;
        }

        foreach (File::files($migrationPath) as $file) {
            if (str_contains($file->getFilename(), 'create_permission_tables')) {
                return 'database/migrations/'.$file->getFilename();
            }
        }

        return null;
    }

    /**
     * Spatie's permission migration flushes the permission cache. When the app
     * uses the database cache driver but the cache table does not exist yet
     * (common during moox:install), migration would fail without this guard.
     */
    private function runPermissionMigration(string $migrationPath): void
    {
        $previousDefault = config('cache.default');
        $previousPermissionCacheStore = config('permission.cache.store');

        config([
            'cache.default' => 'array',
            'permission.cache.store' => 'array',
        ]);

        app()->forgetInstance('cache');
        app()->forgetInstance('cache.store');

        try {
            $migrateOptions = [
                '--force' => true,
                '--path' => $migrationPath,
            ];

            if ($this->command) {
                $this->command->call('migrate', $migrateOptions);
            } else {
                Artisan::call('migrate', $migrateOptions);
            }
        } finally {
            config([
                'cache.default' => $previousDefault,
                'permission.cache.store' => $previousPermissionCacheStore,
            ]);

            app()->forgetInstance('cache');
            app()->forgetInstance('cache.store');
        }
    }
}
