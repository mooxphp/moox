<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

trait SelectFilamentPanel
{
    protected array $panelMap = [
        'cms' => ['path' => 'packages/content/src/panels', 'namespace' => 'Moox\\Content\\Panels'],
        'devops' => ['path' => 'packages/devops/src/panels',  'namespace' => 'Moox\\Devops\\Panels'],
        'shop' => ['path' => 'packages/shop/src/panels',    'namespace' => 'Moox\\Shop\\Panels'],
        'press' => ['path' => 'packages/press/src/panels',   'namespace' => 'Moox\\Press\\Panels'],
        'empty' => ['path' => 'packages/core/src/panels',    'namespace' => 'Moox\\Core\\Panels'],
    ];

    protected array $pluginPackageMap = [
        'Moox\\News\\' => 'moox/news',
        'Moox\\Media\\' => 'moox/media',
        'Moox\\Jobs\\' => 'moox/jobs',
        'Moox\\User\\' => 'moox/user',
        'Moox\\Page\\' => 'moox/page',
        'Moox\\Tag\\' => 'moox/tag',
        'Moox\\Category\\' => 'moox/category',
        'Moox\\Security\\' => 'moox/security',
        'Moox\\UserSession\\' => 'moox/user-session',
        'Moox\\UserDevice\\' => 'moox/user-device',
        'Moox\\Press\\' => 'moox/press',
    ];

    public function selectPanels(): array
    {
        $availablePanels = collect($this->panelMap)
            ->filter(fn ($config, $panel) => ! $this->panelExists($panel))
            ->keys()
            ->toArray();

        if (empty($availablePanels)) {
            info('✅ All panels are already installed. Nothing to do.');

            return [];
        }

        $selectedPanels = multiselect(
            label: '🛠️ Which panels do you want to install?',
            options: $availablePanels,
            required: false
        );

        if (empty($selectedPanels)) {
            warning('⚠️ No panels selected. Aborting.');

            return [];
        }

        foreach ($selectedPanels as $panel) {
            if (! isset($this->panelMap[$panel])) {
                error("❌ No path mapping found for panel '{$panel}'. Skipping.");

                continue;
            }

            if ($this->panelExists($panel)) {
                warning("⚠️ Panel '{$panel}' already exists. Skipping creation.");

                continue;
            }

            $shouldPublish = confirm("📤 Do you want to publish the panel '{$panel}' into app/Providers/Filament?", default: false);

            $panelId = text("🔧 Enter the panel ID for '{$panel}':", default: $panel);

            $this->call('make:filament-panel', [
                'id' => $panelId,
            ]);

            $from = base_path('app/Providers/Filament/'.ucfirst($panel).'PanelProvider.php');
            $toDir = base_path($this->panelMap[$panel]['path']);
            $to = $toDir.'/'.ucfirst($panel).'PanelProvider.php';

            if (! File::exists($from)) {
                warning("⚠️ Expected file {$from} not found. Skipping.");

                continue;
            }

            File::ensureDirectoryExists($toDir);
            File::move($from, $to);
            info("✅ Moved panel provider to: {$to}");

            $content = File::get($to);
            $content = str_replace(
                'namespace App\\Providers\\Filament;',
                'namespace '.$this->panelMap[$panel]['namespace'].';',
                $content
            );
            File::put($to, $content);
            info('🧭 Updated namespace to: '.$this->panelMap[$panel]['namespace']);

            $this->registerDefaultPluginsForPanel($panel, $to);

            $this->configureAuthUserModelForPanel($panel, $to);

            if ($shouldPublish) {
                $publishDir = base_path('app/Providers/Filament');
                File::ensureDirectoryExists($publishDir);
                $publishPath = $publishDir.'/'.ucfirst($panel).'PanelProvider.php';

                $publishContent = File::get($to);
                $publishContent = preg_replace(
                    '/namespace\s+[^;]+;/',
                    'namespace App\\Providers\\Filament;',
                    $publishContent
                );

                File::put($publishPath, $publishContent);
                info("📤 Panel has been published: {$publishPath}");
            }

            $providerClass = $shouldPublish
                ? 'App\\Providers\\Filament\\'.ucfirst($panel).'PanelProvider'
                : $this->panelMap[$panel]['namespace'].'\\'.ucfirst($panel).'PanelProvider';

            $this->registerPanelProviderInBootstrapProviders($providerClass, $panel);

            if (! $shouldPublish) {
                $this->cleanupPanelProviderInAppServiceProvider($panel);
            }
        }

        return $selectedPanels;
    }

