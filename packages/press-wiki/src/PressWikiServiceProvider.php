<?php

declare(strict_types=1);

namespace Moox\PressWiki;

use Moox\PressWiki\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PressWikiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('press-wiki')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }
}
