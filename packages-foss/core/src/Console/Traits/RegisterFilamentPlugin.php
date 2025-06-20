<?php

namespace Moox\Core\Console\Traits;

use function Laravel\Prompts\alert;

trait RegisterFilamentPlugin
{
    public function registerFilamentPlugin(array $package): void
    {
        $providerPath = app_path('Providers/Filament');
        $panelsToRegister = $this->selectFilamentPanel();
        if ($panelsToRegister != null) {
            if (is_array($panelsToRegister)) {
                // Multiselect
                foreach ($panelsToRegister as $panelProvider) {
                    $this->registerPlugins($providerPath.'/'.$panelProvider, $package);
                }
            } else {
                // only one
                $this->registerPlugins($panelsToRegister, $package);
            }
        } else {
            alert('No PanelProvider Detected please register Plugins manualy.');
        }
    }

    protected function registerPlugins(string $providerPath, array $package): void
    {
        $content = file_get_contents($providerPath);
        $plugins = $this->packageService->getPlugins($package);

        foreach ($plugins as $plugin) {
            if (! str_contains($content, $plugin)) {
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
