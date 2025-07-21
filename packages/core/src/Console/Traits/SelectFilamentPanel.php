<?php

namespace Moox\Core\Console\Traits;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\text;

trait SelectFilamentPanel
{
    protected array $panelBundles = [
        'None' => [],
        'Moox Complete' => ['admin', 'shop', 'press', 'devops', 'cms', 'empty'],
    ];

    public function selectPanelBundle(): array
    {
        $bundleName = select(
            'Which panel bundle do you want to install?',
            array_keys($this->panelBundles),
        );

        $selectedPanels = $this->panelBundles[$bundleName];

        $this->info("You selected the '{$bundleName}' bundle.");
        $this->info('Included panels: ' . implode(', ', $selectedPanels));

        foreach ($selectedPanels as $panel) {
            if ($this->panelExists($panel)) {
                warning("Panel '{$panel}' already exists. Skipping generation.");
                continue;
            }

            $panelId = text("What is the panel ID for '{$panel}'?", default: $panel);

            $this->call('make:filament-panel', [
                'id' => $panelId,
            ]);

            info("Filament panel '{$panel}' generated.");

            $this->registerDefaultPluginsForPanel($panel);
        }

        return $selectedPanels;
    }

    protected function panelExists(string $panel): bool
    {
        $providerClass = 'App\\Providers\\Filament\\' . ucfirst($panel) . 'PanelProvider';

        $providersFile = base_path('bootstrap/providers.php');

        if (!file_exists($providersFile)) {
            return false;
        }

        $registeredProviders = include $providersFile;

        return in_array($providerClass, $registeredProviders, true);
    }

    protected function registerDefaultPluginsForPanel(string $panel): void
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
            'devops' => [
                
            ],
            'shop' => [
                
            ],
            'cms' => [
               
            ],
            'empty' => [
            
            ],
            'admin' => [
            
            ],
        ];

        $plugins = $pluginMap[$panel] ?? [];

        if (empty($plugins)) {
            info("No default plugins defined for panel '{$panel}'.");
            return;
        }

        $providerPath = base_path("app/Providers/Filament/" . ucfirst($panel) . "PanelProvider.php");

        if (!file_exists($providerPath)) {
            warning("Provider file for panel '{$panel}' not found at {$providerPath}.");
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
}
