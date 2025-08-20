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
            info("📦 Adding package {$package} via composer require...");

            $command = "composer require {$package}:* 2>&1";
            exec($command, $output, $returnVar);

            foreach ($output as $line) {
                info("    " . $line);
            }

            if ($returnVar !== 0) {
                warning("❌ Error running composer require {$package}.");
                throw new \RuntimeException("Composer require for {$package} failed.");
            }

            info("✅ Package {$package} installed successfully.");
        } else {
            info("✅ Package {$package} is already installed.");
        }
    }

    public function installPackage(array $package, array $panelPaths = []): void
    {
        if (empty($package) || ! isset($package['name'])) {
            warning('⚠️ Empty or invalid package. Skipping installation.');
            return;
        }

        if (isset($package['composer'])) {
            $this->requirePackage($package['composer']);
        }

        $this->ensurePackageServiceIsSet();

        info("🚀 Installing package: {$package['name']}");

        $this->runMigrations($package);
        $this->publishConfig($package);
        $this->runSeeders($package);

        if (empty($panelPaths)) {
            $panelPaths = $this->determinePanelsForPackage($package);
        }

        
        $this->installPlugins($package, $panelPaths);


        $this->checkOrCreateFilamentUser();

        info("🛠️ Running filament:upgrade...");
        Artisan::call('filament:upgrade');
        info("✅ Upgrade completed.");
    }

    protected function determinePanelsForPackage(array $package): array
    {
        $existingPanels = $this->getExistingPanelsWithLogin();

        if (empty($existingPanels)) {
            info("ℹ️ No existing panels found. Creating a new panel...");
            $newPanel = $this->createNewPanelProvider();
            return [$newPanel];
        }

        info("🔹 Existing panels found:");
        foreach ($existingPanels as $key => $panel) {
            info("  [{$key}] {$panel}");
        }

        $useExisting = confirm("Do you want to install '{$package['name']}' in an existing panel?", true);

        if ($useExisting) {
            $selectedKey = $this->selectFromList($existingPanels, "Select panel for '{$package['name']}'");
            $selectedPanel = $existingPanels[$selectedKey];
            info("✅ Installing in existing panel: {$selectedPanel}");
            return [$selectedPanel];
        }

        info("ℹ️ Creating a new panel for '{$package['name']}'...");
        $newPanel = $this->createNewPanelProvider();
        return [$newPanel];
    }

    public function installPlugins(array $package, array $panelPaths): void
    {
        $plugins = $this->packageService->getPlugins($package);

        if (empty($plugins)) {
            info("ℹ️ No plugins found in package '{$package['name']}'.");
            return;
        }

        foreach ($panelPaths as $panelPath) {
            info("🔌 Registering plugins for panel: {$panelPath}");
            $this->registerPlugins($panelPath, $package);
        }
    }

    protected function createNewPanelProvider(): string
    {
        $panelName = 'Panel' . time();
        info("Creating new panel provider: {$panelName} ...");

        Artisan::call('make:filament-panel', ['name' => $panelName]);

        return $panelName;
    }

    protected function runMigrations(array $package): void
    {
        info('🔍 Checking migrations...');

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
                    if (! confirm("❗ Migration '{$migration}' removes columns with data. Continue anyway?", false)) {
                        warning("⏭️ Skipped migration '{$migration}'.");
                        continue;
                    }
                }

                info("📥 Running migration {$migration}...");
                $exitCode = Artisan::call('migrate', [
                    '--path' => $migration,
                    '--force' => true,
                ]);
                info("✅ Migration completed (Exit Code: {$exitCode})");
            } else {
                info("⏭️ No changes in {$migration}, skipped.");
            }
        }
    }

    protected function publishConfig(array $package): void
    {
        $configs = $this->packageService->getConfig($package);

        foreach ($configs as $path => $content) {
            $publishPath = config_path(basename($path));

            if (! file_exists($publishPath)) {
                info("📄 Publishing new config: {$path}");
                File::put($publishPath, $content);
                continue;
            }

            $existingContent = File::get($publishPath);
            if ($existingContent === $content) {
                info("✅ Config {$path} is up to date.");
                continue;
            }

            if (confirm("⚠️ Config file {$path} has changes. Overwrite?", false)) {
                info("🔄 Updating config file: {$path}");
                File::put($publishPath, $content);
            } else {
                warning("⏭️ Config {$path} was not overwritten.");
            }
        }
    }

    protected function runSeeders(array $package): void
    {
        $requiredSeeders = $this->packageService->getRequiredSeeders($package);

        foreach ($requiredSeeders as $seeder) {
            $table = $this->getSeederTable($seeder);

            if (! $table || ! Schema::hasTable($table)) {
                warning("⚠️ Table for seeder {$seeder} not found. Skipping.");
                continue;
            }

            if (DB::table($table)->count() === 0) {
                info("🌱 Seeding initial data into {$table}...");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
                continue;
            }

            if (confirm("📂 Table '{$table}' already contains data. Seed again anyway?", false)) {
                info("🔁 Re-running seeder for {$table}...");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
            } else {
                warning("⏭️ Seeder for {$table} skipped.");
            }
        }
    }

    private function getSeederTable(string $seederClass): ?string
    {
        $seeder = new $seederClass;
        return property_exists($seeder, 'table') ? $seeder->table : null;
    }

    protected function getExistingPanelsWithLogin(): array
    {
        $panels = [];
        $panelPath = app_path('Filament/Pages');

        if (! is_dir($panelPath)) {
            return [];
        }

        $files = scandir($panelPath);
        foreach ($files as $file) {
            if (str_ends_with($file, '.php')) {
                $panels[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return $panels;
    }

    protected function selectFromList(array $items, string $prompt): int
    {
        info($prompt);

        foreach ($items as $key => $item) {
            info("  [{$key}] {$item}");
        }

        $choice = (int) readline("Enter number: ");
        if (! isset($items[$choice])) {
            warning("Invalid selection, defaulting to first item.");
            return 0;
        }

        return $choice;
    }
}
