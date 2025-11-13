<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Console\Traits\SelectFilamentPanel;

use function Laravel\Prompts\select;

class UpdatePanelDependencies extends Command
{
    use SelectFilamentPanel;

    protected $signature = 'moox:update-panel-dependencies {panel? : The panel to update dependencies for}';

    protected $description = 'Update composer dependencies for panel packages based on their plugins';

    public function handle(): int
    {
        $panel = $this->argument('panel');

        if (! $panel) {
            $availablePanels = array_keys($this->panelMap);
            $panel = select(
                label: '🛠️ Which panel do you want to update dependencies for?',
                options: $availablePanels
            );
        }

        if (! isset($this->panelMap[$panel])) {
            $this->error("❌ Panel '{$panel}' not found.");

            return 1;
        }

        $this->updatePanelDependencies($panel);

        return 0;
    }
}
