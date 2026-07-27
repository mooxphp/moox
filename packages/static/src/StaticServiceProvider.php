<?php

declare(strict_types=1);

namespace Moox\Static;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\Static\Resources\StaticEntryResource\Pages\ListStaticEntries;
use Spatie\LaravelPackageTools\Package;

class StaticServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('static')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations('create_static_entries_table', 'create_static_entry_translations_table');
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListStaticEntries::class
        );
    }
}
