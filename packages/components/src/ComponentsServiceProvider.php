<?php

declare(strict_types=1);

namespace Moox\Components;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ComponentsServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('components')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();
    }
}
