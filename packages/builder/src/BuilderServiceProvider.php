<?php

declare(strict_types=1);

namespace Moox\Builder;

use Moox\Builder\Commands\CreateEntityCommand;
use Moox\Builder\Commands\DeleteEntityCommand;
use Moox\Builder\Commands\InstallCommand;
use Moox\Builder\Providers\BuilderPanelProvider;
use Moox\Builder\Services\Block\BlockReconstructor;
use Moox\Builder\Services\Build\BuildManager;
use Moox\Builder\Services\Build\BuildRecorder;
use Moox\Builder\Services\Entity\EntityCreator;
use Moox\Builder\Services\Entity\EntityGenerator;
use Moox\Builder\Services\Entity\EntityRebuilder;
use Moox\Builder\Services\File\FileCleanup;
use Moox\Builder\Services\File\FileFormatter;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Services\File\FileOperations;
use Moox\Builder\Services\Preview\PreviewTableManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BuilderServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    public function register(): void
    {
        parent::register();

        $this->app->register(BuilderPanelProvider::class);

        $this->app->singleton(EntityCreator::class);
        $this->app->singleton(PreviewTableManager::class);
        $this->app->singleton(EntityGenerator::class);
        $this->app->singleton(BuildManager::class);
        $this->app->singleton(BuildRecorder::class);
        $this->app->singleton(BlockReconstructor::class);
        $this->app->singleton(FileOperations::class);
        $this->app->singleton(FileFormatter::class);
        $this->app->singleton(FileCleanup::class);

        $this->app->singleton(EntityRebuilder::class, function ($app) {
            return new EntityRebuilder(
                $app->make(BuildManager::class)
            );
        });

        $this->app->singleton(FileManager::class, function ($app) {
            return new FileManager(
                $app->make(FileOperations::class),
                $app->make(FileFormatter::class),
                $app->make(FileCleanup::class)
            );
        });
    }

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
                DeleteEntityCommand::class,
                CreateEntityCommand::class,
            ]);
    }
}
