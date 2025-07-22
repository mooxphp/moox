<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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

    protected array $panelMap = [
        'cms'   => ['path' => 'packages/content/src/panels', 'namespace' => 'Moox\\Content\\Panels'],
        'devops'=> ['path' => 'packages/devops/src/panels',  'namespace' => 'Moox\\Devops\\Panels'],
        'shop'  => ['path' => 'packages/shop/src/panels',    'namespace' => 'Moox\\Shop\\Panels'],
        'press' => ['path' => 'packages/press/src/panels',   'namespace' => 'Moox\\Press\\Panels'],
        'empty' => ['path' => 'packages/core/src/panels',    'namespace' => 'Moox\\Core\\Panels'],
        'admin' => ['path' => 'packages/admin/src/panels',   'namespace' => 'Moox\\Admin\\Panels'],
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
            if (!isset($this->panelMap[$panel])) {
                warning("No path mapping found for panel '{$panel}'. Skipping.");
                continue;
            }

            $panelId = text("What is the panel ID for '{$panel}'?", default: $panel);

            // Step 1: Panel im Default-Pfad generieren
            $this->call('make:filament-panel', [
                'id' => $panelId,
            ]);

            // Step 2: Pfade berechnen
            $from = base_path("app/Providers/Filament/" . ucfirst($panel) . "PanelProvider.php");
            $toDir = base_path($this->panelMap[$panel]['path']);
            $to = $toDir . '/' . ucfirst($panel) . "PanelProvider.php";

            // Step 3: Datei verschieben
            if (File::exists($from)) {
                File::ensureDirectoryExists($toDir);
                File::move($from, $to);
                info("Moved panel provider to: {$to}");

                // Namespace ersetzen
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
            'shop' => [],
            'cms' => [],
            'empty' => [],
            'admin' => [],
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
        return false; // oder bessere Prüfung einbauen
    }
}