    protected function runFilamentUpgrade(): void
    {
        info('⚙️ Running php artisan filament:upgrade ...');

        Artisan::call('filament:upgrade');
        $output = Artisan::output();

        info($output);
        info('✅ Filament upgrade command finished.');
    }

    protected function registerDefaultPluginsForPanel(string $panel, string $providerPath): void
    {
        $pluginMap = [
            'press' => [
                '\Moox\Press\WpCategoryPlugin::make()',
                '\Moox\Press\WpCommentMetaPlugin::make()',
                '\Moox\Press\WpCommentPlugin::make()',
                '\Moox\Press\WpMediaPlugin::make()',
                '\Moox\Press\WpOptionPlugin::make()',
                '\Moox\Press\WpPagePlugin::make()',
                '\Moox\Press\WpPostPlugin::make()',
                '\Moox\Press\WpPostMetaPlugin::make()',
                '\Moox\Press\WpTagPlugin::make()',
                '\Moox\Press\WpTermMetaPlugin::make()',
                '\Moox\Press\WpTermPlugin::make()',
                '\Moox\Press\WpTermRelationshipPlugin::make()',
                '\Moox\Press\WpTermTaxonomyPlugin::make()',
                '\Moox\Press\WpUserMetaPlugin::make()',
                '\Moox\Press\WpUserPlugin::make()',
            ],
            'cms' => [
                '\Moox\News\Moox\Plugins\NewsPlugin::make()',
                '\Moox\Media\MediaCollectionPlugin::make()',
                '\Moox\Media\MediaPlugin::make()',
                '\Moox\Jobs\JobsBatchesPlugin::make()',
                '\Moox\Jobs\JobsFailedPlugin::make()',
                '\Moox\Jobs\JobsPlugin::make()',
                '\Moox\Jobs\JobsWaitingPlugin::make()',
                '\Moox\User\UserPlugin::make()',
                '\Moox\Page\PagePlugin::make()',
                '\Moox\Tag\TagPlugin::make()',
                '\Moox\Category\Moox\Entities\Categories\Plugins\CategoryPlugin::make()',
                '\Moox\Security\ResetPasswordPlugin::make()',
                '\Moox\UserSession\UserSessionPlugin::make()',
                '\Moox\UserDevice\UserDevicePlugin::make()',
            ],
            'devops' => [],
            'shop' => [],
            'empty' => [],
            'admin' => [],
        ];

        $plugins = $pluginMap[$panel] ?? [];

        if (empty($plugins)) {
            info("ℹ️ No default plugins defined for panel '{$panel}'.");

            return;
        }

        if (! File::exists($providerPath)) {
            error("❌ Provider file not found: {$providerPath}");

            return;
        }

        $content = File::get($providerPath);

        if (str_contains($content, '->plugins([')) {
            warning("⚠️ Panel '{$panel}' already has plugins registered. Skipping.");

            return;
        }

        $pluginCode = implode(",\n        ", $plugins);

        $insert = <<<PHP
    ->plugins([
        {$pluginCode}
    ])
PHP;

        $content = preg_replace(
            '/return\s+\$panel(.*?)(;)/s',
            "return \$panel\$1{$insert}\$2",
            $content,
            1
        );

        File::put($providerPath, $content);

        info("✅ Plugins registered for panel '{$panel}'.");

        $requiredPackages = [];

        foreach ($plugins as $plugin) {
            if (preg_match('/\\\\?([\w\\\\]+)::make/', $plugin, $matches)) {
                $class = ltrim($matches[1], '\\');
                $package = $this->guessComposerPackageFromClass($class);
                if ($package && ! in_array($package, $requiredPackages)) {
                    $requiredPackages[] = $package;
                }
            }
        }

        foreach ($requiredPackages as $package) {
            if (! $this->isPackageInstalled($package)) {
                $this->requireComposerPackage($package);
            }
        }

        if (! empty($requiredPackages)) {
            $this->updatePanelPackageComposerJson($panel, $requiredPackages);
        }
    }

