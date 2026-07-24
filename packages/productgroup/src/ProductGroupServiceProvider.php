<?php

declare(strict_types=1);

namespace Moox\ProductGroup;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\ProductGroup\Resources\ProductGroup\Pages\ListProductGroups;
use Spatie\LaravelPackageTools\Package;

class ProductGroupServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('productgroup')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(
                'create_productgroups_table',
                'create_productgroup_translations_table',
            )
            ->hasCommands();
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListProductGroups::class
        );
    }

    public function mooxInfo(): array
    {
        $info = parent::mooxInfo();
        $info['migration_depends_on'] = ['moox/localization'];

        return $info;
    }
}
