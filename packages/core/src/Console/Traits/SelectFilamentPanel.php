<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\text;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\confirm;

trait SelectFilamentPanel
{
    protected array $panelMap = [
        'cms'    => ['path' => 'packages/content/src/panels', 'namespace' => 'Moox\\Content\\Panels'],
        'devops' => ['path' => 'packages/devops/src/panels',  'namespace' => 'Moox\\Devops\\Panels'],
        'shop'   => ['path' => 'packages/shop/src/panels',    'namespace' => 'Moox\\Shop\\Panels'],
        'press'  => ['path' => 'packages/press/src/panels',   'namespace' => 'Moox\\Press\\Panels'],
        'empty'  => ['path' => 'packages/core/src/panels',    'namespace' => 'Moox\\Core\\Panels'],
    ];

    public function selectPanels(): array
    {
        $availablePanels = collect($this->panelMap)
            ->filter(fn($config, $panel) => !$this->panelExists($panel))
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
            if (!isset($this->panelMap[$panel])) {
                error("❌ No path mapping found for panel '{$panel}'. Skipping.");
                continue;
            }

            if ($this->panelExists($panel)) {
                warning("⚠️ Panel '{$panel}' already exists. Skipping creation.");
                continue;
            }

            // 1. Frage vor Erstellung, ob Panel veröffentlicht werden soll
            $shouldPublish = confirm("📤 Do you want to publish the panel '{$panel}' into app/Providers/Filament?", default: false);

            // 2. Panel ID erfragen
            $panelId = text("🔧 Enter the panel ID for '{$panel}':", default: $panel);

            // 3. Panel erstellen
            $this->call('make:filament-panel', [
                'id' => $panelId,
            ]);

            // 4. Dateien verschieben und Namespace anpassen
            $from = base_path("app/Providers/Filament/" . ucfirst($panel) . "PanelProvider.php");
            $toDir = base_path($this->panelMap[$panel]['path']);
            $to = $toDir . '/' . ucfirst($panel) . "PanelProvider.php";

            if (!File::exists($from)) {
                warning("⚠️ Expected file {$from} not found. Skipping.");
                continue;
            }

            File::ensureDirectoryExists($toDir);
            File::move($from, $to);
            info("✅ Moved panel provider to: {$to}");

            // Namespace anpassen
            $content = File::get($to);
            $content = str_replace(
                'namespace App\\Providers\\Filament;',
                'namespace ' . $this->panelMap[$panel]['namespace'] . ';',
                $content
            );
            File::put($to, $content);
            info("🧭 Updated namespace to: " . $this->panelMap[$panel]['namespace']);

            // 5. Plugins registrieren (falls definiert)
            $this->registerDefaultPluginsForPanel($panel, $to);

            // 6. Auth User Model konfigurieren
            $this->configureAuthUserModelForPanel($panel, $to);

            // 7. Wenn publish gewünscht, Panel nochmal nach app/Providers/Filament kopieren mit angepasstem Namespace
            if ($shouldPublish) {
                $publishDir = base_path('app/Providers/Filament');
                File::ensureDirectoryExists($publishDir);
                $publishPath = $publishDir . '/' . ucfirst($panel) . 'PanelProvider.php';

                $publishContent = File::get($to);
                $publishContent = preg_replace(
                    '/namespace\s+[^;]+;/',
                    'namespace App\\Providers\\Filament;',
                    $publishContent
                );

                File::put($publishPath, $publishContent);
                info("📤 Panel has been published: {$publishPath}");
            }

            // 8. Provider in AppServiceProvider registrieren
            $providerClass = $shouldPublish
                ? 'App\\Providers\\Filament\\' . ucfirst($panel) . 'PanelProvider'
                : $this->panelMap[$panel]['namespace'] . '\\' . ucfirst($panel) . 'PanelProvider';

            $this->registerPanelProviderInAppServiceProvider($providerClass, $panel);
        }

        // 9. Am Ende Upgrade ausführen
        $this->runFilamentUpgrade();

