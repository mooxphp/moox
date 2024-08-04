<?php

declare(strict_types=1);

namespace Moox\Core;

use Moox\Core\Commands\InstallCommand;
use Moox\Core\Traits\GoogleIcons;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CoreServiceProvider extends PackageServiceProvider
{
    use GoogleIcons;

    public function boot()
    {
        parent::boot();

        $this->useGoogleIcons();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('core')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }
}
