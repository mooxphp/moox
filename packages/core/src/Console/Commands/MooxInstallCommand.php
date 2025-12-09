<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Console\Traits\SelectFilamentPanel;

use function Moox\Prompts\clear;
use function Moox\Prompts\confirm;
use function Moox\Prompts\error;
use function Moox\Prompts\info;
use function Moox\Prompts\multiselect;
use function Moox\Prompts\note;
use function Moox\Prompts\select;
use function Moox\Prompts\text;
use function Moox\Prompts\warning;
class MooxInstallCommand extends Command
{
    use Art, CheckForFilament, SelectFilamentPanel;

    protected $signature = 'moox:install {--debug : Show detailed information about all packages}';

    protected $description = 'Install Moox packages that extend MooxServiceProvider';

    protected array $mooxProviders = []; // packageName => providerClass

    public function handle(): int
    {
        $this->art();
        info('✨ Welcome to the Moox Installer!');

        // Step 1: Check for Filament
        if (! $this->checkForFilament(silent: true)) {
            error('❌ Filament installation is required.');

            return self::FAILURE;
        }

        // Step 2: Scan for MooxServiceProvider packages
        info('🔍 Scanning for Moox packages...');
        $this->scanMooxProviders();

        if (empty($this->mooxProviders)) {
            warning('⚠️ No packages extending MooxServiceProvider found.');

            return self::SUCCESS;
        }

        info('✅ Found '.count($this->mooxProviders).' Moox package(s)');
        

        // Step 3: Collect all assets from all packages
        $assets = $this->collectPackageAssets();

        if (empty($assets)) {
            warning('⚠️ No assets found to install.');

            return self::SUCCESS;
        }

        // Step 4: Install assets in batches (includes plugins)
        $this->installAssets($assets);

        
        info('✅ Installation completed successfully!');

        return self::SUCCESS;
    }

