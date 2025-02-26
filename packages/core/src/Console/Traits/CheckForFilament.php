<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait CheckForFilament
{
    public function checkForFilament(): void
    {
        if (! File::exists($this->providerPath)) {
            error('The Filament AdminPanelProvider.php or FilamentServiceProvider.php file does not exist.\n');
            warning('You should install FilamentPHP first, see https://filamentphp.com/docs/panels/installation \n');
            if (confirm('Do you want to install Filament now?', true)) {
                info('Starting Filament installer...');
                $this->callSilent('filament:install', ['--panels' => true]);
            }
        }

        if (! File::exists($this->providerPath) && ! confirm('Filament is not installed properly. Do you want to proceed anyway?', false)) {
            info('Installation cancelled.');

            return;
        }
    }
}
