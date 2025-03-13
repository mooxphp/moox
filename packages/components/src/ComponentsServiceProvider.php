<?php

declare(strict_types=1);

namespace Moox\Components;

use Illuminate\Support\Facades\Blade;
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

    public function bootingPackage(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'components');

        Blade::component('components::components.buttons.button', 'moox-button');
        Blade::component('components::components.icons.icon', 'moox-icon');
    }
}