    protected function collectPackageAssets(): array
    {
        $assets = [];

        foreach ($this->mooxProviders as $packageName => $providerClass) {
            try {
                $instance = new $providerClass(app());
                $mooxInfo = $instance->mooxInfo();

                // Initialize package entry if not exists
                if (! isset($assets[$packageName])) {
                    $assets[$packageName] = [
                        'provider' => $providerClass,
                    ];
                }

                // Standard asset types - dynamically process all
                $standardAssetTypes = [
                    'migrations' => 'migrations',
                    'configFiles' => 'configs',
                    'translations' => 'translations',
                    'seeders' => 'seeders',
                    'plugins' => 'plugins',
                ];

                foreach ($standardAssetTypes as $mooxInfoKey => $assetType) {
                    if (! empty($mooxInfo[$mooxInfoKey])) {
                        $assets[$packageName][$assetType] = $mooxInfo[$mooxInfoKey];
                    }
                }

                // Allow packages to add custom assets via hook
                if (method_exists($instance, 'getCustomInstallAssets')) {
                    $customAssets = $instance->getCustomInstallAssets();
                    if (is_array($customAssets)) {
                        foreach ($customAssets as $customAsset) {
                            $customType = $customAsset['type'] ?? 'unknown';
                            if (! isset($assets[$packageName][$customType])) {
                                $assets[$packageName][$customType] = [];
                            }
                            if (isset($customAsset['data'])) {
                                $assets[$packageName][$customType] = array_merge(
                                    $assets[$packageName][$customType] ?? [],
                                    $customAsset['data']
                                );
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                warning("⚠️ Failed to collect assets for {$packageName}: {$e->getMessage()}");
            }
        }
        return $assets;
    }

    protected function installAssets(array $assets): void
    {
        if (empty($assets)) {
            return;
        }

        // Group assets by type (assets are now grouped by package)
        $groupedAssets = [];
        foreach ($assets as $packageName => $packageAssets) {
            $provider = $packageAssets['provider'] ?? null;
            
            foreach ($packageAssets as $type => $data) {
                // Skip 'provider' key
                if ($type === 'provider') {
                    continue;
                }
                
                if (! isset($groupedAssets[$type])) {
                    $groupedAssets[$type] = [];
                }
                
                $groupedAssets[$type][] = [
                    'package' => $packageName,
                    'data' => $data,
                    'provider' => $provider,
                ];
            }
        }

        if (empty($groupedAssets)) {
            return;
        }

        // Build options for multiselect with counts
        $typeOptions = [];
        $typeLabels = [
            'migrations' => 'Migrations',
            'configs' => 'Config Files',
            'translations' => 'Translations',
            'seeders' => 'Seeders',
            'plugins' => 'Plugins',
        ];

        foreach ($groupedAssets as $type => $typeAssets) {
            $totalItems = 0;
            $packageCount = 0;
            foreach ($typeAssets as $asset) {
                $totalItems += count($asset['data'] ?? []);
                $packageCount++;
            }
            $label = $typeLabels[$type] ?? ucfirst($type);
            $typeOptions["{$label} ({$totalItems} items from {$packageCount} package(s))"] = $type;
        }

        // Let user select which asset types to install
        $selectedTypes = multiselect(
            label: 'Select asset types to install:',
            options: array_keys($typeOptions),
            default: array_keys($typeOptions), // Select all by default
            scroll: min(10, count($typeOptions)),
            required: false
        );

        // Convert selected labels back to types
        $typesToInstall = [];
        foreach ($selectedTypes as $label) {
            if (isset($typeOptions[$label])) {
                $typesToInstall[] = $typeOptions[$label];
            }
        }

        // Install only selected types - with item-level multiselect
        foreach ($typesToInstall as $type) {
            if (isset($groupedAssets[$type])) {
                $typeLabel = $typeLabels[$type] ?? ucfirst($type);
                info("📦 Installing {$typeLabel}...");
                $this->installAssetType($type, $groupedAssets[$type]);
                note("✅ {$typeLabel} installation completed");
            }
        }
    }

    protected function installAssetType(string $type, array $assets): void
    {
        $installer = $this->getAssetInstaller($type);

        if (! $installer) {
            warning("⚠️ No installer found for asset type: {$type}");

            return;
        }

        // Collect all data and map items to packages
        $allData = [];
        $itemToPackageMap = [];
        $packages = [];

        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            $packages[] = $packageName;
            $data = $asset['data'] ?? [];

            foreach ($data as $item) {
                $allData[] = $item;
                $itemToPackageMap[$item] = $packageName;
            }
        }

        if (empty($allData)) {
            return;
        }

        // Show summary
        $typeLabels = [
            'migrations' => 'Migrations',
            'configs' => 'Config Files',
            'translations' => 'Translations',
            'seeders' => 'Seeders',
            'plugins' => 'Plugins',
        ];
        $label = $typeLabels[$type] ?? ucfirst($type);
        info("📦 {$label}: ".count($allData).' item(s) from '.count(array_unique($packages)).' package(s)');

        // Plugins have their own multiselect in installPlugins, skip item-level selection here
        if ($type === 'plugins') {
            call_user_func($installer, $type, $allData, $assets);
            return;
        }

        // Build multiselect options for individual items
        $itemOptions = [];
        foreach ($allData as $item) {
            $packageName = $itemToPackageMap[$item] ?? 'unknown';
            $itemOptions["{$item} ({$packageName})"] = $item;
        }

        // Let user select which items to install
        $selectedItemLabels = multiselect(
            label: "Select {$type} to install:",
            options: array_keys($itemOptions),
            default: array_keys($itemOptions), // Select all by default
            scroll: min(10, count($itemOptions)),
            required: false
        );

        // Convert selected labels back to items
        $selectedItems = [];
        foreach ($selectedItemLabels as $itemLabel) {
            if (isset($itemOptions[$itemLabel])) {
                $selectedItems[] = $itemOptions[$itemLabel];
            }
        }

        if (empty($selectedItems)) {
            note("⏩ No {$type} selected, skipping");
            return;
        }

        // Show what will be installed
        info("📦 Installing ".count($selectedItems)." {$label} item(s)...");

        // Filter assets to only include selected items
        $filteredAssets = [];
        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            $data = $asset['data'] ?? [];
            $filteredData = array_intersect($data, $selectedItems);
            
            if (! empty($filteredData)) {
                $filteredAssets[] = [
                    'package' => $packageName,
                    'data' => array_values($filteredData),
                    'provider' => $asset['provider'] ?? null,
                ];
            }
        }

        // Call the installer with filtered assets
        call_user_func($installer, $type, $selectedItems, $filteredAssets);
    }

    protected function getAssetInstaller(string $type): ?callable
    {
        $installers = [
            'migrations' => [$this, 'installMigrations'],
            'configs' => [$this, 'installConfigs'],
            'translations' => [$this, 'installTranslations'],
            'seeders' => [$this, 'installSeeders'],
            'plugins' => [$this, 'installPlugins'],
        ];

        // Allow custom installers to be registered
        if (method_exists($this, 'install'.ucfirst($type))) {
            return [$this, 'install'.ucfirst($type)];
        }

        return $installers[$type] ?? null;
    }

    protected function installMigrations(string $type, array $migrations, array $assets): void
    {
        $published = $this->publishAndInstallAssets($type, $assets, function () {
            try {
                Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
                info('✅ Migrations executed successfully');
            } catch (\Exception $e) {
                warning("⚠️ Migration error: {$e->getMessage()}");
            }
        });

        if (! $published) {
            note("⏩ Migrations were skipped or failed");
        }
    }

    protected function installConfigs(string $type, array $configs, array $assets): void
    {
        try {
            $published = $this->publishAndInstallAssets($type, $assets);
            
            if (! $published) {
                note("⏩ No configs were published (all already exist or no publish tags found)");
            }
        } catch (\Exception $e) {
            warning("⚠️ Configs installation failed: {$e->getMessage()}");
        }
    }

    protected function installTranslations(string $type, array $translations, array $assets): void
    {
        try {
            $published = $this->publishAndInstallAssets($type, $assets);
            
            if (! $published) {
                note("⏩ No translations were published (all already exist or no publish tags found)");
            }
        } catch (\Exception $e) {
            warning("⚠️ Translations installation failed: {$e->getMessage()}");
        }
    }

    protected function installPlugins(string $type, array $plugins, array $assets): void
    {
        // Plugins need special handling with panel selection
        // The $plugins array already contains the plugin class names from mooxInfo
        // We just need to map them to their packages for display
        
        $allPlugins = [];
        $packagePluginMap = [];

        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            $pluginList = $asset['data'] ?? [];

            foreach ($pluginList as $plugin) {
                // Plugin is already a full class name from mooxInfo
                // Don't use class_exists() as it can cause class loading conflicts
                // Just store the plugin name - validation will happen when registering
                $allPlugins[$plugin] = $plugin;
                $packagePluginMap[$plugin] = $packageName;
            }
        }

        if (empty($allPlugins)) {
            warning('⚠️ No plugins found');
            return;
        }

        // Loop: Install plugins in panels until user says no
        // Plugins can be installed in multiple panels, so we don't remove them from the list
        while (true) {
            // Clear terminal to reset state after Artisan calls
            clear();
            
            $panelPath = $this->selectOrCreatePanel();
            if (! $panelPath) {
                break;
            }
    
            info('✅ Selected Panel: '.basename($panelPath, '.php'));
    
            // Build display options for the plugins
            $pluginChoices = [];
            foreach ($allPlugins as $plugin) {
                $displayName = basename(str_replace('\\', '/', $plugin));
                $package = $packagePluginMap[$plugin] ?? 'unknown';
                $pluginChoices["{$displayName} ({$package})"] = $plugin;
            }
    
            // Multiselect which plugins to install in this panel
            $selectedLabels = multiselect(
                label: 'Select plugins to install in this panel:',
                options: array_keys($pluginChoices),
                default: array_keys($pluginChoices), // default: all selected
                scroll: min(10, count($pluginChoices)),
                required: false
            );
    
            if (empty($selectedLabels)) {
                note('⏩ No plugins selected for this panel');
            } else {
                $selectedPlugins = array_map(
                    fn ($label) => $pluginChoices[$label],
                    $selectedLabels
                );
        
                $this->installResolvedPlugins($selectedPlugins, $panelPath);
            }
    
            if (! confirm(label: 'Install plugins in another panel?', default: false)) {
                break;
            }
        }
    }

    /**
     * Generic method to publish and optionally install assets.
     * Handles checking, publishing, skipping, and feedback for all asset types.
     */
    protected function publishAndInstallAssets(string $type, array $assets, ?callable $afterPublish = null): bool
    {
        // No confirmation needed here - user already selected types via multiselect

        $published = false;
        $publishedPackages = [];
        $skippedPackages = [];
        $failedPackages = [];

        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            $itemNames = $asset['data'] ?? [];

            // Show progress
            note("  → Processing {$packageName}...");

            // Check if assets are already published/exist
            $alreadyExists = $this->checkAssetsExist($type, $packageName, $itemNames);

            if ($alreadyExists) {
                $skippedPackages[] = $packageName;
                note("  ℹ️ {$packageName}: {$type} already exist, skipping");

                continue;
            }

            // Try to publish
            note("    Publishing {$type} for {$packageName}...");
            if ($this->publishPackageAssets($packageName, $type)) {
                $published = true;
                $publishedPackages[] = $packageName;
                note("    ✅ Published");
            } else {
                $failedPackages[] = $packageName;
                note("    ⚠️ No publish tag found");
            }
        }

        // Show summary
        if ($published) {
            info('✅ '.ucfirst($type).' published for: '.implode(', ', $publishedPackages));
        }

        if (! empty($skippedPackages)) {
            note('ℹ️ Skipped (already exist): '.implode(', ', $skippedPackages));
        }

        if (! empty($failedPackages) && ! $published) {
            note('ℹ️ No '.$type.' were published (no publish tags found)');
        }

        // Run post-publish action if provided (e.g., migrate for migrations)
        // Only run if something was actually published
        if ($afterPublish && $published) {
            $afterPublish();
        }
        
        return $published || ! empty($skippedPackages);
    }