        return $selectedPanels;
    }

    protected function runFilamentUpgrade(): void
    {
        info("⚙️ Running php artisan filament:upgrade ...");

        Artisan::call('filament:upgrade');

        $output = Artisan::output();

        info($output);
        info("✅ Filament upgrade command finished.");
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
            'shop'   => [],
            'empty'  => [],
            'admin'  => [],
        ];

        $plugins = $pluginMap[$panel] ?? [];

        if (empty($plugins)) {
            info("ℹ️ No default plugins defined for panel '{$panel}'.");
            return;
        }

        if (!File::exists($providerPath)) {
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

        // Insert plugins vor dem letzten Semikolon nach return $panel
        $content = preg_replace(
            '/return\s+\$panel(.*?)(;)/s',
            "return \$panel\$1{$insert}\$2",
            $content,
            1
        );

        File::put($providerPath, $content);

        info("✅ Plugins registered for panel '{$panel}'.");
    }

    protected function configureAuthUserModelForPanel(string $panel, string $providerPath): void
    {
        if (!File::exists($providerPath)) {
            error("❌ PanelProvider not found: {$providerPath}");
            return;
        }

        // Je nach Panel unterschiedliches User-Model setzen
        $userModel = $panel === 'press'
            ? 'Moox\\Press\\Models\\WpUser::class'
            : 'Moox\\User\\Models\\User::class';

        $content = File::get($providerPath);

        // Wenn auth() oder login() schon gesetzt, skip
        if (str_contains($content, '->login(') || str_contains($content, '->auth(')) {
            info("ℹ️ Auth already configured for panel '{$panel}'. Skipping.");
            return;
        }

        // use Filament import ergänzen, falls nicht vorhanden
        if (!str_contains($content, 'use Filament\Facades\Filament;')) {
            $content = preg_replace(
                '/(namespace\s+[^\s;]+;)/',
                "$1\n\nuse Filament\Facades\Filament;",
                $content
            );
        }

        // Auth-Block als String (Fluent-Chain Syntax)
        $authCode = <<<PHP
    ->login(
        fn () => Filament::auth(
            userModel: {$userModel},
        ),
    )
PHP;

        // Auth-Block nach ->path(...) einfügen
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
        if (!isset($this->panelMap[$panel])) {
            return false;
        }

        $providerPath = base_path($this->panelMap[$panel]['path'] . '/' . ucfirst($panel) . 'PanelProvider.php');
        return File::exists($providerPath);
    }

    protected function registerPanelProviderInAppServiceProvider(string $providerClass, string $panel): void
    {
        $appServiceProviderPath = app_path('Providers/AppServiceProvider.php');

        if (!File::exists($appServiceProviderPath)) {
            error("❌ AppServiceProvider.php not found at {$appServiceProviderPath}");
            return;
        }

        $content = File::get($appServiceProviderPath);

        // Bereits registriert?
        if (
            str_contains($content, $providerClass . '::class') ||
            str_contains($content, '\\' . $providerClass . '::class')
        ) {
            info("✅ Provider {$providerClass} is already registered in AppServiceProvider.");
            return;
        }

        // Suche boot()-Methode und füge dort $this->app->register() hinzu
        $pattern = '/public function boot\(\)\s*\{(.*?)\}/s';

        if (preg_match($pattern, $content, $matches)) {
            $bootBody = $matches[1];

            $registerLine = "        \$this->app->register({$providerClass}::class);";

            if (str_contains($bootBody, $registerLine)) {
                info("✅ Provider {$providerClass} already registered inside boot().");
                return;
            }

            $bootBodyNew = $bootBody . "\n" . $registerLine;

            $contentNew = preg_replace($pattern, "public function boot()\n    {\n{$bootBodyNew}\n    }", $content);

            File::put($appServiceProviderPath, $contentNew);

            info("✅ Registered {$providerClass} in AppServiceProvider::boot()");
        } else {
            warning("⚠️ Could not find boot() method in AppServiceProvider.php to register provider {$providerClass}.");
        }
    }
}
