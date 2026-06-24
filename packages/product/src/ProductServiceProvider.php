<?php

declare(strict_types=1);

namespace Moox\Product;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\Product\Resources\Product\Pages\ListProducts;
use Spatie\LaravelPackageTools\Package;

class ProductServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('product')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(
                'create_products_table',
                'create_product_translations_table',
                'upgrade_products_table',
            )
            ->hasCommands();
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListProducts::class
        );
    }

    public function mooxInfo(): array
    {
        $info = parent::mooxInfo();
        $info['migration_depends_on'] = ['moox/localization'];

        return $info;
    }
}
