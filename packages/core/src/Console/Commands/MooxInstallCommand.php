<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Support\Facades\File;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Installer\InstallerRegistry;
use Moox\Core\Installer\Traits\HasConfigurableInstallers;
use Moox\Core\Installer\Traits\HasCustomInstallers;
use Moox\Core\Installer\Traits\HasInstallationHooks;
use Moox\Core\Installer\Traits\HasSkippableInstallers;
use Moox\Prompts\Support\FlowCommand;

use function Moox\Prompts\error;
use function Moox\Prompts\info;
use function Moox\Prompts\multiselect;
use function Moox\Prompts\note;

/**
 * Moox Package Installer Command.
 *
 * A dynamic, configurable installer for Moox packages.
 * Supports:
 * - Custom installers via traits
 * - Skipping/only running specific installers
 * - Configuration-driven behavior
 * - Lifecycle hooks
 */
class MooxInstallCommand extends FlowCommand
{
    use Art;
    use CheckForFilament;
    use HasConfigurableInstallers;
    use HasCustomInstallers;
    use HasInstallationHooks;
    use HasSkippableInstallers;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moox:install
        {--debug : Show detailed information about all packages}
        {--skip=* : Skip specific installers (migrations, configs, translations, seeders, plugins)}
        {--only=* : Only run specific installers}
        {--force : Force overwrite existing assets}';

    protected $description = 'Install Moox packages that extend MooxServiceProvider';

    public ?bool $filamentInstalled = false;

    public ?bool $registryInitialized = null;

    public ?array $mooxProvidersScanned = null;

    public ?array $assets = null;

    protected ?InstallerRegistry $registry = null;

    public function promptFlowSteps(): array
    {
        return [
            'stepArt',
            'stepIntro',
            'stepCheckForFilament',
            'stepInitializeRegistry',
            'stepScanMooxProviders',
            'stepCollectPackageAssets',
            'stepInstallAssets',
            'stepShowOutput',
        ];
    }

    public function stepArt(): void
    {
        $this->art();
    }

    public function stepIntro(): void
    {
        info('✨ Welcome to the Moox Installer!');
        note('This command will install the Moox packages that extend MooxServiceProvider.');
    }

    public function stepCheckForFilament(): int
    {
        if (! $this->checkForFilament(silent: true)) {
            $this->error('❌ Filament installation is required.');

            return self::FAILURE;
        }
        $this->filamentInstalled = true;

        return self::SUCCESS;
    }

    public function stepInitializeRegistry(): int
    {
        $this->initializeRegistry();
        $this->registryInitialized = true;

        return self::SUCCESS;
    }

    public function stepScanMooxProviders(): int
    {
        $this->mooxProvidersScanned = $this->scanMooxProviders();
        info('✅ Moox providers scanned found: '.count($this->mooxProvidersScanned));

        if (empty($this->mooxProvidersScanned)) {
            $this->error('⚠️ No packages extending MooxServiceProvider found.');
        } else {
            foreach ($this->mooxProvidersScanned as $packageName => $providerClass) {
                info("  • {$packageName}: {$providerClass}");
            }
        }

        return self::SUCCESS;
    }

    public function stepCollectPackageAssets(): int
    {
        if (empty($this->mooxProvidersScanned)) {
            error('⚠️ No packages extending MooxServiceProvider found.');

            return self::SUCCESS;
        }

        $this->assets = $this->collectPackageAssets();

        if (empty($this->assets)) {
            error('⚠️ No assets found to install.');

            return self::SUCCESS;
        }

        return self::SUCCESS;
    }

    public function stepInstallAssets(): int
    {
        $this->installAssets($this->assets);
        info('✅ Installation completed successfully!');

        return self::SUCCESS;
    }

    protected function scanMooxProviders(): array
    {
        $result = [];

        $composerPath = base_path('composer.json');
        if (! File::exists($composerPath)) {
            error('❌ Composer.json not found.');

            return $result;
        }

        $composer = json_decode(File::get($composerPath), true);
        $allPackages = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );

        $mooxPackages = array_filter(
            array_keys($allPackages),
            fn ($pkg) => str_starts_with($pkg, 'moox/')
        );

