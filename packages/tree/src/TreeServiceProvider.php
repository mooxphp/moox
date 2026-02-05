<?php

declare(strict_types=1);

namespace Moox\Tree;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
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
