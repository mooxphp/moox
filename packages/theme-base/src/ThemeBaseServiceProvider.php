<?php

declare(strict_types=1);

namespace Moox\ThemeBase;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ThemeBaseServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('theme-base')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        // optional Moox settings
        /*
        $this->getMooxPackage()
            ->mooxPlugins([
                'theme-base',
            ])
            ->mooxFirstPlugin(true)
            ->mooxRequiredSeeders(['ThemeBaseSeeder']);
        */
    }
}
