<?php

namespace Moox\Core\Console\Traits;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait RegisterFilamentPlugin
{
    public function registerFilamentPlugin(array $package): void
    {
        $providerPath = app_path('Providers/Filament');

        $panelsToRegister = $this->selectFilamentPanel();

        if (empty($panelsToRegister)) {
            alert('⚠️ No panel providers found. Please register plugins manually.');
            return;
        }

        foreach ((array) $panelsToRegister as $panelProvider) {
            $fullPath = $providerPath . '/' . $panelProvider;

            if (!file_exists($fullPath)) {
                warning("⚠️ Panel provider not found: {$fullPath}. Skipping.");
                continue;
            }

            $this->registerPlugins($fullPath, $package);
            info("✅ Plugins registered for panel provider: {$panelProvider}");
        }
    }

    protected function registerPlugins(string $providerPath, array $package): void
    {
        $content = file_get_contents($providerPath);
        $plugins = $this->packageService->getPlugins($package);

        foreach ($plugins as $plugin) {
            if (!str_contains($content, $plugin)) {
                $content = str_replace(
                    'return $panel;',
                    "        \$panel->plugin(new {$plugin}());\n        return \$panel;",
                    $content
                );
            }
        }

        file_put_contents($providerPath, $content);
    }
}
