<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Console\Traits\SelectFilamentPanel;

class MooxInstallCommand extends Command
{
    use Art, CheckForFilament, SelectFilamentPanel;

    protected $signature = 'moox:install {--debug : Show detailed information about all packages}';

    protected $description = 'Install Moox packages that extend MooxServiceProvider';

    protected array $mooxProviders = []; // packageName => providerClass

    protected array $packagePlugins = []; // packageName => ['plugins' => [], 'provider' => '']

    public function handle(): int
    {
        $this->art();
        $this->info('✨ Welcome to the Moox Installer!');
        $this->newLine();

        // Step 1: Check for Filament
        if (! $this->checkForFilament(silent: true)) {
            $this->error('❌ Filament installation is required.');

            return self::FAILURE;
        }

        // Step 2: Scan for MooxServiceProvider packages
        $this->info('🔍 Scanning for Moox packages...');
        $this->scanMooxProviders();

        if (empty($this->mooxProviders)) {
            $this->warn('⚠️ No packages extending MooxServiceProvider found.');

            return self::SUCCESS;
        }

        $this->info('✅ Found '.count($this->mooxProviders).' Moox package(s)');
        $this->newLine();

        // Step 3: Collect all assets from all packages
        $assets = $this->collectPackageAssets();

        if (empty($assets)) {
            $this->warn('⚠️ No assets found to install.');

            return self::SUCCESS;
        }

        // Step 4: Install assets in batches
        $this->installAssets($assets);

        // Step 5: Install plugins per package (panel-specific)
        $this->installAllPlugins();

        $this->newLine();
        $this->info('✅ Installation completed successfully!');

        return self::SUCCESS;
    }

