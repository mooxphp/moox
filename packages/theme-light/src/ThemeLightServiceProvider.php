<?php

declare(strict_types=1);

namespace Moox\ThemeLight;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ThemeLightServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('theme-light')
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'theme-light');
    }
}
