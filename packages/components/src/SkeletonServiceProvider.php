<?php

declare(strict_types=1);

namespace Moox\Skeleton;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class SkeletonServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('skeleton')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        // optional Moox settings
        /*
        $this->getMooxPackage()
            ->mooxPlugins([
                'skeleton',
            ])
            ->mooxFirstPlugin(true)
            ->mooxRequiredSeeders(['SkeletonSeeder']);
        */
    }
}
