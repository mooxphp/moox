<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Services\PackageService;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait InstallPackage
{
    use RegisterFilamentPlugin;
    use SelectFilamentPanel;
    use CheckOrCreateFilamentUser;

    protected PackageService $packageService;

    public function setPackageService(PackageService $packageService): void
    {
        $this->packageService = $packageService;
    }

    protected function ensurePackageServiceIsSet(): void
    {
        if (! isset($this->packageService)) {
            throw new \RuntimeException('PackageService is not set on InstallPackage trait.');
        }
    }

    protected function requirePackage(string $package): void
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

        if (! isset($composerJson['require'][$package])) {
            info("📦 Füge Package {$package} via composer require hinzu...");

            $command = "composer require {$package}:* 2>&1";
            exec($command, $output, $returnVar);

            // 👉 Ausgabe von composer require anzeigen (neutral)
            foreach ($output as $line) {
                line("    " . $line); // neutrales Grau, statt info()
            }

            if ($returnVar !== 0) {
                warning("❌ Fehler beim composer require {$package}.");
                throw new \RuntimeException("Composer require für {$package} fehlgeschlagen.");
            }

            info("✅ Package {$package} erfolgreich installiert.");
        } else {
            info("✅ Package {$package} ist bereits installiert.");
        }
    }



    public function installPackage(array $package, array $panelPaths): void
    {
        if (empty($package) || ! isset($package['name'])) {
            warning('⚠️ Empty or invalid package. Skip installation.');
            return;
        }

        if (isset($package['composer'])) {
            $this->requirePackage($package['composer']);
        }

        $this->ensurePackageServiceIsSet();

        info("🚀 Installiere Paket: {$package['name']}");

        $this->runMigrations($package);
        $this->publishConfig($package);
        $this->runSeeders($package);
        $this->installPlugins($package, $panelPaths);

        $this->checkOrCreateFilamentUser();

        info("🛠️ Führe filament:upgrade aus...");
        Artisan::call('filament:upgrade', ['--force' => true]);
        info("✅ Upgrade abgeschlossen.");
    }

    protected function runMigrations(array $package): void
    {
        info('🔍 Prüfe Migrationen...');

        $migrations = $this->packageService->getMigrations($package);

        if (empty($migrations)) {
            info("ℹ️ Keine Migrationen gefunden für {$package['name']}.");
            return;
        }

        foreach ($migrations as $migration) {
            info("➡️ Prüfe Migration: {$migration}");

            $status = $this->packageService->checkMigrationStatus($migration);

            if ($status['hasChanges']) {
                if ($status['hasDataInDeletedFields']) {
                    if (! confirm("❗ Migration '{$migration}' entfernt Spalten mit Daten. Trotzdem fortfahren?", false)) {
                        warning("⏭️ Migration '{$migration}' übersprungen.");
                        continue;
                    }
                }

                info("📥 Führe Migration {$migration} aus...");
                $exitCode = Artisan::call('migrate', [
                    '--path' => $migration,
                    '--force' => true,
                ]);
                info("✅ Migration abgeschlossen (Exit Code: {$exitCode})");
            } else {
                info("⏭️ Keine Änderungen in {$migration}, übersprungen.");
            }
        }
    }

    protected function publishConfig(array $package): void
    {
        $configs = $this->packageService->getConfig($package);

        foreach ($configs as $path => $content) {
            $publishPath = config_path(basename($path));

            if (! file_exists($publishPath)) {
                info("📄 Veröffentliche neue Konfig: {$path}");
                File::put($publishPath, $content);
                continue;
            }

            $existingContent = File::get($publishPath);
            if ($existingContent === $content) {
                info("✅ Konfiguration {$path} ist aktuell.");
                continue;
            }

            if (confirm("⚠️ Config-Datei {$path} hat Änderungen. Überschreiben?", false)) {
                info("🔄 Aktualisiere Config-Datei: {$path}");
                File::put($publishPath, $content);
            } else {
                warning("⏭️ Konfig {$path} wurde nicht überschrieben.");
            }
        }
    }

    protected function runSeeders(array $package): void
    {
        $requiredSeeders = $this->packageService->getRequiredSeeders($package);

        foreach ($requiredSeeders as $seeder) {
            $table = $this->getSeederTable($seeder);

            if (! $table || ! Schema::hasTable($table)) {
                warning("⚠️ Tabelle für Seeder {$seeder} nicht gefunden. Überspringe.");
                continue;
            }

            if (DB::table($table)->count() === 0) {
                info("🌱 Seed initialer Daten in {$table}...");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
                continue;
            }

            if (confirm("📂 Tabelle '{$table}' enthält bereits Daten. Trotzdem neu seeden?", false)) {
                info("🔁 Seed wiederhole für {$table}...");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
            } else {
                warning("⏭️ Seeder für {$table} übersprungen.");
            }
        }
    }

    public function installPlugins(array $package, array $panelPaths): void
    {
        $plugins = $this->packageService->getPlugins($package);

        if (empty($plugins)) {
            info("ℹ️ Keine Plugins im Paket '{$package['name']}'.");
            return;
        }

        foreach ($panelPaths as $panelPath) {
            info("🔌 Registriere Plugins für Panel: {$panelPath}");
            $this->registerPlugins($panelPath, $package);
        }
    }

    private function getSeederTable(string $seederClass): ?string
    {
        $seeder = new $seederClass;
        return property_exists($seeder, 'table') ? $seeder->table : null;
    }
}
