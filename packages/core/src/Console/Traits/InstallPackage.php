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
        $this->info('Empty or invalid package data. Skipping.');
        return;
    }

    $this->ensurePackageServiceIsSet();

    $this->info("Checking package {$package['name']}");
    $this->runMigrations($package);
    $this->publishConfig($package);
    $this->runSeeders($package);
    $this->installPlugins($package, $panelPaths);
}


    protected function runMigrations(array $package): void
    {
        info('runMigrations() called');
        
        $migrations = $this->packageService->getMigrations($package);
        
        info('Migrations found: ' . print_r($migrations, true));
        
        if (empty($migrations)) {
            info("No migrations found for {$package['name']}");
            return;
        }
        
        foreach ($migrations as $migration) {
            info("Checking migration: {$migration}");
            
            $status = $this->packageService->checkMigrationStatus($migration);
            
            info("Migration status: " . print_r($status, true));
            
            if ($status['hasChanges']) {
                if ($status['hasDataInDeletedFields']) {
                    if (! confirm("Migration {$migration} will delete fields containing data. Proceed?", false)) {
                        warning("Skipping migration {$migration}");
                        continue;
                    }
                }
                
                info("Running migration {$migration}");
                $exitCode = Artisan::call('migrate', [
                    '--path' => $migration,
                    '--force' => true,
                ]);
                info("Artisan migrate exit code: {$exitCode}");
            } else {
                info("No changes detected for migration {$migration}, skipping.");
            }
        }
    }


    protected function publishConfig(array $package): void
    {
        $configs = $this->packageService->getConfig($package);

        foreach ($configs as $path => $content) {
            $publishPath = config_path(basename($path));

            if (! file_exists($publishPath)) {
                info("Publishing new config file: {$path}");
                File::put($publishPath, $content);

                continue;
            }

            $existingContent = File::get($publishPath);
            if ($existingContent === $content) {
                info("Config file {$path} is up to date");

                continue;
            }

            if (confirm("Config file {$path} has changes. Overwrite?", false)) {
                info("Updating config file: {$path}");
                File::put($publishPath, $content);
            } else {
                warning("Skipping config file: {$path}");
            }
        }
    }

    protected function runSeeders(array $package): void
    {
        $requiredSeeders = $this->packageService->getRequiredSeeders($package);

        foreach ($requiredSeeders as $seeder) {
            $table = $this->getSeederTable($seeder);

            if (! $table || ! Schema::hasTable($table)) {
                warning("Could not determine table for seeder {$seeder}, skipping");

                continue;
            }

            if (DB::table($table)->count() === 0) {
                info("Seeding required data for {$table}");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);

                continue;
            }

            if (confirm("Table {$table} already has data. Run seeder anyway?", false)) {
                info("Seeding required data for {$table}");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
            } else {
                warning("Skipping seeder for {$table}");
            }
        }
    }

    public function installPlugins(array $package, array $panelPaths): void
    {
        $plugins = $this->packageService->getPlugins($package);
        if (empty($plugins)) {
            info("No plugins found for {$package['name']}");

            return;
        }

        foreach ($panelPaths as $panelPath) {
            info("Registering plugins for panel {$panelPath}");
            $this->registerPlugins($panelPath, $package);
        }
    }

    private function getSeederTable(string $seederClass): ?string
    {
        $seeder = new $seederClass;

        return property_exists($seeder, 'table') ? $seeder->table : null;
    }
}
