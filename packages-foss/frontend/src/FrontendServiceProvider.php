<?php

declare(strict_types=1);

namespace Moox\Frontend;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FrontendServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('frontend')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Frontend')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                '%%UsedFor%%',
            ])
            ->alternatePackages([
                '', // optional alternative package (e.g. moox/post)
            ])
            ->templateFor([
                'creating simple Laravel packages',
            ])

            ->templateRemove([
                'build.php',
            ]);
    }
}
