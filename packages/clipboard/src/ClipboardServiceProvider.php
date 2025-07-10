<?php

declare(strict_types=1);

namespace Moox\Clipboard;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ClipboardServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('clipboard')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommands()
            ->hasAssets();
    }
}
