<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait InstallPackage
{
    use RegisterFilamentPlugin;
    use SelectFilamentPanel;

    public function installPackage(array $package): void
    {
        $this->runMigrations($package);
        $this->publishConfig($package);
        $this->runSeeders($package);
        $this->installPlugins($package);
    }

    protected function runMigrations(array $package): void
    {
        $migrations = $this->packageService->getMigrations($package);

        foreach ($migrations as $migration) {
            $status = $this->packageService->checkMigrationStatus($migration);

            if ($status['hasChanges']) {
                if ($status['hasDataInDeletedFields']) {
                    if (! confirm(
                        "Migration {$migration} will delete fields containing data. Proceed?",
                        false
                    )) {
                        warning("Skipping migration {$migration}");

                        continue;
                    }
                }

                info("Running migration {$migration}");
                Artisan::call('migrate', [
                    '--path' => $migration,
                    '--force' => true,
                ]);
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

    public function installPlugins(array $package): void
    {
        $plugins = $this->packageService->getPlugins($package);
        if (empty($plugins)) {
            return;
        }

        $panelPaths = $this->selectFilamentPanel();
        if (empty($panelPaths)) {
            warning('No Filament panels selected, skipping plugin registration');

            return;
        }

        $paths = is_array($panelPaths) ? $panelPaths : [$panelPaths];
        foreach ($paths as $panelPath) {
            foreach ($plugins as $plugin) {
                info("Registering plugin {$plugin} to panel {$panelPath}");
                $this->registerPlugins($panelPath);
            }
        }
    }
}
