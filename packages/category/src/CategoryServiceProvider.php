<?php

declare(strict_types=1);

namespace Moox\Category;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Category\Commands\InstallCommand;
use Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages\ListCategories;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class CategoryServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
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
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListCategories::class
        );
    }

    public function mooxInfo(): array
    {
        $info = parent::mooxInfo();
        $info['migration_depends_on'] = ['moox/localization'];

        return $info;
    }
}
