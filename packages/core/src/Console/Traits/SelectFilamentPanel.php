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
        'Moox Complete' => ['shop', 'press', 'devops', 'jobs'],
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
        }

        return $selectedPanels;
    }

    protected function panelExists(string $panel): bool
    {
        $panelClass = 'App\\Panels\\' . ucfirst($panel) . 'Panel';
        $providerPath = base_path('app/Providers/Filament/' . ucfirst($panel) . 'PanelProvider.php');

        return class_exists($panelClass) || file_exists($providerPath);
    }
}
