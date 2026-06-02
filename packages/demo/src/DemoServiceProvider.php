<?php

declare(strict_types=1);

namespace Moox\Demo;

use Moox\Core\MooxServiceProvider;
use Moox\Demo\Commands\DemoCommand;
use Spatie\LaravelPackageTools\Package;

class DemoServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('demo')
            ->hasConfigFile()
            ->hasCommands(
                DemoCommand::class,
            );

        $this->getMooxPackage()
            ->title('Moox Demo')
            ->released(false)
            ->stability('dev')
            ->category('development')
            ->usedFor([
                'seeding demo data for installed Moox packages',
            ]);
    }
}
