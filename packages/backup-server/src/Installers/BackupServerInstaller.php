<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Installers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer for the Backup Server package.
 *
 * Publishes Spatie Laravel Backup Server configuration and migration files,
 * then runs only the backup-server migration (not all pending migrations).
 */
class BackupServerInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'backup-server-setup';
    }

    public function getLabel(): string
    {
        return 'Backup Server (Spatie config + migrations)';
    }

    protected function getDefaultConfig(): array
    {
        $config = parent::getDefaultConfig();
        $config['priority'] = 8;

        return $config;
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        if (! File::exists(config_path('backup-server.php'))) {
            return false;
        }

        return $this->hasBackupServerMigration();
    }

    public function install(array $assets): bool
    {
        try {
            $force = $this->config['force'] ?? false;

            note('📦 Publishing Spatie Laravel Backup Server configuration…');
            $this->publish('backup-server-config', $force);

            $configPath = config_path('backup-server.php');
            if (! File::exists($configPath)) {
                error('⚠️ backup-server.php was not published.');

                return false;
            }

            note('✅ Spatie Laravel Backup Server config published.');

            if (! $this->hasBackupServerMigration()) {
                note('📦 Publishing Spatie Laravel Backup Server migrations…');
                $this->publish('backup-server-migrations', $force);

                if (! $this->hasBackupServerMigration()) {
                    error('⚠️ Backup Server migration was not published.');

                    return false;
                }

                note('✅ Spatie Laravel Backup Server migrations published.');
            } else {
                note('ℹ️ Backup Server migrations already present, skipping publish.');
            }

            $migrationPath = $this->getBackupServerMigrationPath();
            if ($migrationPath === null) {
                error('⚠️ Backup Server migration file not found.');

                return false;
            }

            note('🔄 Running Backup Server migration…');

            $migrateOptions = [
                '--force' => true,
                '--path' => $migrationPath,
            ];

            if ($this->command) {
                $this->command->call('migrate', $migrateOptions);
            } else {
                Artisan::call('migrate', $migrateOptions);
            }

            note('✅ Backup Server migration executed.');

            return true;
        } catch (\Throwable $e) {
            error('⚠️ Backup Server setup failed: '.$e->getMessage());

            return false;
        }
    }

    private function publish(string $tag, bool $force): void
    {
        $options = [
            '--provider' => 'Spatie\BackupServer\BackupServerServiceProvider',
            '--tag' => $tag,
            '--force' => $force,
        ];

        if ($this->command) {
            $this->command->call('vendor:publish', $options);
        } else {
            Artisan::call('vendor:publish', $options);
        }
    }

    private function hasBackupServerMigration(): bool
    {
        return $this->getBackupServerMigrationPath() !== null;
    }

    private function getBackupServerMigrationPath(): ?string
    {
        $migrationPath = database_path('migrations');

        if (! File::isDirectory($migrationPath)) {
            return null;
        }

        foreach (File::files($migrationPath) as $file) {
            if (str_contains($file->getFilename(), 'create_backup_server_tables')) {
                return 'database/migrations/'.$file->getFilename();
            }
        }

        return null;
    }
}
