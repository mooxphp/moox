<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Console\Traits\CheckForMooxPackages;
use Moox\Core\Console\Traits\CheckOrCreateFilamentUser;
use Moox\Core\Console\Traits\InstallPackage;
use Moox\Core\Console\Traits\InstallPackages;
use Moox\Core\Console\Traits\SelectFilamentPanel;
use Moox\Core\Services\PackageService;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class MooxInstaller extends Command
{
    use Art,
        CheckForFilament,
        CheckForMooxPackages,
        CheckOrCreateFilamentUser,
        InstallPackage,
        InstallPackages,
        SelectFilamentPanel;

    protected $signature = 'moox:install';

    protected $description = 'Install Moox Packages or generate Filament Panels.';

    protected array $selectedPanels = [];

    public function __construct(protected PackageService $packageService)
    {
        parent::__construct();
        $this->setPackageService($packageService);
    }

    public function handle(): void
    {
        $this->art();
        $this->info('✨ Welcome to the Moox Installer!');

        $choice = select(
            label: 'What would you like to do?',
            options: [
                'packages' => '📦 Install Moox Packages',
                'panels' => '🖼️ Generate Filament Panels',
            ]
        );

        if (! $this->checkForFilament(silent: true)) {
            $this->error('❌ Filament installation is required or was aborted.');

            return;
        }

        match ($choice) {
            'packages' => $this->runPackageInstallFlow(),
            'panels' => $this->runPanelGenerationFlow(),
        };
    }

    protected function isPanelGenerationMode(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            if (($frame['function'] ?? null) === 'runPanelGenerationFlow') {
                return true;
            }
        }

        return false;
    }

    protected function runPackageInstallFlow(): void
    {
        $available = $this->getPackagesFromComposerRequire();
        if (empty($available)) {
            $this->warn('⚠️ No Moox packages found in composer.json require.');

            return;
        }

        sort($available);

        $this->info('Please select the packages you want to install from composer.json:');

        $selection = multiselect(
            label: 'Select Moox packages (from composer.json require) to install/configure:',
            options: array_combine($available, $available),
            required: true
        );

        if (empty($selection)) {
            $this->warn('⚠️ No selection made. Aborting.');

            return;
        }

        $this->warnIfPackagesAlreadyRegistered($selection);

        $selectedPanelKey = $this->determinePanelForPackage(implode(', ', $selection));
        $this->ensurePanelForKey($selectedPanelKey, $selectedPanelKey, false);

        $providerPath = $this->panelMap[$selectedPanelKey]['path'].'/'.ucfirst($selectedPanelKey).'PanelProvider.php';

        $changedAny = false;
        foreach ($selection as $package) {
            $packageData = ['name' => $package, 'composer' => $package];
            $changed = $this->installPackage($packageData, [$providerPath]);
            $changedAny = $changedAny || $changed;

            $this->updatePanelPackageComposerJson($selectedPanelKey, [$package]);
        }

        if ($changedAny) {
            $this->info('⚙️ Finalizing (package discovery + Filament upgrade)...');
            $this->callSilent('package:discover');
            $this->callSilent('filament:upgrade');
        }

        $this->info('🎉 Selected packages have been installed successfully: '.implode(', ', $selection));
    }

    protected function getPackagesFromComposerRequire(): array
    {
        $composerPath = base_path('composer.json');
        if (! file_exists($composerPath)) {
            return [];
        }

        $json = json_decode(file_get_contents($composerPath), true);
        $require = $json['require'] ?? [];

        return array_values(array_filter(array_keys($require), function ($pkg) {
            return is_string($pkg) && str_starts_with($pkg, 'moox/');
        }));
    }

    protected function runPanelGenerationFlow(): void
    {
        $this->setAutoRequireComposer(false);

        $existingPanels = $this->getExistingPanelsWithLogin();

        if (! empty($existingPanels)) {
            $this->info('➡️ You can still create additional panels.');
            $this->error('❌ A panel with login already exists:');
            foreach ($existingPanels as $panelClass) {
                $this->line("  • {$panelClass}");
            }
        }

        $this->selectedPanels = $this->selectPanels();

        if (empty($this->selectedPanels)) {
            $this->warn('⚠️ No panel selection made. Operation aborted.');

            return;
        }

        $this->info('ℹ️ Panels created/updated. Skipped composer require in panel-generation mode.');

        foreach ($this->selectedPanels as $panel) {
            if ($panel === 'press') {
                $this->checkOrCreateWpUser();
            } else {
                $this->checkOrCreateFilamentUser();
            }
        }

        $this->installPluginsFromGeneratedPanels();

        $this->info('✅ Moox Panels installed successfully. Enjoy! 🎉');
    }

    protected function determinePanelForPackage(string $package): string
    {
        $providerClasses = $this->getProviderClassesFromBootstrap();

        $panelOptions = [];
        foreach ($providerClasses as $index => $class) {
            $key = $this->mapProviderClassToPanelKey($class);
            if (! $key) {
                continue;
            }
            $uniqueKey = $key.'_'.$index;
            $panelOptions[$uniqueKey] = $key.' ('.$class.')';
        }

        $panelChoice = select(
            label: "Would you like to register the package '{$package}' into an existing panel or create a new panel?",
            options: [
                'existing' => 'Existing Panel',
                'new' => 'Create New Panel',
            ]
        );

        if ($panelChoice === 'existing') {
            if (empty($panelOptions)) {
                $this->warn('⚠️ No existing panels found. Creating a new panel instead.');

                return $this->selectNewPanel([]);
            }

            $selectedUniqueKey = select(
                label: 'Select an existing panel:',
                options: $panelOptions
            );

            return explode('_', $selectedUniqueKey)[0];
        } else {
            $existingKeys = array_values(array_filter(array_map(function ($class) {
                return $this->mapProviderClassToPanelKey($class);
            }, $providerClasses)));

            return $this->selectNewPanel($existingKeys);
        }
    }

    protected function selectNewPanel(array $existingPanels): string
    {
        $allPanels = array_keys($this->panelMap);
        $availablePanels = array_diff($allPanels, $existingPanels);

        if (empty($availablePanels)) {
            $this->warn('⚠️ No new panels available, using default.');

            return reset($allPanels);
        }

        $panelOptions = array_combine($availablePanels, $availablePanels);

        return select(
            label: 'Select a new panel:',
            options: $panelOptions
        );
    }

    protected function getExistingPanelsWithLogin(): array
    {
        $providerClasses = $this->getProviderClassesFromBootstrap();

        $panels = [];
        foreach ($providerClasses as $class) {
            if (! class_exists($class)) {
                continue;
            }

            if (is_subclass_of($class, \Filament\PanelProvider::class)) {
                if (method_exists($class, 'login')) {
                    $panels[] = $class;
                }
            }
        }

        return $panels;
    }

    protected function warnIfPackagesAlreadyRegistered(array $packages): void
    {
        $already = [];
        foreach ($packages as $pkg) {
            $panels = $this->findPanelsContainingPackage($pkg);
            if (! empty($panels)) {
                $already[$pkg] = $panels;
            }
        }

        if (! empty($already)) {
            $this->warn('⚠️ Selected packages are already registered in panels:');
            foreach ($already as $pkg => $panels) {
                $this->line('  • '.$pkg.' → '.implode(', ', $panels));
            }
        }
    }

    protected function findPanelsContainingPackage(string $package): array
    {
        $panels = [];
        foreach ($this->panelMap as $key => $cfg) {
            $panelPath = $cfg['path'] ?? null;
            if (! $panelPath) {
                continue;
            }
            $composerJsonPath = base_path($panelPath.'/../../composer.json');
            if (! File::exists($composerJsonPath)) {
                continue;
            }
            $composerJson = json_decode(File::get($composerJsonPath), true);
            if (isset($composerJson['require']) && array_key_exists($package, $composerJson['require'])) {
                $panels[] = $key;
            }
        }

        return $panels;
    }

    protected function updatePanelPackageComposerJson(string $panelKey, array $packages): void
    {
        $panelPath = base_path($this->panelMap[$panelKey]['path'].'/../../composer.json');
        if (! file_exists($panelPath)) {
            $this->warn("⚠️ Panel composer.json not found for {$panelKey}, skipping update.");

            return;
        }

        $composer = json_decode(file_get_contents($panelPath), true);
        foreach ($packages as $pkg) {
            $composer['require'][$pkg] = '*';
        }

        file_put_contents($panelPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("✅ Updated composer.json for panel '{$panelKey}' with package(s): ".implode(', ', $packages));
    }

    protected function installPluginsFromGeneratedPanels(): void
    {
        $this->info('🔎 Search for plugin packages in the generated panels...');

        if (empty($this->selectedPanels)) {
            $this->warn('⚠️ No panels selected. Skipping plugin installation.');

            return;
        }

        $panelCount = count($this->selectedPanels);

        foreach ($this->selectedPanels as $i => $panelKey) {
            $panelInfo = $this->panelMap[$panelKey] ?? null;

            if (! $panelInfo) {
                $this->warn("⚠️ Unknown panel key '{$panelKey}'. Skipping plugin scan.");

                continue;
            }

            $panelClass = ($panelInfo['namespace'] ?? null)
                ? $panelInfo['namespace'].'\\'.ucfirst($panelKey).'PanelProvider'
                : null;

            if (! $panelClass || ! class_exists($panelClass)) {
                $this->warn("⚠️ Panel provider class '{$panelClass}' does not exist. Skipping plugin scan.");

                continue;
            }

            $this->newLine();
            $this->line(str_repeat('═', 60));
            $this->info('🧩 ['.($i + 1)."/{$panelCount}] Processing panel: {$panelKey}");
            $this->line(str_repeat('═', 60));
            $this->newLine();

            $providerInstance = new $panelClass(app());

            $panel = new \Filament\Panel;
            $configuredPanel = $providerInstance->panel($panel);

            $plugins = $configuredPanel->getPlugins() ?? [];

            if (empty($plugins)) {
                $this->info("ℹ️ No plugins found in panel '{$panelKey}'.");

                continue;
            }

            // --- Plugin-Klassen in Composer-Pakete umwandeln ---
            $packagesToInstall = [];
            foreach ($plugins as $plugin) {
                $class = get_class($plugin);

                foreach ($this->pluginPackageMap as $prefix => $package) {
                    if (str_starts_with($class, $prefix)) {
                        $packagesToInstall[] = $package;
                        break;
                    }
                }
            }

            $packagesToInstall = array_unique($packagesToInstall);

            if (empty($packagesToInstall)) {
                $this->info("ℹ️ No composer packages detected for panel '{$panelKey}'.");

                continue;
            }

            $this->info('📦 Detected plugin packages:');
            foreach ($packagesToInstall as $pkg) {
                $this->line("   • {$pkg}");
            }

            $providerPath = $panelInfo['path'].'/'.ucfirst($panelKey).'PanelProvider.php';

            // --- Pakete installieren ---
            foreach ($packagesToInstall as $pkg) {
                $this->line("\n─────────────────────────────────────────────");
                $this->info("📦 Installing package: {$pkg}");

                $packageData = ['name' => $pkg, 'composer' => $pkg];

                try {
                    $this->installPackage($packageData, [$providerPath]);
                    $this->updatePanelPackageComposerJson($panelKey, [$pkg]);
                    $this->info(" ✔ Updated composer.json for {$panelKey} → {$pkg}");
                } catch (\RuntimeException $e) {
                    $this->warn("⚠️ Installation failed for '{$pkg}': {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->info("🎉 All plugins declared in the '{$panelKey}' panel were installed successfully!");
        }

        $this->newLine(2);
        $this->line(str_repeat('═', 60));
        $this->info('🎉 All selected panels processed successfully!');
        $this->info('✨ Moox Panels installed successfully. Enjoy!');
        $this->line(str_repeat('═', 60));
        $this->newLine();
    }

    protected function getProviderClassesFromBootstrap(): array
    {
        $bootstrapProvidersPath = base_path('bootstrap/providers.php');
        if (! file_exists($bootstrapProvidersPath)) {
            return [];
        }

        $content = file_get_contents($bootstrapProvidersPath);
        if ($content === false) {
            return [];
        }

        if (! preg_match_all('/([\\\\A-Za-z0-9_]+)::class/', $content, $matches)) {
            return [];
        }

        return $matches[1] ?? [];
    }

    protected function mapProviderClassToPanelKey(string $class): ?string
    {
        $classParts = explode('\\', $class);
        $name = end($classParts);

        return strtolower(str_replace('PanelProvider', '', $name));
    }
}
