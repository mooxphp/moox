<?php

namespace Moox\Monorepo;

use Moox\Core\MooxServiceProvider;
use Moox\Monorepo\Commands;
use Spatie\LaravelPackageTools\Package;

class MonorepoServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('monorepo')
            ->hasCommands([
                Commands\CreateReleaseCommand::class,
            ])
            ->hasTranslations()
            ->hasConfigFile();
    }
}
