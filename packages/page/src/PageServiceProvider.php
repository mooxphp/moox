<?php

declare(strict_types=1);

namespace Moox\Page;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\Page\Resources\PageResource\Pages\ListPages;
use Spatie\LaravelPackageTools\Package;

class PageServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('page')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations('create_pages_table', 'create_page_translations_table')
            ->hasCommands();
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListPages::class
        );
    }
}
