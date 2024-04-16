<?php

declare(strict_types=1);

namespace Moox\RedisModel;

use Moox\RedisModel\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RedisModelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('redis-model')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_redis_models_table'])
            ->hasCommand(InstallCommand::class);
    }
}
