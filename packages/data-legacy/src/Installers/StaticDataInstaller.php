<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Installers;

use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;
use Moox\DataLegacy\Jobs\ImportStaticDataJob;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer für die statischen Daten aus dem Data-Package.
 *
 * Führt den Import-Job aus, der Länder, Sprachen, Zeitzonen etc.
 * aus der REST Countries API einspielt. Darauf kann u. a.
 * die Localization-Installation (Default-English) aufbauen.
 */
class StaticDataInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'static-data';
    }

    public function getLabel(): string
    {
        return 'Static Data (countries, languages, timezones)';
    }

    /**
     * Höhere Priorität, damit die Daten vor anderen Installern kommen.
     */
    protected function getDefaultConfig(): array
    {
        $config = parent::getDefaultConfig();
        $config['priority'] = 15;

        return $config;
    }

    public function hasItemSelection(): bool
    {
        // Keine Item-Auswahl, Import läuft als Ganzes.
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        // Wenn es bereits Sprachen gibt, gehen wir davon aus,
        // dass der Import schon einmal gelaufen ist.
        if (! Schema::hasTable('static_languages')) {
            return false;
        }

        return Schema::hasTable('static_languages')
            && \DB::table('static_languages')->exists();
    }

    public function install(array $assets): bool
    {
        if (! Schema::hasTable('static_languages')) {
            note('ℹ️ Table static_languages not found. Run migrations first (data + core).');

            return false;
        }

        try {
            note('🌍 Importing static data (countries, languages, timezones) …');

            // Synchronously run the import job (like the --sync option of the command).
            (new ImportStaticDataJob)->handle();

            note('✅ Static data import completed.');

            return true;
        } catch (\Throwable $e) {
            error('⚠️ Static data import failed: '.$e->getMessage());

            return false;
        }
    }
}
