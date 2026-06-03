<?php

declare(strict_types=1);

namespace Moox\Scopes\Installers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer für das Scopes-Package.
 *
 * Synchronisiert Scopes aus den Package-Configs (resources.*.scopes.allowed)
 * in die scopes-Tabelle — gleiche Logik wie `php artisan moox:scope`.
 * Voraussetzung: Migrationen (moox/core, scopes-Tabelle).
 */
class ScopesInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'scopes';
    }

    public function getLabel(): string
    {
        return 'Scopes (sync from config)';
    }

    /**
     * Nach Migrationen und Config-Publish, vor Plugins.
     */
    protected function getDefaultConfig(): array
    {
        $config = parent::getDefaultConfig();
        $config['priority'] = 25;

        return $config;
    }

    public function hasItemSelection(): bool
    {
        // Keine Item-Auswahl, Sync läuft als Ganzes.
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        // Wenn es bereits Scopes gibt, war der Sync schon einmal gelaufen.
        if (! Schema::hasTable('scopes')) {
            return false;
        }

        return DB::table('scopes')->exists();
    }

    public function install(array $assets): bool
    {
        if (! Schema::hasTable('scopes')) {
            note('ℹ️ Table scopes not found. Run migrations first (moox core).');

            return false;
        }

        try {
            note('🔗 Syncing scopes from package configs (moox:scope) …');

            if ($this->command) {
                $exitCode = $this->command->call('moox:scope');
            } else {
                $exitCode = Artisan::call('moox:scope');
            }

            if ($exitCode !== 0) {
                error('⚠️ Scope sync failed (moox:scope exit code '.$exitCode.').');

                return false;
            }

            note('✅ Scopes synced from config.');

            return true;
        } catch (\Throwable $e) {
            error('⚠️ Scope sync failed: '.$e->getMessage());

            return false;
        }
    }
}