        if (empty($mooxPackages)) {
            error('❌ No Moox packages found.');

            return $result;
        }

        $debug = (bool) $this->option('debug');

        foreach ($mooxPackages as $packageName) {
            $providerClass = $this->getProviderClassFromPackage($packageName);

            if (! $providerClass) {
                if ($debug) {
                    error("  ⚠️ {$packageName}: No service provider found");
                }

                continue;
            }

            if ($this->isMooxProvider($providerClass)) {
                $result[$packageName] = $providerClass;
            } elseif ($debug) {
                error("  • {$packageName}: {$providerClass} (not Moox provider)");
            }
        }

        return $result;
    }

    public function stepShowOutput(): int
    {
        info($this->filamentInstalled ? '✅ Filament is installed.' : '❌ Filament installation is required.');

        return self::SUCCESS;
    }

    protected function initializeRegistry(): void
    {
        $registry = $this->buildConfiguredRegistry();

        $this->registry = $registry;

        // Register custom installers from traits
        $this->registerCustomInstallers($this->registry);

        // Apply command-line skip/only options
        $this->applySkipOptions($this->registry);

        // Apply force option if set
        if ($this->option('force')) {
            $this->registry->configureAll(['force' => true, 'skip_existing' => false]);
        }

        // Allow further customization
        $this->configureRegistry($this->registry);
    }

    protected function getProviderClassFromPackage(string $packageName): ?string
    {
        $packageParts = explode('/', $packageName);
        $packageDir = $packageParts[1] ?? null;

        $possiblePaths = [
            base_path("packages/{$packageDir}/composer.json"),
            base_path("vendor/{$packageName}/composer.json"),
        ];

        foreach ($possiblePaths as $composerPath) {
            if (File::exists($composerPath)) {
                $composer = json_decode(File::get($composerPath), true);
                $providerClasses = $composer['extra']['laravel']['providers'] ?? [];

                if (! empty($providerClasses)) {
                    return $providerClasses[0];
                }
            }
        }

        return null;
    }

    protected function isMooxProvider(string $providerClass): bool
    {
        if (! class_exists($providerClass)) {
            return false;
        }

        try {
            $reflection = new \ReflectionClass($providerClass);

            return $reflection->isSubclassOf(\Moox\Core\MooxServiceProvider::class);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function installAssets(array $assets): void
    {
        if (empty($assets)) {
            return;
        }

        // Group assets by type
        $groupedAssets = $this->groupAssetsByType($assets);

        if (empty($groupedAssets)) {
            return;
        }

        // Let user select which asset types to install
        $selectedTypes = $this->selectAssetTypes($groupedAssets);

        if (empty($selectedTypes)) {
            note('⏩ No asset types selected');

            return;
        }

        // Run installation with hooks
        $this->runWithHooks($this->registry, $assets, $selectedTypes);
    }

    protected function groupAssetsByType(array $assets): array
    {
        $grouped = [];
        $enabledInstallers = $this->registry->getEnabled();

        foreach ($assets as $packageName => $packageAssets) {
            $provider = $packageAssets['provider'] ?? null;

            foreach ($enabledInstallers as $type => $installer) {
                if (! isset($packageAssets[$type])) {
                    continue;
                }

                if (! isset($grouped[$type])) {
                    $grouped[$type] = [];
                }

                $grouped[$type][] = [
                    'package' => $packageName,
                    'data' => $packageAssets[$type],
                    'provider' => $provider,
                ];
            }
        }

        return $grouped;
    }

    protected function selectAssetTypes(array $groupedAssets): array
    {
        $typeOptions = [];
        $enabledInstallers = $this->registry->getEnabled();

        foreach ($groupedAssets as $type => $typeAssets) {
            $installer = $enabledInstallers[$type] ?? null;
            if (! $installer) {
                continue;
            }

            $totalItems = 0;
            $packageCount = 0;
            foreach ($typeAssets as $asset) {
                $totalItems += count($asset['data'] ?? []);
                $packageCount++;
            }

            $label = $installer->getLabel();
            $typeOptions["{$label} ({$totalItems} items from {$packageCount} package(s))"] = $type;
        }

        if (empty($typeOptions)) {
            return [];
        }

        $selectedLabels = multiselect(
            label: 'Select asset types to install:',
            options: array_keys($typeOptions),
            default: array_keys($typeOptions),
            scroll: (string) min(10, count($typeOptions)),
            required: false
        );

        $selectedTypes = [];
        foreach ($selectedLabels as $label) {
            if (isset($typeOptions[$label])) {
                $selectedTypes[] = $typeOptions[$label];
            }
        }

        return $selectedTypes;
    }

    protected function runWithHooks(InstallerRegistry $registry, array $assets, array $selectedTypes): void
    {
        $this->beforeInstall();

        $failedInstallers = [];

        foreach ($selectedTypes as $type) {
            try {
                $installer = $registry->get($type);
                if (! $installer) {
                    note("ℹ️ Installer '{$type}' not found, skipping");

                    continue;
                }

                $typeAssets = $this->filterAssetsByType($assets, $type, $installer);
                if (empty($typeAssets)) {
                    note("ℹ️ No assets for '{$type}', skipping");

                    continue;
                }

                $this->beforeInstaller($type);

                // Setze das Command-Objekt, damit Installer $this->command->call() verwenden können
                // Das ist wichtig, damit der IO-Context nach Prompts korrekt funktioniert
                if (method_exists($installer, 'setCommand')) {
                    $installer->setCommand($this);
                }

                $installer->install($typeAssets);
                $this->afterInstaller($type);
            } catch (\Exception $e) {
                $failedInstallers[] = $type;
                error("⚠️ Installer '{$type}' failed: {$e->getMessage()}");
                // Continue with next installer instead of stopping
            }
        }

        if (! empty($failedInstallers)) {
            error('⚠️ Some installers failed: '.implode(', ', $failedInstallers));
        }

        $this->afterInstall();
    }

    protected function collectPackageAssets(): array
    {
        $assets = [];

        // PHASE 1: Registry mit allen (auch Custom-)Installern aufbauen
        foreach ($this->mooxProvidersScanned as $packageName => $providerClass) {
            try {
                $instance = new $providerClass(app());

                if (method_exists($instance, 'getCustomInstallers')) {
                    foreach ($instance->getCustomInstallers() as $installer) {
                        $this->registry->register($installer->getType(), $installer);
                    }
                }
            } catch (\Exception $e) {
                error("⚠️ Failed to register custom installers for {$packageName}: {$e->getMessage()}");
            }
        }

        // Jetzt einmal alle aktivierten Installer holen (inkl. Custom)
        $enabledInstallers = $this->registry->getEnabled();

        // PHASE 2: Assets pro Package und Installer-Typ einsammeln
        foreach ($this->mooxProvidersScanned as $packageName => $providerClass) {
            try {
                $instance = new $providerClass(app());
                $mooxInfo = $instance->mooxInfo();

                $packageAssets = [
                    'provider' => $providerClass,
                    'publishTags' => $mooxInfo['publishTags'] ?? [],
                ];

                // Standard-Assets aus mooxInfo je Installer-Typ
                foreach ($enabledInstallers as $type => $installer) {
                    $items = $installer->getItemsFromMooxInfo($mooxInfo);
                    if (! empty($items)) {
                        $packageAssets[$type] = $items;
                    }
                }

                // Optionale Custom-Assets vom Service Provider
                if (method_exists($instance, 'getCustomInstallAssets')) {
                    $customAssets = $instance->getCustomInstallAssets();
                    if (is_array($customAssets)) {
                        foreach ($customAssets as $customAsset) {
                            $customType = $customAsset['type'] ?? 'unknown';
                            if (isset($customAsset['data'])) {
                                $packageAssets[$customType] = array_merge(
                                    $packageAssets[$customType] ?? [],
                                    $customAsset['data']
                                );
                            }
                        }
                    }
                }

                // Nur Packages aufnehmen, die mehr als provider + publishTags haben
                if (count($packageAssets) > 2) {
                    $assets[$packageName] = $packageAssets;
                }
            } catch (\Exception $e) {
                error("⚠️ Failed to collect assets for {$packageName}: {$e->getMessage()}");
            }
        }

        return $assets;
    }
}