    protected function guessComposerPackageFromClass(string $class): ?string
    {
        foreach ($this->pluginPackageMap as $namespacePrefix => $packageName) {
            if (str_starts_with($class, $namespacePrefix)) {
                return $packageName;
            }
        }

        return null;
    }

    protected function isPackageInstalled(string $package): bool
    {
        $installed = shell_exec("composer show {$package} 2>&1");

        return ! str_contains($installed, 'not found');
    }

    protected function requireComposerPackage(string $package): void
    {
        info("📦 Requiring composer package: {$package} ...");
        exec("composer require {$package}", $output, $exitCode);
        if ($exitCode !== 0) {
            warning("⚠️ Failed to require {$package}. Please check manually.");
        } else {
            info("✅ Package {$package} required successfully.");
        }
    }

    protected function updatePanelPackageComposerJson(string $panel, array $requiredPackages): void
    {
        $panelPath = $this->panelMap[$panel]['path'] ?? null;
        if (! $panelPath) {
            warning("⚠️ No path found for panel '{$panel}'. Cannot update composer.json.");

            return;
        }

        $composerJsonPath = base_path($panelPath.'/../../composer.json');

        if (! File::exists($composerJsonPath)) {
            info("ℹ️ No composer.json found for panel package at: {$composerJsonPath}");

            return;
        }

        $composerJson = json_decode(File::get($composerJsonPath), true);
        if (! $composerJson) {
            warning("⚠️ Invalid composer.json at: {$composerJsonPath}");

            return;
        }

        $updated = false;
        if (! isset($composerJson['require'])) {
            $composerJson['require'] = [];
        }

        foreach ($requiredPackages as $package) {
            if (! isset($composerJson['require'][$package])) {
                $composerJson['require'][$package] = '*';
                $updated = true;
                info("📝 Added {$package} to panel package composer.json");
            }
        }

        if ($updated) {
            File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            info("✅ Updated composer.json for panel package: {$composerJsonPath}");
        } else {
            info('ℹ️ No new dependencies to add to panel package composer.json');
        }
    }

    public function updatePanelDependencies(string $panel): void
    {
        if (! isset($this->panelMap[$panel])) {
            error("❌ Panel '{$panel}' not found in panel map.");

            return;
        }

        $providerPath = base_path($this->panelMap[$panel]['path'].'/'.ucfirst($panel).'PanelProvider.php');

        if (! File::exists($providerPath)) {
            error("❌ Panel provider not found: {$providerPath}");

            return;
        }

        info("🔍 Analyzing plugins for panel '{$panel}'...");

        $content = File::get($providerPath);
        $plugins = $this->extractPluginsFromProvider($content);

        if (empty($plugins)) {
            info("ℹ️ No plugins found in panel '{$panel}'.");

            return;
        }

        $requiredPackages = [];
        foreach ($plugins as $plugin) {
            $package = $this->guessComposerPackageFromClass($plugin);
            if ($package && ! in_array($package, $requiredPackages)) {
                $requiredPackages[] = $package;
            }
        }

        if (! empty($requiredPackages)) {
            $this->updatePanelPackageComposerJson($panel, $requiredPackages);
        }
    }

    protected function extractPluginsFromProvider(string $content): array
    {
        $plugins = [];

        if (preg_match('/->plugins\(\[(.*?)\]\)/s', $content, $matches)) {
            $pluginLines = explode(',', $matches[1]);
            foreach ($pluginLines as $line) {
                $line = trim($line);
                if (preg_match('/\\\\?([\w\\\\]+)::make\(\)/', $line, $matches)) {
                    $plugins[] = ltrim($matches[1], '\\');
                }
            }
        }

        return $plugins;
    }

