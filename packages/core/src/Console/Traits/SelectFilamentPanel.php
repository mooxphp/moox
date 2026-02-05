<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

/** @phpstan-ignore-next-line trait.unused */
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

    protected bool $autoRequireComposer = true;

    public function setAutoRequireComposer(bool $v): void
    {
        $this->autoRequireComposer = $v;
    }

    public function selectPanels(): array
    {
        $existingPanel = $this->getPanelWithLoginFromBootstrap();
        if ($existingPanel) {
            warning("⚠️ A panel with login already exists: {$existingPanel}");
            info('➡️ You can still create additional panels.');
        }

        // Build list of panels that are NOT yet registered in bootstrap/providers.php
        $registeredProviderClasses = method_exists($this, 'getProviderClassesFromBootstrap')
            ? $this->getProviderClassesFromBootstrap()
            : [];

        $registeredPanelKeys = [];
        foreach ($registeredProviderClasses as $class) {
            $key = $this->mapProviderClassToPanelKey($class);
            if ($key !== null) {
                $registeredPanelKeys[$key] = true;
            }
        }

        $availablePanels = collect(array_keys($this->panelMap))
            ->reject(fn ($panel) => isset($registeredPanelKeys[$panel]))
            ->values()
            ->toArray();

        if (empty($availablePanels)) {
            info('✅ All panels are already installed. Nothing to do.');

            return [];
        }

        $selectedPanels = multiselect(
            label: '🛠️ Which panels do you want to enable?',
            options: $availablePanels,
            required: false
        );

        if (empty($selectedPanels)) {
            warning('⚠️ No panels selected. Aborting.');

            return [];
        }

        foreach ($selectedPanels as $panel) {
            $customize = confirm("⚙️ Do you want to change something in {$panel}? For example the path or the user model?", default: false);
            $this->ensurePanelForKey($panel, $panel, $customize);
        }

        return $selectedPanels;
    }

    protected function getPanelWithLoginFromBootstrap(): ?string
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
            if ($panel && $this->panelHasLogin($panel)) {
                return $class;
            }
        }

        return null;
    }

    protected function panelHasLogin(string $panel): bool
    {
        $providerPath = base_path($this->panelMap[$panel]['path'].'/'.ucfirst($panel).'PanelProvider.php');
        if (! File::exists($providerPath)) {
            return false;
        }

        $content = File::get($providerPath);

        return str_contains($content, '->login(');
    }

    protected function ensurePanelForKey(string $panel, string $panelId, bool $publish = false): void
    {
        if (! isset($this->panelMap[$panel])) {
            error("❌ Unknown panel '{$panel}'.");

            return;
        }

        $packageProviderPath = base_path($this->panelMap[$panel]['path'].'/'.ucfirst($panel).'PanelProvider.php');

        if (! File::exists($packageProviderPath)) {
            error("❌ Panel provider not found in package: {$packageProviderPath}");

            return;
        }

        if ($publish) {
            $toDir = app_path('Providers/Filament');
            File::ensureDirectoryExists($toDir);
            $publishedPath = $toDir.'/'.ucfirst($panel).'PanelProvider.php';
            File::copy($packageProviderPath, $publishedPath);

            $content = File::get($publishedPath);
            $content = preg_replace('/^namespace\s+[^;]+;/m', 'namespace App\\Providers\\Filament;', $content, 1);

            File::put($publishedPath, $content);
            info("✅ Published and customized provider: {$publishedPath}");

            $providerClass = 'App\\\\Providers\\\\Filament\\\\'.ucfirst($panel).'PanelProvider';
            $this->registerPanelProviderInBootstrapProviders($providerClass, $panel);
            $this->cleanupPanelProviderInAppServiceProvider($panel);
        } else {
            $providerClass = $this->panelMap[$panel]['namespace'].'\\'.ucfirst($panel).'PanelProvider';
            $this->registerPanelProviderInBootstrapProviders($providerClass, $panel);
        }
    }

    protected function panelExists(string $panel): bool
    {
        if (! isset($this->panelMap[$panel])) {
            return false;
        }

        $providerPath = base_path($this->panelMap[$panel]['path'].'/'.ucfirst($panel).'PanelProvider.php');

        return File::exists($providerPath);
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

    protected function extractPanelPath(string $content): ?string
    {
        if (preg_match('/->path\(\s*[\'\"]([^\'\"]+)[\'\"]/m', $content, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function setPanelPath(string $content, string $newPath): string
    {
        if (preg_match('/->path\(.*?\)/m', $content)) {
            return preg_replace('/->path\(.*?\)/m', "->path('".addslashes($newPath)."')", $content, 1);
        }

        return preg_replace('/(->id\(.*?\))/m', "$1\n            ->path('".addslashes($newPath)."')", $content, 1);
    }

    protected function setAuthUserModel(string $content, string $userModelFqn): string
    {
        if (! str_contains($content, 'use Filament\\Facades\\Filament;')) {
            $content = preg_replace('/(namespace\s+[^;]+;)/', "$1\n\nuse Filament\\Facades\\Filament;", $content, 1);
        }

        $authCode = <<<PHP
    ->login(
        fn () => Filament::auth({$userModelFqn}::class),
    )
PHP;

        if (str_contains($content, 'Filament::auth(')) {
            $content = preg_replace('/Filament::auth\(([^\)]+)\)/', 'Filament::auth('.$userModelFqn.'::class)', $content, 1);

            return $content;
        }

        return preg_replace('/(->path\(.*?\))/', "$1\n    {$authCode}", $content, 1);
    }

    protected function registerPanelProviderInBootstrapProviders(string $providerClass, string $panel): void
    {
        $bootstrapProvidersPath = base_path('bootstrap/providers.php');

        if (! File::exists($bootstrapProvidersPath)) {
            warning("⚠️ bootstrap/providers.php not found. Cannot register {$providerClass}.");

            return;
        }

        $content = File::get($bootstrapProvidersPath);

        $mooxProvider = $this->panelMap[$panel]['namespace'].'\\'.ucfirst($panel).'PanelProvider';
        $appProvider = 'App\\Providers\\Filament\\'.ucfirst($panel).'PanelProvider';

        $content = preg_replace(
            '/^\s*('.preg_quote($mooxProvider, '/').'|'.preg_quote($appProvider, '/').')::class,?\s*$/m',
            '',
            $content
        );

        if (preg_match('/return\s*\[(.*?)\];/s', $content, $matches)) {
            $inner = trim($matches[1]);

            if ($inner !== '' && ! str_ends_with(trim($inner), ',')) {
                $inner .= ',';
            }

            $inner .= "\n    {$providerClass}::class,";

            $newContent = preg_replace('/return\s*\[.*?\];/s', "return [\n{$inner}\n];", $content, 1);
            File::put($bootstrapProvidersPath, $newContent);

            info("✅ Registered {$providerClass} in bootstrap/providers.php.");
        } else {
            warning("⚠️ Could not find return array in bootstrap/providers.php. Please add {$providerClass} manually.");
        }
    }

    protected function cleanupPanelProviderInAppServiceProvider(string $panel): void
    {
        $appServiceProviderPath = app_path('Providers/AppServiceProvider.php');
        if (! File::exists($appServiceProviderPath)) {
            return;
        }

        $content = File::get($appServiceProviderPath);
        $pattern = '/\$this->app->register\(App\\\\Providers\\\\Filament\\\\'.ucfirst($panel).'PanelProvider::class\);/';
        $updated = preg_replace($pattern, '', $content);

        if ($updated !== null && $updated !== $content) {
            File::put($appServiceProviderPath, $updated);
            info("🧹 Removed published App provider registration from AppServiceProvider for panel '{$panel}'");
        }
    }
}
