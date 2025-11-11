<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
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
        if (!isset($this->packageService)) {
            throw new \RuntimeException('PackageService is not set on InstallPackage trait.');
        }
    }

    public function installPackage(array $package, array $panelPaths = []): bool
    {
        if (empty($package) || !isset($package['name'])) {
            warning('⚠️ Empty or invalid package. Skipping installation.');
            return false;
        }

        $didChange = false;
        $this->ensurePackageServiceIsSet();

        // --- Composer require ---
        if (isset($package['composer'])) {
            $status = $this->requirePackage($package['composer']);
            if ($status === 'installed') {
                info("✅ Installed '{$package['name']}' via composer.");
                $didChange = true;
            }
        }

        // --- Migrations publish & run ---
        $migrations = $this->packageService->getMigrations($package);
        if (!empty($migrations)) {
            if (confirm("📥 New migrations have been published. Would you like to run them now?", true)) {
                // --- Config publish ---
                if (confirm("📄 Would you like to publish configs for '{$package['name']}'?", true)) {
                    $didChange = $this->packageService->publishConfigs($package) || $didChange;
                }

                $didChange = $this->runMigrations($migrations) || $didChange;
            } else {
                info("⏩ Migrations were published but not executed.");
            }
        }

        // --- Seeders ---
        $seeders = $this->packageService->getRequiredSeeders($package);
        if (!empty($seeders)) {
            if (confirm("🌱 Run seeders for '{$package['name']}'?", false)) {
                $didChange = $this->runSeeders($package) || $didChange;
            } else {
                info("⏩ Skipped seeders by user.");
            }
        }

        // --- Panels & plugins ---
        if (empty($panelPaths)) {
            $panelPaths = $this->determinePanelsForPackage($package);
            if (!empty($panelPaths)) $didChange = true;
        }
        $didChange = $this->installPlugins($package, $panelPaths) || $didChange;

        // --- Post-install commands ---
        $didChange = $this->runAutoCommands($package) || $didChange;

        if ($didChange) {
            info("🎉 Installation for '{$package['name']}' completed!");
        } else {
            info("ℹ️ Nothing was changed for '{$package['name']}'.");
        }

        return $didChange;
    }

    /**
     * Install a composer package if not already installed.
     */
    protected function requirePackage(string $packageName): string
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        if (isset($composerJson['require'][$packageName])) {
            info("ℹ️ Package '{$packageName}' is already required in composer.json.");
            return 'already';
        }

        info("📦 Installing composer package: {$packageName}");
        $process = Process::fromShellCommandline("composer require {$packageName} --no-scripts --quiet");
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            foreach (explode("\n", rtrim($buffer)) as $line) {
                if ($line !== '') info('    ' . $line);
            }
        });

        if ($process->isSuccessful()) {
            return 'installed';
        }

        warning("⚠️ Failed to install {$packageName}");
        return 'failed';
    }

    protected function determinePanelsForPackage(array $package): array
    {
        $existingPanels = $this->getExistingPanelsWithLogin();

        if (empty($existingPanels)) {
            info("ℹ️ No existing panels found. Creating a new panel...");
            return [$this->createNewPanelProvider()];
        }

        info("🔹 Existing panels found:");
        foreach ($existingPanels as $key => $panel) info("  [{$key}] {$panel}");

        $useExisting = confirm("Do you want to install '{$package['name']}' in an existing panel?", true);
        if ($useExisting) {
            $selectedKey = $this->selectFromList($existingPanels, "Select panel for '{$package['name']}'");
            return [$existingPanels[$selectedKey]];
        }

        info("ℹ️ Creating a new panel for '{$package['name']}'...");
        return [$this->createNewPanelProvider()];
    }

    public function installPlugins(array $package, array $panelPaths): bool
    {
        $plugins = $this->packageService->getPlugins($package);
        if (empty($plugins)) {
            info("ℹ️ No plugins found for '{$package['name']}'. Skipping.");
            return false;
        }

        $changedAny = false;
        foreach ($panelPaths as $panelPath) {
            $changed = $this->registerPlugins($panelPath, $package);
            if ($changed) {
                info("🔌 Registered plugins for panel: {$panelPath}");
                $changedAny = true;
            }
        }
        return $changedAny;
    }

    protected function createNewPanelProvider(): string
    {
        $panelName = 'Panel' . time();
        info("Creating new panel provider: {$panelName} ...");
        Artisan::call('make:filament-panel', ['name' => $panelName]);
        return $panelName;
    }

    protected function runMigrations(array $migrations): bool
    {
        $didRun = false;
        foreach ($migrations as $migration) {
            $absolutePath = base_path($migration);
            if (!$this->hasMigrationsAtPath($absolutePath)) {
                info("ℹ️ No migrations found at: {$migration}. Skipping.");
                continue;
            }

            $relativePath = str_replace(base_path() . '/', '', $absolutePath);
            Artisan::call('migrate', [
                '--path' => $relativePath,
                '--force' => true,
                '--no-interaction' => true,
            ]);
            info("✅ Migration completed for {$relativePath}");
            $didRun = true;
        }
        return $didRun;
    }

    private function hasMigrationsAtPath(string $absolutePath): bool
    {
        if (File::isFile($absolutePath)) return str_ends_with($absolutePath, '.php');
        if (File::isDirectory($absolutePath)) {
            $files = collect(File::files($absolutePath))->filter(fn($f) => str_ends_with($f->getFilename(), '.php'));
            return $files->isNotEmpty();
        }
        return false;
    }

    protected function runSeeders(array $package): bool
    {
        $requiredSeeders = $this->packageService->getRequiredSeeders($package);
        $didSeed = false;

        foreach ($requiredSeeders as $seeder) {
            $table = $this->getSeederTable($seeder);
            if (!$table || !Schema::hasTable($table)) {
                warning("⚠️ Table for seeder {$seeder} not found. Skipping.");
                continue;
            }

            if (DB::table($table)->count() === 0 || confirm("📂 Table '{$table}' already contains data. Seed again anyway?", false)) {
                info("🌱 Seeding data into {$table}...");
                Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
                $didSeed = true;
            } else {
                warning("⏩ Seeder for {$table} skipped.");
            }
        }

        return $didSeed;
    }

    protected function runAutoCommands(array $package): bool
    {
        $rootCmds = $this->packageService->getAutoRunCommands($package);
        $hereCmds = $this->packageService->getAutoRunHereCommands($package);
        $ranAny = false;

        foreach ($rootCmds as $cmd) {
            info("▶️  {$cmd}");
            $this->execInCwd($cmd, base_path());
            $ranAny = true;
        }

        foreach ($hereCmds as $entry) {
            info("▶️  (in {$entry['cwd']}) {$entry['cmd']}");
            $this->execInCwd($entry['cmd'], $entry['cwd']);
            $ranAny = true;
        }

        return $ranAny;
    }

    protected function execInCwd(string $command, string $cwd): void
    {
        $process = Process::fromShellCommandline($command, $cwd);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            foreach (explode("\n", rtrim($buffer)) as $line) {
                if ($line !== '') info('    ' . $line);
            }
        });
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

        if (!is_dir($panelPath)) return [];

        foreach (scandir($panelPath) as $file) {
            if (str_ends_with($file, '.php')) $panels[] = pathinfo($file, PATHINFO_FILENAME);
        }

        return $panels;
    }

    protected function selectFromList(array $items, string $prompt): int
    {
        info($prompt);
        foreach ($items as $key => $item) info("  [{$key}] {$item}");

        $choice = (int)readline("Enter number: ");
        return $items[$choice] ?? 0;
    }
}
