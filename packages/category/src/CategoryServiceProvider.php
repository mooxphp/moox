<?php

declare(strict_types=1);

namespace Moox\Category;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Category\Commands\InstallCommand;
use Moox\Category\Moox\Entities\Categories\Category\Pages\ListCategories;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CategoryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('category')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(['create_categories_table', 'create_categorizables_table', 'create_category_translations_table'])
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_TOGGLE_COLUMN_TRIGGER_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListCategories::class
        );
    }
}