    /**
     * Generic method to check if assets already exist.
     */
    protected function checkAssetsExist(string $type, string $packageName, array $itemNames): bool
    {
        return match ($type) {
            'migrations' => $this->checkMigrationsPublished($packageName, $itemNames),
            'configs' => $this->checkConfigsPublished($packageName, $itemNames),
            'translations' => $this->checkTranslationsPublished($packageName, $itemNames),
            default => false,
        };
    }

    protected function checkMigrationsPublished(string $packageName, array $migrationNames): bool
    {
        if (empty($migrationNames)) {
            return false;
        }

        $migrationPath = database_path('migrations');

        if (! File::isDirectory($migrationPath)) {
            return false;
        }

        $existingFiles = File::files($migrationPath);

        foreach ($migrationNames as $migrationName) {
            foreach ($existingFiles as $file) {
                $filename = $file->getFilename();
                // Remove timestamp prefix if exists (format: YYYY_MM_DD_HHMMSS_name.php)
                $nameWithoutTimestamp = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
                $nameWithoutExtension = str_replace('.php', '', $nameWithoutTimestamp);

                if ($nameWithoutExtension === $migrationName || str_ends_with($nameWithoutExtension, '_'.$migrationName)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function installSeeders(string $type, array $seeders, array $assets): void
    {
        // Ask for confirmation
        
        if (! confirm(label: 'Run seeders for all packages?', default: false)) {
            note("⏩ Skipped {$type}");

            return;
        }

        $executed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            foreach ($asset['data'] as $seeder) {
                try {
                    $seederClass = $this->resolveSeederClass($packageName, $seeder);
                    if ($seederClass && class_exists($seederClass)) {
                        Artisan::call('db:seed', [
                            '--class' => $seederClass,
                            '--force' => true,
                            '--no-interaction' => true,
                        ]);
                        $executed++;
                    } else {
                        warning("  ⚠️ Seeder class not found: {$seeder}");
                        $failed++;
                    }
                } catch (\Exception $e) {
                    // Check if error is because data already exists
                    if (str_contains($e->getMessage(), 'already') || str_contains($e->getMessage(), 'duplicate')) {
                        note("  ℹ️ {$seeder}: Already seeded, skipping");
                        $skipped++;
                    } else {
                        warning("  ⚠️ Seeder error for {$seeder}: {$e->getMessage()}");
                        $failed++;
                    }
                }
            }
        }

        if ($executed > 0) {
            info("✅ Executed {$executed} seeder(s)");
        }
        if ($skipped > 0) {
            note("ℹ️ Skipped {$skipped} seeder(s) (already executed)");
        }
        if ($failed > 0) {
            warning("⚠️ {$failed} seeder(s) failed");
        }
    }


    protected function scanMooxProviders(): void
    {
        // Get all installed packages from composer.json
        $composerPath = base_path('composer.json');
        if (! File::exists($composerPath)) {
            return;
        }

        $composer = json_decode(File::get($composerPath), true);
        $allPackages = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );

        // Filter Moox packages
        $mooxPackages = array_filter(
            array_keys($allPackages),
            fn ($pkg) => str_starts_with($pkg, 'moox/')
        );

        if (empty($mooxPackages)) {
            return;
        }

        // Get provider classes directly from packages
        foreach ($mooxPackages as $packageName) {
            $providerClass = $this->getProviderClassFromPackage($packageName);

            if (! $providerClass) {
                if ($this->option('debug')) {
                    note("  ⚠️ {$packageName}: No service provider found");
                }

                continue;
            }

            // Check if it extends MooxServiceProvider without instantiating
            if ($this->isMooxProvider($providerClass)) {
                $this->mooxProviders[$packageName] = $providerClass;
            } else {
                if ($this->option('debug')) {
                    note("  • {$packageName}: {$providerClass} (not Moox provider)");
                }
            }
        }
        note("✅ Found ".count($this->mooxProviders)." Moox providers");
    }

    protected function getProviderClassFromPackage(string $packageName): ?string
    {
        // Try to find composer.json in vendor or local packages
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
                    return $providerClasses[0]; // Use first provider
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

    protected function checkMigrationsExist(string $packageName, array $migrations): bool
    {
        // Check if migrations directory exists in package
        $packageParts = explode('/', $packageName);
        $packageDir = $packageParts[1] ?? '';

        $possiblePaths = [
            base_path("packages/{$packageDir}/database/migrations"),
            base_path("vendor/{$packageName}/database/migrations"),
        ];

        foreach ($possiblePaths as $path) {
            if (File::isDirectory($path) && ! empty(File::files($path))) {
                return true;
            }
        }

        return false;
    }

    protected function checkTranslationsPublished(string $packageName, array $translations): bool
    {
        // Check if translations are published in lang directory
        $packageTag = str_replace('moox/', '', $packageName);

        // Check common translation paths
        $langPath = lang_path($packageTag);
        if (File::isDirectory($langPath) && ! empty(File::allFiles($langPath))) {
            return true;
        }

        return false;
    }

    protected function checkConfigsPublished(string $packageName, array $configFiles): bool
    {
        // Check if config files are published
        foreach ($configFiles as $configFile) {
            $configPath = config_path($configFile.'.php');
            if (File::exists($configPath)) {
                return true;
            }
        }

        return false;
    }

    protected function publishPackageAssets(string $packageName, string $type): bool
    {
        $packageTag = str_replace('moox/', '', $packageName);

        // Try common tag patterns
        $tags = [
            $packageTag.'-'.$type,
            $packageTag,
            $packageName.'-'.$type,
            $packageName,
        ];

        $published = false;
        foreach ($tags as $tag) {
            try {
                $result = Artisan::call('vendor:publish', [
                    '--tag' => $tag,
                    '--force' => false,
                    '--no-interaction' => true,
                ], $this->output);

                if ($result === 0) {
                    // Check if anything was actually published
                    $output = Artisan::output();
                    if (! str_contains($output, 'Nothing to publish')) {
                        $published = true;
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Continue to next tag
                continue;
            }
        }

        return $published;
    }

    protected function resolveSeederClass(string $packageName, string $seeder): ?string
    {
        // If seeder is already a full class name, return it
        if (class_exists($seeder)) {
            return $seeder;
        }

        // Try to resolve from package namespace
        $packageParts = explode('/', $packageName);
        $packageNamespace = 'Moox\\'.ucfirst($packageParts[1] ?? '');

        $possibleClasses = [
            $packageNamespace.'\\Database\\Seeders\\'.ucfirst($seeder),
            $packageNamespace.'\\Database\\Seeders\\'.$seeder,
            $packageNamespace.'\\Seeders\\'.ucfirst($seeder),
            $packageNamespace.'\\Seeders\\'.$seeder,
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    protected function selectOrCreatePanel(): ?string
    {
        $existingPanels = $this->getExistingPanels();

        if (empty($existingPanels)) {
            if (confirm(label: 'No panels found. Create a new panel?', default: true)) {
                return $this->createNewPanel();
            }

            return null;
        }

        // Build options - use panel class as key and display name as value
        $options = [];
        foreach ($existingPanels as $panel) {
            $displayName = basename(str_replace('\\', '/', $panel));
            $options[$panel] = $displayName;
        }
        $options['__new__'] = '✨ Create new panel';
        $options['__skip__'] = '⏩ Skip';

        $selected = select(
            label: 'Which panel should be used?',
            options: $options,
            default: array_key_first($options)
        );

        if ($selected === '__skip__') {
            return null;
        }

        if ($selected === '__new__') {
            return $this->createNewPanel();
        }

        return $this->getPanelPath($selected);
    }

    protected function getExistingPanels(): array
    {
        $panels = [];
        $bootstrapPath = base_path('bootstrap/providers.php');

        if (File::exists($bootstrapPath)) {
            $content = File::get($bootstrapPath);
            if (preg_match_all('/([\\\\A-Za-z0-9_]+)::class/', $content, $matches)) {
                foreach ($matches[1] as $class) {
                    if (str_contains($class, 'PanelProvider')) {
                        $panels[] = $class;
                    }
                }
            }
        }

        // Also check app/Providers/Filament
        $filamentPath = app_path('Providers/Filament');
        if (File::isDirectory($filamentPath)) {
            $files = File::files($filamentPath);
            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), 'PanelProvider.php')) {
                    $className = 'App\\Providers\\Filament\\'.basename($file->getFilename(), '.php');
                    if (! in_array($className, $panels)) {
                        $panels[] = $className;
                    }
                }
            }
        }

        return $panels;
    }

    protected function getPanelPath(string $panelClass): ?string
    {
        // Try to resolve class to file path
        $parts = explode('\\', $panelClass);
        $className = end($parts);

        // Check app/Providers/Filament first
        $appPath = app_path('Providers/Filament/'.$className.'.php');
        if (File::exists($appPath)) {
            return $appPath;
        }

        // Try to find via PSR-4 autoload
        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            $psr4 = $composer['autoload']['psr-4'] ?? [];

            foreach ($psr4 as $namespace => $dir) {
                if (str_starts_with($panelClass, rtrim($namespace, '\\'))) {
                    $relative = str_replace('\\', '/', substr($panelClass, strlen($namespace)));
                    $path = base_path(rtrim($dir, '/').'/'.$relative.'.php');
                    if (File::exists($path)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }

    protected function createNewPanel(): string
    {
        $panelName = text(
            label: 'Panel name (e.g. admin, cms):',
            default: 'admin',
            required: true
        );

        // Get list of files before creation
        $filesBefore = collect(File::files(app_path('Providers/Filament')))
            ->map(fn ($file) => $file->getFilename())
            ->toArray();

        try {
            Artisan::call('make:filament-panel', [
                'id' => $panelName,
            ]);

            // Find the newly created file
            $filesAfter = File::files(app_path('Providers/Filament'));
            foreach ($filesAfter as $file) {
                if (! in_array($file->getFilename(), $filesBefore) &&
                    str_ends_with($file->getFilename(), 'PanelProvider.php')) {
                    info("✅ Panel created: {$panelName}");

                    return $file->getPathname();
                }
            }

            throw new \RuntimeException('Panel file was not created');
        } catch (\Exception $e) {
            error("❌ Could not create panel: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Install already-resolved plugin classes into a panel file.
     */
    protected function installResolvedPlugins(array $pluginClasses, ?string $panelPath): void
    {
        if (empty($pluginClasses) || ! $panelPath || ! File::exists($panelPath)) {
            return;
        }

        // Register plugins in panel
        $content = File::get($panelPath);
        $changed = false;
        $pluginsToAdd = [];

        foreach ($pluginClasses as $pluginClass) {
            // Check if plugin is already registered (check for class name or ->plugin call)
            $escapedPluginClass = preg_quote($pluginClass, '/');
            if (str_contains($content, $pluginClass) ||
                preg_match('/->plugin\([^)]*'.$escapedPluginClass.'[^)]*\)/', $content)) {
                note("ℹ️ Plugin already registered: {$pluginClass}");

                continue;
            }

            // Collect plugins to add
            $pluginClassWithBackslash = str_starts_with($pluginClass, '\\') ? $pluginClass : '\\'.$pluginClass;
            $pluginsToAdd[] = $pluginClassWithBackslash;
        }

        if (empty($pluginsToAdd)) {
            note('ℹ️ All plugins are already registered');

            return;
        }

        // Ensure plugin class has leading backslash for absolute namespace
        $pluginsList = implode("::make(),\n                ", $pluginsToAdd).'::make()';

        if (preg_match('/->plugins\(\s*\[/', $content)) {
            // Add to existing plugins array - find the opening bracket and add after it
            $content = preg_replace(
                '/->plugins\(\s*\[/',
                "->plugins([\n                {$pluginsList},\n                ",
                $content,
                1
            );
            $changed = true;
            foreach ($pluginsToAdd as $pluginClass) {
                info("✅ Registered plugin: {$pluginClass}");
            }
        } else {
            // No ->plugins() section exists - we need to create it
            // Find the last semicolon before the closing brace of the panel() method
            // and insert ->plugins() before it

            $pluginsSection = "\n            ->plugins([\n                {$pluginsList},\n            ])";

            // Strategy: Find the last `);` or `];` that's followed by newnote, whitespace, and closing brace
            // The structure is: return $panel->...->method();\n    }
            // We want to replace `]);` with `])` + `->plugins([...])` + `;`

            // Find the position of the last `];` or `);` before the closing `}` of the method
            // Look for pattern: ]); or ); followed by newnote, spaces/tabs, and }
            $lastPos = -1;
            $patternToMatch = '';
            $isArrayPattern = false;

            // Try to find ]); pattern first (for array parameters like ->method([...]);)
            if (preg_match_all('/\]\s*;\s*\n\s*\}/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lastMatch = end($matches[0]);
                $lastPos = $lastMatch[1];
                $patternToMatch = $lastMatch[0];
                $isArrayPattern = true;
            }
            // If not found, try ); pattern (for simple parameters like ->method('value');)
            elseif (preg_match_all('/\)\s*;\s*\n\s*\}/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lastMatch = end($matches[0]);
                $lastPos = $lastMatch[1];
                $patternToMatch = $lastMatch[0];
                $isArrayPattern = false;
            }

            if ($lastPos !== -1) {
                // The pattern matched `];\n    }` or `);\n    }`
                // We need to replace `];` with `])` + `->plugins([...])` + `;` and keep `\n    }`
                $beforeLast = substr($content, 0, $lastPos);
                $afterLast = substr($content, $lastPos + strlen($patternToMatch));

                // Extract the closing part from pattern (the `\n    }` part after the semicolon)
                // Pattern is like `];\n    }` - extract everything after `];` or `);`
                if (preg_match('/[\]\)]\s*;(.*)/s', $patternToMatch, $closingMatch)) {
                    $closingPart = $closingMatch[1]; // This is `\n    }`
                } else {
                    // Fallback: assume standard format
                    $closingPart = "\n    }";
                }

                // Build the replacement: close the previous method's array/param, add plugins, then semicolon and closing brace
                $closingBracket = $isArrayPattern ? ']' : ')';
                $replacement = $closingBracket.$pluginsSection.';'.$closingPart;

                $content = $beforeLast.$replacement.$afterLast;
                $changed = true;
                foreach ($pluginsToAdd as $pluginClass) {
                    info("✅ Registered plugin: {$pluginClass}");
                }
            } else {
                warning('⚠️ Could not find plugin registration point in panel file');
                note('   Panel file structure may be different than expected');

                return;
            }
        }

        if ($changed) {
            File::put($panelPath, $content);
            info('✅ Plugins registered in panel');
        }
    }

    protected function resolvePluginClass(string $packageName, string $plugin): ?string
    {
        // If plugin looks like a full class name (contains backslash), return it as-is
        if (str_contains($plugin, '\\')) {
            return $plugin;
        }

        // Try to resolve from package namespace
        $packageParts = explode('/', $packageName);
        $packageShortName = ucfirst($packageParts[1] ?? '');

        // Common plugin namespace patterns
        $possibleNamespaces = [
            'Moox\\'.$packageShortName.'\\Moox\\Plugins\\',
            'Moox\\'.$packageShortName.'\\Filament\\Plugins\\',
            'Moox\\'.$packageShortName.'\\Plugins\\',
            'Moox\\'.$packageShortName.'\\',
        ];

        $pluginName = ucfirst($plugin);
        if (! str_ends_with($pluginName, 'Plugin')) {
            $pluginName .= 'Plugin';
        }

        // Return the most likely namespace pattern
        // Don't use class_exists() to avoid class loading conflicts
        return $possibleNamespaces[0].$pluginName;
    }
}
