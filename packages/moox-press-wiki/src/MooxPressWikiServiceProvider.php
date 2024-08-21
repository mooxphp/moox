<?php

declare(strict_types=1);

namespace Moox\MooxPressWiki;

use Moox\MooxPressWiki\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MooxPressWikiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('moox-press-wiki')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }
}
