<?php

declare(strict_types=1);

namespace Moox\Core;

use Moox\Core\Commands\InstallCommand;
use Moox\Core\Traits\GoogleIcons;
use Moox\Core\Traits\TranslatableConfig;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CoreServiceProvider extends PackageServiceProvider
{
    use GoogleIcons, TranslatableConfig;

    public function boot()
    {
        parent::boot();

        $this->useGoogleIcons();
        $this->translateConfigurations();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('core')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }

    protected function translateConfigurations()
    {
        $packages = config('core.packages', []);

        foreach ($packages as $slug => $package) {
            $configData = config($slug);
            if (is_array($configData)) {
                $translatedConfig = $this->translateConfig($configData);
                config([$slug => $translatedConfig]);
            }
        }
    }
}
