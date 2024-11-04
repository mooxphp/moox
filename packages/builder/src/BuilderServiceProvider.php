<?php

declare(strict_types=1);

namespace Moox\Builder;

use Moox\Builder\Commands\CreateEntityCommand;
use Moox\Builder\Commands\CreatePreviewCommand;
use Moox\Builder\Commands\DeleteEntityCommand;
use Moox\Builder\Commands\DeletePreviewCommand;
use Moox\Builder\Commands\InstallCommand;
use Moox\Builder\Providers\BuilderPanelProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('builder')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_items_table',
                'create_full_items_table',
                'create_simple_items_table',
                'create_simple_taxonomies_table',
                'create_nested_taxonomies_table',
                'create_simple_taxonomyables_table',
                'create_nested_taxonomyables_table',
            ])
            ->hasCommands([
                InstallCommand::class,
                DeletePreviewCommand::class,
                DeleteEntityCommand::class,
                CreatePreviewCommand::class,
                CreateEntityCommand::class,
            ]);
    }

    public function register(): void
    {
        $this->app->register(BuilderPanelProvider::class);
    }
}
