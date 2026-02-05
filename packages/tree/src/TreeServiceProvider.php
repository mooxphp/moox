<?php

declare(strict_types=1);

namespace Moox\Tree;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class TreeServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('tree')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations('create_trees_table')
            ->hasCommands();
    }
}