    protected function configureAuthUserModelForPanel(string $panel, string $providerPath): void
    {
        if (! File::exists($providerPath)) {
            error("❌ PanelProvider not found: {$providerPath}");

            return;
        }

        $userModel = $panel === 'press'
            ? '\\Moox\\Press\\Models\\WpUser'
            : '\\Moox\\User\\Models\\User';

        $content = File::get($providerPath);

        if (str_contains($content, 'Filament::auth(')) {
            info("ℹ️ Auth already configured for panel '{$panel}'. Skipping.");

            return;
        }

        if (! str_contains($content, 'use Filament\Facades\Filament;')) {
            $content = preg_replace(
                '/(namespace\s+[^\s;]+;)/',
                "$1\n\nuse Filament\Facades\\Filament;",
                $content
            );
        }

        $authCode = <<<PHP
    ->login(
        fn () => Filament::auth({$userModel}::class),
    )
    PHP;

        $content = preg_replace(
            '/(->path\(.*?\))/',
            "\$1\n    {$authCode}",
            $content,
            1
        );

        File::put($providerPath, $content);

        info("✅ Auth configuration for panel '{$panel}' set to: {$userModel}");
    }

    protected function panelExists(string $panel): bool
    {
        if (! isset($this->panelMap[$panel])) {
            return false;
        }

        $providerPath = base_path($this->panelMap[$panel]['path'].'/'.ucfirst($panel).'PanelProvider.php');

        return File::exists($providerPath);
    }

    protected function registerPanelProviderInAppServiceProvider(string $providerClass, string $panel): void
    {
        $appServiceProviderPath = app_path('Providers/AppServiceProvider.php');

        if (! File::exists($appServiceProviderPath)) {
            error("❌ AppServiceProvider.php not found at {$appServiceProviderPath}");

            return;
        }

        $content = File::get($appServiceProviderPath);

        if (str_contains($content, $providerClass.'::class')) {
            info("✅ Provider {$providerClass} is already registered in AppServiceProvider.");

            return;
        }

        $pattern = '/public function register\s*\([^)]*\)\s*(?::\s*\w+)?\s*\{(.*?)\}/s';

        if (preg_match($pattern, $content, $matches)) {
            $registerBody = $matches[1];

            $registerLine = "        \$this->app->register({$providerClass}::class);";

            if (str_contains($registerBody, $registerLine)) {
                info("✅ Provider {$providerClass} already registered inside register().");

                return;
            }

            $registerBodyNew = rtrim($registerBody)."\n".$registerLine;

            $contentNew = preg_replace($pattern, "public function register(): void\n    {\n{$registerBodyNew}\n    }", $content);

            File::put($appServiceProviderPath, $contentNew);

            info("✅ Registered {$providerClass} in AppServiceProvider::register()");
        } else {
            warning("⚠️ Could not find register() method in AppServiceProvider.php to register provider {$providerClass}.");
        }
    }

    protected function registerPanelProviderInBootstrapProviders(string $providerClass, string $panel): void
    {
        $bootstrapProvidersPath = base_path('bootstrap/providers.php');

        if (! File::exists($bootstrapProvidersPath)) {
            return;
        }

        $content = File::get($bootstrapProvidersPath);

        $appClass = 'App\\Providers\\Filament\\'.ucfirst($panel).'PanelProvider';
        $packageClass = $this->panelMap[$panel]['namespace'].'\\'.ucfirst($panel).'PanelProvider';

        $desiredClass = $providerClass;
        $otherClass = ($providerClass === $appClass) ? $packageClass : $appClass;

        $patternRemove = '/^\s*'.preg_quote($otherClass, '/').'::class\s*,?\s*$/m';

        $contentWithoutOther = preg_replace($patternRemove, '', $content);

        if (str_contains($contentWithoutOther, $desiredClass.'::class')) {
            if ($contentWithoutOther !== $content) {
                File::put($bootstrapProvidersPath, $contentWithoutOther);
                info('🧹 Removed other provider variant from bootstrap/providers.php');
            } else {
                info("✅ Provider {$providerClass} already present in bootstrap/providers.php");
            }

            return;
        }

        $updated = preg_replace_callback(
            '/return\s*\[([\s\S]*?)\];/m',
            function (array $matches) use ($desiredClass) {
                $inner = rtrim($matches[1]);
                if ($inner !== '' && ! str_ends_with(trim($inner), ',')) {
                    $inner .= ',';
                }
                $inner .= "\n    {$desiredClass}::class,";

                return "return [\n{$inner}\n];";
            },
            $contentWithoutOther ?? $content,
            1
        );

        if ($updated && $updated !== $content) {
            File::put($bootstrapProvidersPath, $updated);
            info("✅ Registered {$providerClass} in bootstrap/providers.php");
        } else {
            // Fallback: simple append before closing array, for non-matching formats
            $pos = strrpos($content, '];');
            if ($pos !== false) {
                $before = substr($content, 0, $pos);
                $after = substr($content, $pos);
                $line = "    {$desiredClass}::class,\n";
                // Ensure trailing comma before appending if needed
                if (! preg_match('/,\s*$/', trim($before))) {
                    $before = rtrim($before).",\n";
                }
                $newContent = $before.$line.$after;
                File::put($bootstrapProvidersPath, $newContent);
                info("✅ Registered {$providerClass} in bootstrap/providers.php (fallback)");
            } else {
                warning("⚠️ Could not update bootstrap/providers.php to register {$providerClass}");
            }
        }
    }

