<?php

declare(strict_types=1);

namespace Moox\Build;

use Moox\Build\Console\Commands\BuildCommand;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BuildServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('build')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        // optional Moox settings
        /*
        $this->getMooxPackage()
            ->mooxPlugins([
                'build',
            ])
            ->mooxFirstPlugin(true)
            ->mooxParentTheme('theme-base')
            ->mooxRequiredSeeders(['BuildSeeder']);
        */
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildCommand::class,
            ]);
        }
    }
}
