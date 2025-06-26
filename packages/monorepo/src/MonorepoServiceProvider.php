<?php

namespace Moox\Monorepo;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Moox\Monorepo\Console\Commands;

class MonorepoServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('monorepo')
            ->hasCommands([
                Commands\ReleaseCommand::class,
                Commands\CreateReleaseCommand::class,
            ])
            ->hasConfigFile();
    }
}
