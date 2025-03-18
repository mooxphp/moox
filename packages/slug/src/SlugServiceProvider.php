<?php

declare(strict_types=1);

namespace Moox\Slug;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SlugServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('slug')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-title-with-slug', __DIR__.'/../resources/dist/filament-title-with-slug.css')->loadedOnRequest(),
        ], 'filament-title-with-slug');
    }
}
