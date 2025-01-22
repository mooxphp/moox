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
use Moox\Builder\Services\File\FileFormatter;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Services\File\FileOperations;
use Moox\Builder\Services\Preview\PreviewTableManager;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BuilderServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function boot(): void
    {
        parent::boot();
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->bind('moox.builder.path', fn (): string => dirname(__DIR__));

        $this->app->register(BuilderPanelProvider::class);

        $this->app->singleton(EntityCreator::class);
        $this->app->singleton(PreviewTableManager::class);
        $this->app->singleton(EntityGenerator::class);
        $this->app->singleton(BuildManager::class);
        $this->app->singleton(BuildRecorder::class);
        $this->app->singleton(BlockReconstructor::class);
        $this->app->singleton(FileOperations::class);
        $this->app->singleton(FileFormatter::class);

        $this->app->singleton(EntityRebuilder::class, fn ($app): EntityRebuilder => new EntityRebuilder(
            $app->make(EntityCreator::class),
            $app->make(BuildManager::class),
            $app->make(FileManager::class)
        ));

        $this->app->singleton(FileManager::class, fn ($app): FileManager => new FileManager(
            $app->make(FileOperations::class),
            $app->make(FileFormatter::class)
        ));
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('builder')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([

                // TODO: delete when builder is able to generate
                'create_items_table',
                'create_full_items_table',
                'create_simple_items_table',
                'create_simple_taxonomies_table',
                'create_nested_taxonomies_table',
                'create_simple_taxonomyables_table',
                'create_nested_taxonomyables_table',

                'create_builder_entities_table',
                'create_builder_entity_blocks_table',
                'create_builder_entity_builds_table',
                'create_builder_entity_tabs_table',
                'create_builder_package_versions_table',
                'create_builder_packages_table',
            ])
            ->hasCommands([
                InstallCommand::class,
                DeleteEntityCommand::class,
                CreateEntityCommand::class,
            ]);
    }
}
