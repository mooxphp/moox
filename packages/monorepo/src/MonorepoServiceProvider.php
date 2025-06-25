<?php

namespace Moox\Monorepo;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class MonorepoServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('monorepo')
            ->hasConfigFile();
    }
}