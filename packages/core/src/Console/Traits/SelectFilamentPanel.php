<?php

namespace Moox\Core\Console\Traits;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

trait SelectFilamentPanel
{
    protected array $panelBundles = [
        'Moox Complete' => ['admin', 'shop', 'press', 'devops', 'jobs'],
    ];

    public function selectPanelBundle(): array
    {
        $bundleName = select(
            'Which panel bundle do you want to install?',
            array_keys($this->panelBundles),
        );

        $selectedPanels = $this->panelBundles[$bundleName];

        $this->info("You selected the '{$bundleName}' bundle.");
        $this->info('Included panels: '.implode(', ', $selectedPanels));

        foreach ($selectedPanels as $panel) {
            if ($this->panelExists($panel)) {
                warning("Panel '{$panel}' already exists. Skipping generation.");

                continue;
            }

            $this->call('make:filament-panel', [
                'name' => ucfirst($panel),
            ]);

            info("Filament panel '{$panel}' generated.");
        }

        return $selectedPanels;
    }

    protected function panelExists(string $panel): bool
    {
        $panelClass = 'App\\Panels\\'.ucfirst($panel).'Panel';

        return class_exists($panelClass);
    }
}
