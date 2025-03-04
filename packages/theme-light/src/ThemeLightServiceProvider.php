<?php

declare(strict_types=1);

namespace Moox\ThemeLight;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ThemeLightServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('theme-light')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        // optional Moox settings
        /*
        $this->getMooxPackage()
            ->mooxPlugins([
                'theme-light',
            ])
            ->mooxFirstPlugin(true)
            ->mooxRequiredSeeders(['ThemeLightSeeder']);
        */
    }
}
