<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\Installer\InstallerRegistry;
use Moox\Core\Installer\Traits\HasConfigurableInstallers;
use Moox\Core\Installer\Traits\HasCustomInstallers;
use Moox\Core\Installer\Traits\HasInstallationHooks;
use Moox\Core\Installer\Traits\HasSkippableInstallers;

use function Moox\Prompts\error;
use function Moox\Prompts\info;
use function Moox\Prompts\multiselect;
use function Moox\Prompts\note;
use function Moox\Prompts\warning;

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
class MooxInstallCommand extends Command
{
    use Art;
    use CheckForFilament;
    use HasConfigurableInstallers;
    use HasCustomInstallers;
    use HasInstallationHooks;
    use HasSkippableInstallers;

    protected $signature = 'moox:install 
        {--debug : Show detailed information about all packages}
        {--skip=* : Skip specific installers (migrations, configs, translations, seeders, plugins)}
        {--only=* : Only run specific installers}
        {--force : Force overwrite existing assets}';

    protected $description = 'Install Moox packages that extend MooxServiceProvider';

    protected array $mooxProviders = [];

    protected InstallerRegistry $registry;

    public function handle(): int
    {
        $this->art();
        info('✨ Welcome to the Moox Installer!');

        // Step 1: Check for Filament
        if (! $this->checkForFilament(silent: true)) {
            error('❌ Filament installation is required.');

            return self::FAILURE;
        }

        // Step 2: Initialize registry
        $this->initializeRegistry();

        // Step 3: Scan for MooxServiceProvider packages
        info('🔍 Scanning for Moox packages...');
        $this->scanMooxProviders();

        if (empty($this->mooxProviders)) {
            warning('⚠️ No packages extending MooxServiceProvider found.');

            return self::SUCCESS;
        }

        info('✅ Found '.count($this->mooxProviders).' Moox package(s)');

        // Step 4: Collect all assets from all packages
        $assets = $this->collectPackageAssets();

        if (empty($assets)) {
            warning('⚠️ No assets found to install.');

            return self::SUCCESS;
        }

        // Step 5: Install assets
        $this->installAssets($assets);

        info('✅ Installation completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Initialize the installer registry.
     */
    protected function initializeRegistry(): void
    {
        $this->registry = $this->buildConfiguredRegistry();

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

    /**
     * Collect assets from all Moox packages.
     */
    protected function collectPackageAssets(): array
    {
        $assets = [];
        $enabledInstallers = $this->registry->getEnabled();

        foreach ($this->mooxProviders as $packageName => $providerClass) {
            try {
                $instance = new $providerClass(app());
                $mooxInfo = $instance->mooxInfo();

                $packageAssets = [
                    'provider' => $providerClass,
                    'publishTags' => $mooxInfo['publishTags'] ?? [],
                ];

                // Collect assets for each enabled installer
                foreach ($enabledInstallers as $type => $installer) {
                    $items = $installer->getItemsFromMooxInfo($mooxInfo);
                    if (! empty($items)) {
                        $packageAssets[$type] = $items;
                    }
                }

                // Allow packages to add custom assets
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

                if (count($packageAssets) > 2) { // More than just 'provider' and 'publishTags'
                    $assets[$packageName] = $packageAssets;
                }
            } catch (\Exception $e) {
                warning("⚠️ Failed to collect assets for {$packageName}: {$e->getMessage()}");
            }
        }

        return $assets;
    }

    /**
     * Install collected assets.
     */
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

    /**
     * Group assets by installer type.
     */
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

    /**
     * Let user select which asset types to install.
     */
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
            scroll: min(10, count($typeOptions)),
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

    /**
     * Filter assets for a specific installer type.
     */
    protected function filterAssetsByType(array $assets, string $type, AssetInstallerInterface $installer): array
    {
        $typeAssets = [];

        foreach ($assets as $packageName => $packageAssets) {
            if (! isset($packageAssets[$type]) || empty($packageAssets[$type])) {
                continue;
            }

            $data = $packageAssets[$type];

            // Ensure data is always an array
            if (! is_array($data)) {
                $data = [$data];
            }

            $typeAssets[] = [
                'package' => $packageName,
                'data' => $data,
                'provider' => $packageAssets['provider'] ?? null,
                'publishTags' => $packageAssets['publishTags'] ?? [],
            ];
        }

        return $typeAssets;
    }

    /**
     * Scan for Moox providers.
     */
    protected function scanMooxProviders(): void
    {
        $composerPath = base_path('composer.json');
        if (! File::exists($composerPath)) {
            return;
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
            return;
        }

        foreach ($mooxPackages as $packageName) {
            $providerClass = $this->getProviderClassFromPackage($packageName);

            if (! $providerClass) {
                if ($this->option('debug')) {
                    note("  ⚠️ {$packageName}: No service provider found");
                }

                continue;
            }

            if ($this->isMooxProvider($providerClass)) {
                $this->mooxProviders[$packageName] = $providerClass;
            } elseif ($this->option('debug')) {
                note("  • {$packageName}: {$providerClass} (not Moox provider)");
            }
        }

        note('✅ Found '.count($this->mooxProviders).' Moox providers');
    }

    /**
     * Get provider class from package composer.json.
     */
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

    /**
     * Check if a class is a Moox provider.
     */
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

    /**
     * Get the installer registry.
     */
    public function getRegistry(): InstallerRegistry
    {
        return $this->registry;
    }
}