    protected function cleanupPanelProviderInAppServiceProvider(string $panel): void
    {
        $appServiceProviderPath = app_path('Providers/AppServiceProvider.php');
        if (! File::exists($appServiceProviderPath)) {
            return;
        }

        $content = File::get($appServiceProviderPath);
        $pattern = '/\$this->\\app->\\register\((App\\\\Providers\\\\Filament\\\\'.ucfirst($panel).'PanelProvider::class)\);/';
        $updated = preg_replace($pattern, '', $content);

        if ($updated !== null && $updated !== $content) {
            File::put($appServiceProviderPath, $updated);
            info("🧹 Removed published App provider registration from AppServiceProvider for panel '{$panel}'");
        }
    }

    protected function getPanelFromBootstrapProviders(): ?string
    {
        $bootstrapProvidersPath = base_path('bootstrap/providers.php');
        if (! File::exists($bootstrapProvidersPath)) {
            return null;
        }

        $content = File::get($bootstrapProvidersPath);

        if (! preg_match_all('/([\\\\A-Za-z0-9_]+)::class/', $content, $matches)) {
            return null;
        }

        foreach ($matches[1] as $class) {
            $panel = $this->mapProviderClassToPanelKey($class);
            if ($panel !== null) {
                return $panel;
            }
        }

        return null;
    }

    protected function mapProviderClassToPanelKey(string $providerClass): ?string
    {
        foreach ($this->panelMap as $key => $cfg) {
            $expected = $cfg['namespace'].'\\'.ucfirst($key).'PanelProvider';
            if ($providerClass === $expected) {
                return $key;
            }
        }

        if (preg_match('/^App\\\\Providers\\\\Filament\\\\([A-Za-z]+)PanelProvider$/', $providerClass, $m)) {
            $panel = strtolower($m[1]);

            return isset($this->panelMap[$panel]) ? $panel : null;
        }

        return null;
    }

    protected function ensurePanelForKey(string $panel): void
    {
        if (! isset($this->panelMap[$panel])) {
            error("❌ Unknown panel '{$panel}'.");

            return;
        }

        if ($this->panelExists($panel)) {
            return;
        }

        $panelId = $panel;
        $this->call('make:filament-panel', [
            'id' => $panelId,
        ]);

        $from = base_path('app/Providers/Filament/'.ucfirst($panel).'PanelProvider.php');
        $toDir = base_path($this->panelMap[$panel]['path']);
        $to = $toDir.'/'.ucfirst($panel).'PanelProvider.php';

        if (! File::exists($from)) {
            warning("⚠️ Expected file {$from} not found. Skipping panel move.");

            return;
        }

        File::ensureDirectoryExists($toDir);
        File::move($from, $to);
        info("✅ Moved panel provider to: {$to}");

        $content = File::get($to);
        $content = str_replace(
            'namespace App\\Providers\\Filament;',
            'namespace '.$this->panelMap[$panel]['namespace'].';',
            $content
        );
        File::put($to, $content);
        info('🧭 Updated namespace to: '.$this->panelMap[$panel]['namespace']);

        $this->registerDefaultPluginsForPanel($panel, $to);
        $this->configureAuthUserModelForPanel($panel, $to);

        $providerClass = $this->panelMap[$panel]['namespace'].'\\'.ucfirst($panel).'PanelProvider';
        $this->registerPanelProviderInBootstrapProviders($providerClass, $panel);
        $this->cleanupPanelProviderInAppServiceProvider($panel);
    }
}
