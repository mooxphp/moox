<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait CheckForFilament
{
    protected string $providerPath = 'app/Providers/Filament/AdminPanelProvider.php';

    public function checkForFilament(): void
    {
        if (! File::exists(base_path($this->providerPath))) {
            error('The Filament AdminPanelProvider.php or FilamentServiceProvider.php file does not exist.');
            warning('You should install FilamentPHP first, see https://filamentphp.com/docs/panels/installation');

            if (confirm('Do you want to install Filament now?', true)) {
                if (! array_key_exists('filament:install', Artisan::all())) {
                    error('filament:install command not available. Please check if Filament is installed as a dependency.');
                    return;
                }

                info('Starting Filament installer...');
                $this->call('filament:install', ['--panels' => true]);
            }
        }

        if (
            ! File::exists(base_path($this->providerPath)) &&
            ! confirm('Filament is not installed properly. Do you want to proceed anyway?', false)
        ) {
            info('Installation cancelled.');
            return;
        }
    }
}
