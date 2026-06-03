<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Installers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer für das Backup-Server-Package.
 *
 * Publiziert die Spatie Laravel Backup Server Konfiguration und Migrationen
 * und führt die Migrationen aus. Wird vom Moox-Installer ausgeführt.
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
        $config['priority'] = 10;

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

        return $this->hasBackupServerMigration() || Schema::hasTable('backup_server_sources');
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

            if (! $this->hasBackupServerMigration() && ! Schema::hasTable('backup_server_sources')) {
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

            if (! Schema::hasTable('backup_server_sources')) {
                note('🔄 Running Backup Server migrations…');

                if ($this->command) {
                    $this->command->call('migrate', ['--force' => true]);
                } else {
                    Artisan::call('migrate', ['--force' => true]);
                }

                note('✅ Backup Server migrations executed.');
            } else {
                note('ℹ️ Backup Server tables already exist, skipping migrate.');
            }

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
        $migrationPath = database_path('migrations');

        if (! File::isDirectory($migrationPath)) {
            return false;
        }

        foreach (File::files($migrationPath) as $file) {
            if (str_contains($file->getFilename(), 'create_backup_server_tables')) {
                return true;
            }
        }

        return false;
    }
}
