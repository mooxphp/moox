<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Services\PackageService;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait InstallPackage
{
    use CheckOrCreateFilamentUser;
    use RegisterFilamentPlugin;
    use SelectFilamentPanel;

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

    protected function requirePackage(string $package): string
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

        if (isset($composerJson['require'][$package])) {
            return 'already';
        }

        $isPathRepo = false;
        if (isset($composerJson['repositories'])) {
            foreach ($composerJson['repositories'] as $repo) {
                if (($repo['type'] ?? null) === 'path' &&
                    str_contains($repo['url'], str_replace('moox/', '', $package))) {
                    $isPathRepo = true;
                    break;
                }
            }
        }

        // Path-Repo: nur Composer.json updaten, kein "composer require"
        if ($isPathRepo) {
            info("ℹ️ Local path repo detected for {$package}, adding to composer.json...");
            $this->addPackageToComposerJson($package);

            return 'already';
        }

        $version = '*';
        $command = "composer require {$package}:{$version} --no-scripts --quiet 2>&1";
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            warning("❌ Error running composer require {$package}.");
            throw new \RuntimeException("Composer require for {$package} failed.");
        }

        // Immer Composer.json updaten
        $this->addPackageToComposerJson($package);

        info("✅ Installed package: {$package}");

        return 'installed';
    }

    protected function addPackageToComposerJson(string $package, string $version = '*'): void
    {
        $composerPath = base_path('composer.json');
        $composerJson = json_decode(file_get_contents($composerPath), true);

        if (! isset($composerJson['require'][$package])) {
            $composerJson['require'][$package] = $version;

            // Alphabetisch sortieren
            ksort($composerJson['require']);

            file_put_contents($composerPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            info("✅ Added {$package} to global composer.json require");
        }
    }

    public function installPackage(array $package, array $panelPaths = []): bool
    {
        if (empty($package) || ! isset($package['name'])) {
            warning('⚠️ Empty or invalid package. Skipping installation.');

            return false;
        }

        $didChange = false;

        if (isset($package['composer'])) {
            $status = $this->requirePackage($package['composer']);
            if ($status === 'installed') {
                info("✅ Installed: {$package['name']}");
                $didChange = true;
            }
        }

        $this->ensurePackageServiceIsSet();

        $didChange = $this->runMigrations($package) || $didChange;
        $didChange = $this->publishConfig($package) || $didChange;
        $didChange = $this->runSeeders($package) || $didChange;
        $didChange = $this->runAutoCommands($package) || $didChange;

        if (empty($panelPaths)) {
            $panelPaths = $this->determinePanelsForPackage($package);
            if (! empty($panelPaths)) {
                $didChange = true;
            }
        }

        $didChange = $this->installPlugins($package, $panelPaths) || $didChange;

        if ($didChange) {
            info('🎉 Installation completed.');
        }

        return $didChange;
    }

    protected function determinePanelsForPackage(array $package): array
    {
        $existingPanels = $this->getExistingPanelsWithLogin();

        if (empty($existingPanels)) {
            info('ℹ️ No existing panels found. Creating a new panel...');
            $newPanel = $this->createNewPanelProvider();

            return [$newPanel];
        }

        info('🔹 Existing panels found:');
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

    public function installPlugins(array $package, array $panelPaths): bool
    {
        $plugins = $this->packageService->getPlugins($package);

        if (empty($plugins)) {
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
        $panelName = 'Panel'.time();
        info("Creating new panel provider: {$panelName} ...");

        Artisan::call('make:filament-panel', ['name' => $panelName]);

        return $panelName;
    }

    protected function runMigrations(array $package): bool
    {
        $migrations = $this->packageService->getMigrations($package);
        if (empty($migrations)) {
            return false;
        }

        $didRun = false;
        foreach ($migrations as $migration) {
            $absolutePath = base_path($migration);
            if (! $this->hasMigrationsAtPath($absolutePath)) {
                info("ℹ️ No migrations found at: {$migration}. Skipping.");

                continue;
            }

            $status = $this->packageService->checkMigrationStatus($migration);

            if ($status['hasChanges']) {
                if ($status['hasDataInDeletedFields'] && ! confirm("❗ Migration '{$migration}' removes columns with data. Continue anyway?", false)) {
                    warning("⏭️ Skipped migration '{$migration}'.");

                    continue;
                }

                info("📥 Running migration {$migration}...");
                $exitCode = Artisan::call('migrate', [
                    '--path' => $migration,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                info("✅ Migration completed (Exit Code: {$exitCode})");
                $didRun = true;
            }
        }

        return $didRun;
    }

    private function hasMigrationsAtPath(string $absolutePath): bool
    {
        if (File::isFile($absolutePath)) {
            return str_ends_with($absolutePath, '.php');
        }
        if (File::isDirectory($absolutePath)) {
            $files = collect(File::files($absolutePath))
                ->filter(fn ($f) => str_ends_with($f->getFilename(), '.php'));

            return $files->isNotEmpty();
        }

        return false;
    }

    protected function publishConfig(array $package): bool
    {
        $configs = $this->packageService->getConfig($package);
        $updatedAny = false;

        foreach ($configs as $path => $content) {
            if (is_string($path) && str_starts_with($path, 'tag:')) {
                $tag = substr($path, 4);
                info("📦 Publishing vendor tag: {$tag}");
                Artisan::call('vendor:publish', [
                    '--tag' => $tag,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                $updatedAny = true;

                continue;
            }

            $publishPath = config_path(basename($path));
            if (! file_exists($publishPath)) {
                info("📄 Publishing new config: {$path}");
                File::put($publishPath, $content);
                $updatedAny = true;

                continue;
            }

            $existingContent = File::get($publishPath);
            if ($existingContent === $content) {
                continue;
            }

            if (confirm("⚠️ Config file {$path} has changes. Overwrite?", false)) {
                info("🔄 Updating config file: {$path}");
                File::put($publishPath, $content);
                $updatedAny = true;
            } else {
                warning("⏭️ Config {$path} was not overwritten.");
            }
        }

        return $updatedAny;
    }

    protected function runSeeders(array $package): bool
    {
        $requiredSeeders = $this->packageService->getRequiredSeeders($package);
        $didSeed = false;

        foreach ($requiredSeeders as $seeder) {
            $table = $this->getSeederTable($seeder);

            if (! $table || ! Schema::hasTable($table)) {
                warning("⚠️ Table for seeder {$seeder} not found. Skipping.");

                continue;
            }

            if (DB::table($table)->count() === 0 || confirm("📂 Table '{$table}' already contains data. Seed again anyway?", false)) {
                info("🌱 Seeding data into {$table}...");
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
                $didSeed = true;
            } else {
                warning("⏭️ Seeder for {$table} skipped.");
            }
        }

        return $didSeed;
    }

    protected function runAutoCommands(array $package): bool
    {
        $rootCmds = $this->packageService->getAutoRunCommands($package);
        $hereCmds = $this->packageService->getAutoRunHereCommands($package);

        if (empty($rootCmds) && empty($hereCmds)) {
            return false;
        }

        if (! confirm('🚀 Run post-install commands (auto_run/auto_runhere)?', true)) {
            warning('⏭️ Post-install commands skipped.');

            return false;
        }

        $ranAny = false;
        foreach ($rootCmds as $cmd) {
            info("▶️  {$cmd}");
            $this->execInCwd($cmd, base_path());
            $ranAny = true;
        }
        foreach ($hereCmds as $entry) {
            $cmd = $entry['cmd'];
            $cwd = $entry['cwd'];
            info("▶️  (in {$cwd}) {$cmd}");
            $this->execInCwd($cmd, $cwd);
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
                if ($line !== '') {
                    info('    '.$line);
                }
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

        $choice = (int) readline('Enter number: ');
        if (! isset($items[$choice])) {
            warning('Invalid selection, defaulting to first item.');

            return 0;
        }

        return $choice;
    }
}