    protected function collectPackageAssets(): array
    {
        $assets = [];

        foreach ($this->mooxProviders as $packageName => $providerClass) {
            try {
                $instance = new $providerClass(app());
                $mooxInfo = $instance->mooxInfo();

                // Standard asset types - dynamically process all
                $standardAssetTypes = [
                    'migrations' => 'migrations',
                    'configFiles' => 'configs',
                    'translations' => 'translations',
                    'seeders' => 'seeders',
                ];

                foreach ($standardAssetTypes as $mooxInfoKey => $assetType) {
                    if (! empty($mooxInfo[$mooxInfoKey])) {
                        $assets[] = [
                            'type' => $assetType,
                            'package' => $packageName,
                            'data' => $mooxInfo[$mooxInfoKey],
                            'provider' => $providerClass,
                        ];
                    }
                }

                // Store plugin info for later (needs panel selection)
                if (! empty($mooxInfo['plugins'])) {
                    $this->packagePlugins[$packageName] = [
                        'plugins' => $mooxInfo['plugins'],
                        'provider' => $providerClass,
                    ];
                }

                // Allow packages to add custom assets via hook
                if (method_exists($instance, 'getCustomInstallAssets')) {
                    $customAssets = $instance->getCustomInstallAssets();
                    if (is_array($customAssets)) {
                        foreach ($customAssets as $customAsset) {
                            $assets[] = array_merge([
                                'package' => $packageName,
                                'provider' => $providerClass,
                            ], $customAsset);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn("⚠️ Failed to collect assets for {$packageName}: {$e->getMessage()}");
            }
        }

        return $assets;
    }

    protected function installAssets(array $assets): void
    {
        if (empty($assets)) {
            return;
        }

        // Group assets by type
        $groupedAssets = [];
        foreach ($assets as $asset) {
            $type = $asset['type'] ?? 'unknown';
            if (! isset($groupedAssets[$type])) {
                $groupedAssets[$type] = [];
            }
            $groupedAssets[$type][] = $asset;
        }

        // Install each type
        foreach ($groupedAssets as $type => $typeAssets) {
            $this->installAssetType($type, $typeAssets);
        }
    }

    protected function installAssetType(string $type, array $assets): void
    {
        $installer = $this->getAssetInstaller($type);

        if (! $installer) {
            $this->warn("⚠️ No installer found for asset type: {$type}");

            return;
        }

        // Collect all data and group by package
        $allData = [];
        $packages = [];
        $packageDetails = [];

        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            $data = $asset['data'] ?? [];

            $allData = array_merge($allData, $data);
            $packages[] = $packageName;
            $packageDetails[$packageName] = $data;
        }

        if (empty($allData)) {
            return;
        }

        // Show summary
        $this->newLine();
        $this->info("📦 {$type} found:");
        $this->line('  Total: '.count($allData).' item(s) from '.count(array_unique($packages)).' package(s)');

        // List details per package
        foreach ($packageDetails as $packageName => $items) {
            if (! empty($items)) {
                $this->line("  • {$packageName}: ".count($items).' item(s)');
                if ($this->option('debug') || count($items) <= 5) {
                    foreach ($items as $item) {
                        $this->line("    - {$item}");
                    }
                } else {
                    $this->line('    ('.implode(', ', array_slice($items, 0, 3)).', ... and '.(count($items) - 3).' more)');
                }
            }
        }

        // Call the installer (it will handle confirmation internally)
        call_user_func($installer, $type, $allData, $assets);
    }

    protected function getAssetInstaller(string $type): ?callable
    {
        $installers = [
            'migrations' => [$this, 'installMigrations'],
            'configs' => [$this, 'installConfigs'],
            'translations' => [$this, 'installTranslations'],
            'seeders' => [$this, 'installSeeders'],
        ];

        // Allow custom installers to be registered
        if (method_exists($this, 'install'.ucfirst($type))) {
            return [$this, 'install'.ucfirst($type)];
        }

        return $installers[$type] ?? null;
    }

    protected function installMigrations(string $type, array $migrations, array $assets): void
    {
        $this->publishAndInstallAssets($type, $assets, function () {
            try {
                Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
                $this->info('✅ Migrations executed successfully');
            } catch (\Exception $e) {
                $this->warn("⚠️ Migration error: {$e->getMessage()}");
            }
        });
    }

    protected function installConfigs(string $type, array $configs, array $assets): void
    {
        $this->publishAndInstallAssets($type, $assets);
    }

    protected function installTranslations(string $type, array $translations, array $assets): void
    {
        $this->publishAndInstallAssets($type, $assets);
    }

    /**
     * Generic method to publish and optionally install assets.
     * Handles checking, publishing, skipping, and feedback for all asset types.
     */
    protected function publishAndInstallAssets(string $type, array $assets, ?callable $afterPublish = null): void
    {
        // Ask for confirmation - always ask, don't use default to force user interaction
        $defaultConfirm = match ($type) {
            'migrations' => true,
            'configs' => true,
            'translations' => true,
            'seeders' => false,
            default => true,
        };

        // Force interactive confirmation - always ask explicitly
        if ($this->option('no-interaction')) {
            // In non-interactive mode, use default
            $shouldInstall = $defaultConfirm;
        } else {
            // In interactive mode, always ask - ensure each confirmation is separate
            // Add newline and ensure we're in interactive mode
            $this->newLine();

            // Check if we're actually in interactive mode
            if (! $this->input->isInteractive()) {
                // Fallback if not interactive - but warn about it
                $this->warn("⚠️ Not in interactive mode for {$type}, using default: ".($defaultConfirm ? 'yes' : 'no'));
                $shouldInstall = $defaultConfirm;
            } else {
                // Ask the question - this MUST prompt the user
                // The problem: Laravel Prompts confirm() might be using cached input
                // Solution: Use Laravel's built-in confirm() method instead of Prompts
                // This ensures proper input handling
                $question = "Install {$type} for all packages?";

                // Use Laravel Command's confirm() method which handles input correctly
                $shouldInstall = $this->confirm($question, $defaultConfirm);
            }
        }

        if (! $shouldInstall) {
            $this->line("⏩ Skipped {$type}");

            return;
        }

        $published = false;
        $publishedPackages = [];
        $skippedPackages = [];
        $failedPackages = [];

        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            $itemNames = $asset['data'] ?? [];

            // Check if assets are already published/exist
            $alreadyExists = $this->checkAssetsExist($type, $packageName, $itemNames);

            if ($alreadyExists) {
                $skippedPackages[] = $packageName;
                $this->line("  ℹ️ {$packageName}: {$type} already exist, skipping");

                continue;
            }

            // Try to publish
            if ($this->publishPackageAssets($packageName, $type)) {
                $published = true;
                $publishedPackages[] = $packageName;
            } else {
                $failedPackages[] = $packageName;
            }
        }

        // Show summary
        if ($published) {
            $this->info('✅ '.ucfirst($type).' published for: '.implode(', ', $publishedPackages));
        }

        if (! empty($skippedPackages)) {
            $this->line('ℹ️ Skipped (already exist): '.implode(', ', $skippedPackages));
        }

        if (! empty($failedPackages) && ! $published) {
            $this->line('ℹ️ No '.$type.' were published (no publish tags found)');
        }

        // Run post-publish action if provided (e.g., migrate for migrations)
        // Only run if there are assets to process (published or already exist)
        if ($afterPublish && ($published || ! empty($skippedPackages) || ! empty($failedPackages))) {
            $afterPublish();
        }
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
        $this->newLine();
        if (! $this->confirm('Run seeders for all packages?', false)) {
            $this->line("⏩ Skipped {$type}");

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
                        $this->line("  ✅ {$seederClass}");
                        $executed++;
                    } else {
                        $this->warn("  ⚠️ Seeder class not found: {$seeder}");
                        $failed++;
                    }
                } catch (\Exception $e) {
                    // Check if error is because data already exists
                    if (str_contains($e->getMessage(), 'already') || str_contains($e->getMessage(), 'duplicate')) {
                        $this->line("  ℹ️ {$seeder}: Already seeded, skipping");
                        $skipped++;
                    } else {
                        $this->warn("  ⚠️ Seeder error for {$seeder}: {$e->getMessage()}");
                        $failed++;
                    }
                }
            }
        }

        if ($executed > 0) {
            $this->info("✅ Executed {$executed} seeder(s)");
        }
        if ($skipped > 0) {
            $this->line("ℹ️ Skipped {$skipped} seeder(s) (already executed)");
        }
        if ($failed > 0) {
            $this->warn("⚠️ {$failed} seeder(s) failed");
        }
    }

    protected function installAllPlugins(): void
    {
        if (empty($this->packagePlugins)) {
            return;
        }

        $this->newLine();
        $this->info('🧩 Installing plugins...');

        // Collect all available plugins from all packages
        $allPlugins = [];
        $packagePluginMap = [];

        foreach ($this->packagePlugins as $packageName => $pluginInfo) {
            foreach ($pluginInfo['plugins'] as $plugin) {
                $resolved = $this->resolvePluginClass($packageName, $plugin);
                if ($resolved) {
                    $allPlugins[$resolved] = $resolved;
                    $packagePluginMap[$resolved] = $packageName;
                }
            }
        }

        if (empty($allPlugins)) {
            $this->warn('⚠️ No valid plugin classes found');

            return;
        }

        $this->newLine();
        $this->info('📦 Found '.count($allPlugins).' plugin(s)');

        // Loop: Install plugins in panels until user says no
        do {
            $this->newLine();

            // Step 1: Select panel
            $panelPath = $this->selectOrCreatePanel();

            if (! $panelPath) {
                break;
            }

            $this->newLine();
            $this->info('✅ Selected Panel: '.basename($panelPath, '.php'));

            // Step 2: Ask if all plugins should be installed
            $pluginOptions = array_values($allPlugins);

            if (empty($pluginOptions)) {
                $this->info('✅ All plugins have been installed');
                break;
            }

            $this->newLine();
            $installAll = $this->confirm('Install all plugins?', true);

            $selectedPlugins = [];
            if ($installAll) {
                // Install all plugins
                $selectedPlugins = $pluginOptions;
            } else {
                // Let user select individual plugins
                $this->newLine();
                $this->line('Select plugins to install:');
                foreach ($pluginOptions as $plugin) {
                    $displayName = basename(str_replace('\\', '/', $plugin));
                    $packageName = $packagePluginMap[$plugin] ?? 'unknown';
                    if ($this->confirm("  Install '{$displayName}' ({$packageName})?", false)) {
                        $selectedPlugins[] = $plugin;
                    }
                }
            }

            // Install selected plugins
            if (! empty($selectedPlugins)) {
                $this->installResolvedPlugins($selectedPlugins, $panelPath);

                // Remove installed plugins from available list
                foreach ($selectedPlugins as $pluginClass) {
                    unset($allPlugins[$pluginClass]);
                }
            }

            // Ask if user wants to install more plugins in another panel
            if (empty($allPlugins)) {
                $this->newLine();
                $this->info('✅ All plugins have been installed');
                break;
            }

            $this->newLine();
            $installMore = $this->confirm('Install plugins in another panel?', false);
        } while ($installMore);
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
                    $this->line("  ⚠️ {$packageName}: No service provider found");
                }

                continue;
            }

            // Check if it extends MooxServiceProvider without instantiating
            if ($this->isMooxProvider($providerClass)) {
                $this->line("  ✅ {$packageName}: {$providerClass}");
                $this->mooxProviders[$packageName] = $providerClass;
            } else {
                if ($this->option('debug')) {
                    $this->line("  • {$packageName}: {$providerClass} (not Moox provider)");
                }
            }
        }
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
                ]);

                if ($result === 0) {
                    // Silent success - parent method will show summary
                    $published = true;
                    break;
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
            $this->newLine();
            if ($this->confirm('No panels found. Create a new panel?', true)) {
                return $this->createNewPanel();
            }

            return null;
        }

        // Build options
        $options = [];
        foreach ($existingPanels as $panel) {
            $displayName = basename(str_replace('\\', '/', $panel));
            $options[$panel] = $displayName;
        }
        $options['__new__'] = '✨ Create new panel';
        $options['__skip__'] = '⏩ Skip';

        $choiceOptions = array_values($options);
        $defaultIndex = 0;

        $selectedDisplay = $this->choice(
            'Which panel should be used?',
            $choiceOptions,
            $defaultIndex
        );

        $selected = array_search($selectedDisplay, $options, true);
        if ($selected === false) {
            $selected = $selectedDisplay;
        }

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
        $panelName = $this->ask('Panel name (e.g. admin, cms):', 'admin');

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
                    $this->info("✅ Panel created: {$panelName}");

                    return $file->getPathname();
                }
            }

            throw new \RuntimeException('Panel file was not created');
        } catch (\Exception $e) {
            $this->error("❌ Could not create panel: {$e->getMessage()}");
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
                $this->line("ℹ️ Plugin already registered: {$pluginClass}");

                continue;
            }

            // Collect plugins to add
            $pluginClassWithBackslash = str_starts_with($pluginClass, '\\') ? $pluginClass : '\\'.$pluginClass;
            $pluginsToAdd[] = $pluginClassWithBackslash;
        }

        if (empty($pluginsToAdd)) {
            $this->line('ℹ️ All plugins are already registered');

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
                $this->info("✅ Registered plugin: {$pluginClass}");
            }
        } else {
            // No ->plugins() section exists - we need to create it
            // Find the last semicolon before the closing brace of the panel() method
            // and insert ->plugins() before it

            $pluginsSection = "\n            ->plugins([\n                {$pluginsList},\n            ])";

            // Strategy: Find the last `);` or `];` that's followed by newline, whitespace, and closing brace
            // The structure is: return $panel->...->method();\n    }
            // We want to replace `]);` with `])` + `->plugins([...])` + `;`

            // Find the position of the last `];` or `);` before the closing `}` of the method
            // Look for pattern: ]); or ); followed by newline, spaces/tabs, and }
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
                    $this->info("✅ Registered plugin: {$pluginClass}");
                }
            } else {
                $this->warn('⚠️ Could not find plugin registration point in panel file');
                $this->line('   Panel file structure may be different than expected');

                return;
            }
        }

        if ($changed) {
            File::put($panelPath, $content);
            $this->info('✅ Plugins registered in panel');
        }
    }

    protected function resolvePluginClass(string $packageName, string $plugin): ?string
    {
        // If plugin is already a full class name, return it
        if (class_exists($plugin)) {
            return $plugin;
        }

        // Try to resolve from package namespace
        $packageParts = explode('/', $packageName);
        $packageShortName = ucfirst($packageParts[1] ?? '');

        // Common plugin namespace patterns
        $possibleNamespaces = [
            'Moox\\'.$packageShortName.'\\Filament\\Plugins\\',
            'Moox\\'.$packageShortName.'\\Moox\\Plugins\\',
            'Moox\\'.$packageShortName.'\\Plugins\\',
            'Moox\\'.$packageShortName.'\\',
        ];

        $pluginName = ucfirst($plugin);
        if (! str_ends_with($pluginName, 'Plugin')) {
            $pluginName .= 'Plugin';
        }

        foreach ($possibleNamespaces as $namespace) {
            $fullClass = $namespace.$pluginName;
            if (class_exists($fullClass)) {
                return $fullClass;
            }
        }

        return null;
    }
}
