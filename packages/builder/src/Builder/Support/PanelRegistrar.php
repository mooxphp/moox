<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Support;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;

class PanelRegistrar
{
    public static function register(string $panelProviderClass): void
    {
        $provider = new $panelProviderClass(App::getInstance());

        // Get the panel configuration from the provider
        $panel = $provider->panel(Filament::getPanel($provider->getId()));

        // Register the configured panel
        Filament::registerPanel($panel);
    }
}
