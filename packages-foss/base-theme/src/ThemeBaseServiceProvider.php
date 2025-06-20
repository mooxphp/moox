<?php

declare(strict_types=1);

namespace Moox\ThemeBase;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ThemeBaseServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('theme-base')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'theme-base');
    }
}
