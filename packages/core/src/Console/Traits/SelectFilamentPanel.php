<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Facades\File;

use function Laravel\Prompts\multiselect;

trait SelectFilamentPanel
{
    public function selectFilamentPanel(): string|array
    {
        $providerPath = app_path('Providers/Filament');
        $providers = File::allFiles($providerPath);
        if (count($providers) > 1) {
            $providerNames = [];
            foreach ($providers as $provider) {
                $providerNames[] = $provider->getBasename();
            }

            $providerPath = multiselect(
                label: 'Which Panel should it be registered',
                options: [...$providerNames],
                default: [$providerNames[0]],
            );
        }

        if (count($providers) == 1) {
            $providerPath .= '/'.$providers[0]->getBasename();
        }

        return $providerPath;
    }
}
