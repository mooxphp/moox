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

    public function installPackage(array $package, array $panelPaths): void
    {
        if (empty($package) || !isset($package['name'])) {
            warning('⚠️ Empty or invalid package data. Skipping.');
            return;
        }

        $this->ensurePackageServiceIsSet();

        info("📦 Installing package: {$package['name']}");
        $this->runMigrations($package);
        $this->publishConfig($package);
        $this->runSeeders($package);
        $this->installPlugins($package, $panelPaths);
    }

    protected function runMigrations(array $package): void
    {
        info('🔍 Checking for migrations...');
        
        $migrations = $this->packageService->getMigrations($package);
        
        if (empty($migrations)) {
            info("ℹ️ No migrations found for {$package['name']}.");
            return;
        }
        
        foreach ($migrations as $migration) {
            info("➡️ Checking migration: {$migration}");
            
            $status = $this->packageService->checkMigrationStatus($migration);
            
            if ($status['hasChanges']) {
                if ($status['hasDataInDeletedFields']) {
                    if (! confirm("❗ Migration '{$migration}' removes columns with existing data. Continue anyway?", false)) {
                        warning("⏭️ Skipped migration '{$migration}'.");
                        continue;
                    }
                }
                
                info("🚀 Running migration {$migration}...");
                $exitCode = Artisan::call('migrate', [
                    '--path' => $migration,
                    '--force' => true,
                ]);
                info("✅ Migration completed (Exit Code: {$exitCode})");
            } else {
                info("⏭️ No changes detected in {$migration}, skipping.");
            }
        }
    }

    protected function publishConfig(array $package): void
    {
        $configs = $this->packageService->getConfig($package);

        foreach ($configs as $path => $content) {
            $publishPath = config_path(basename($path));

            if (! file_exists($publishPath)) {
                info("📄 Publishing new config file: {$path}");
                File::put($publishPath, $content);
                continue;
            }

            $existingContent = File::get($publishPath);
            if ($existingContent === $content) {
                info("✅ Config file {$path} is already up to date.");
                continue;
            }

            if (confirm("⚠️ Config file {$path} has changed. Overwrite?", false)) {
                info("🔄 Updating config file: {$path}");
                File::put($publishPath, $content);
            } else {
                warning("⏭️ Skipping overwrite of config file {$path}.");
            }
        }
    }

    protected function runSeeders(array $package): void
    {
        $requiredSeeders = $this->packageService->getRequiredSeeders($package);

        foreach ($requiredSeeders as $seeder) {
            $table = $this->getSeederTable($seeder);

            if (! $table || ! Schema::hasTable($table)) {
                warning("⚠️ Could not determine table for seeder {$seeder}. Skipping.");
                continue;
            }

            if (DB::table($table)->count() === 0) {
                info("🌱 Seeding initial data for table {$table}...");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
                continue;
            }

            if (confirm("📂 Table '{$table}' already contains data. Reseed anyway?", false)) {
                info("🔁 Reseeding data for {$table}...");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
            } else {
                warning("⏭️ Seeder skipped for {$table}.");
            }
        }
    }

    public function installPlugins(array $package, array $panelPaths): void
    {
        $plugins = $this->packageService->getPlugins($package);
        if (empty($plugins)) {
            info("ℹ️ No plugins found for package '{$package['name']}'.");
            return;
        }

        foreach ($panelPaths as $panelPath) {
            info("🔌 Registering plugins for panel: {$panelPath}");
            $this->registerPlugins($panelPath, $package);
        }
    }

    private function getSeederTable(string $seederClass): ?string
    {
        $seeder = new $seederClass;
        return property_exists($seeder, 'table') ? $seeder->table : null;
    }
}
