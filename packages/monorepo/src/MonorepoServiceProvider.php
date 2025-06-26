<?php

namespace Moox\Monorepo;

use Moox\Core\MooxServiceProvider;
use Moox\Monorepo\Console\Commands\ReleaseCommand;
use Spatie\LaravelPackageTools\Package;

class MonorepoServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('monorepo')
            ->hasCommands([
                ReleaseCommand::class,
            ])
            ->hasConfigFile();
    }
}
