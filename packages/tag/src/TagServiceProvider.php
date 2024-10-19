<?php

declare(strict_types=1);

namespace Moox\Tag;

use Moox\Tag\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TagServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tag')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_tags_table', 'create_taggables_table'])
            ->hasCommand(InstallCommand::class);
    }
}
