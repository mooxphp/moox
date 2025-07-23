<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\text;
use function Laravel\Prompts\multiselect;

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
            info('All panels are already installed. Nothing to install.');
            return [];
        }

        $selectedPanels = multiselect(
            label: 'Which panels do you want to install?',
            options: $availablePanels,
            required: false
        );

        if (empty($selectedPanels)) {
            info('No panels selected. Nothing to install.');
            return [];
        }

        foreach ($selectedPanels as $panel) {
            if (!isset($this->panelMap[$panel])) {
                warning("No path mapping found for panel '{$panel}'. Skipping.");
                continue;
            }

            if ($this->panelExists($panel)) {
                info("Panel '{$panel}' already exists. Skipping creation.");
                continue;
            }

            $panelId = text("What is the panel ID for '{$panel}'?", default: $panel);

            // Panel generieren
            $this->call('make:filament-panel', [
                'id' => $panelId,
            ]);

            // PanelProvider verschieben
            $from = base_path("app/Providers/Filament/" . ucfirst($panel) . "PanelProvider.php");
            $toDir = base_path($this->panelMap[$panel]['path']);
            $to = $toDir . '/' . ucfirst($panel) . "PanelProvider.php";

            if (File::exists($from)) {
                File::ensureDirectoryExists($toDir);
                File::move($from, $to);
                info("Moved panel provider to: {$to}");

                // Namespace aktualisieren
                $content = File::get($to);
                $content = preg_replace(
                    '/namespace App\\\Providers\\\Filament;/',
                    'namespace ' . $this->panelMap[$panel]['namespace'] . ';',
                    $content
                );
                File::put($to, $content);
                info("Updated namespace to " . $this->panelMap[$panel]['namespace']);
            } else {
                warning("Expected panel file {$from} not found.");
            }

            // AppServiceProvider-Eintrag
            $providerClass = $this->panelMap[$panel]['namespace'] . '\\' . ucfirst($panel) . 'PanelProvider';
            $this->registerPanelProviderInAppServiceProvider($providerClass);

            // Plugins registrieren
            $this->registerDefaultPluginsForPanel($panel, $to);
        }

        return $selectedPanels;
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
            'devops' => [],
            'shop'   => [],
            'cms'    => [
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
            'empty'  => [],
            'admin'  => [],
        ];

        $plugins = $pluginMap[$panel] ?? [];

        if (empty($plugins)) {
            info("No default plugins defined for panel '{$panel}'.");
            return;
        }

        if (!file_exists($providerPath)) {
            warning("Provider file not found: {$providerPath}");
            return;
        }

        $content = file_get_contents($providerPath);

        if (str_contains($content, '->plugins([')) {
            warning("Panel '{$panel}' already has plugins registered. Skipping.");
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

        file_put_contents($providerPath, $content);

        info("Plugins for panel '{$panel}' registered.");
    }

    protected function panelExists(string $panel): bool
    {
        if (!isset($this->panelMap[$panel])) {
            return false;
        }

        $providerPath = base_path($this->panelMap[$panel]['path'] . '/' . ucfirst($panel) . 'PanelProvider.php');
        return File::exists($providerPath);
    }

    protected function registerPanelProviderInAppServiceProvider(string $providerClass): void
    {
        $appServiceProviderPath = app_path('Providers/AppServiceProvider.php');

        if (!file_exists($appServiceProviderPath)) {
            $this->warn("AppServiceProvider.php not found at {$appServiceProviderPath}");
            return;
        }

        $content = file_get_contents($appServiceProviderPath);

        if (
            str_contains($content, $providerClass . '::class') ||
            str_contains($content, '\\' . $providerClass . '::class')
        ) {
            info("Provider {$providerClass} is already registered in AppServiceProvider.");
            return;
        }

        // register()-Methode finden und registrieren
        $pattern = '/public function register\(\): void\s*\{\s*/';

        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1] + strlen($matches[0][0]);
            $insertLine = "\n        \$this->app->register(\\{$providerClass}::class);\n";
            $newContent = substr($content, 0, $pos) . $insertLine . substr($content, $pos);
            file_put_contents($appServiceProviderPath, $newContent);

            info("Provider {$providerClass} added to AppServiceProvider.");
        } else {
            $this->warn('Could not find register() method in AppServiceProvider.php to insert provider.');
        }
    }
}
