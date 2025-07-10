<?php

declare(strict_types=1);

namespace Moox\Progress;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ProgressServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('progress')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommands()
            ->hasAssets();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('core-progress', __DIR__.'/../resources/css/progress.css'),
        ], 'moox/progress');
    }
}
