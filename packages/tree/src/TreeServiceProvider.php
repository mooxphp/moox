<?php

declare(strict_types=1);

namespace Moox\Tree;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-tree-index.php', 'filament-tree-index');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-tree-index');

        FilamentAsset::register([
            Css::make('tree-index', __DIR__.'/../resources/css/tree.css'),
        ], 'moox/tree');

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): HtmlString => new HtmlString(view('filament-tree-index::scripts.alpine-tree-store')->render()),
        );

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            function (): string {
                $livewire = Livewire::current();

                if (! $livewire instanceof TreeIndexListRecords) {
                    return '';
                }

                $resource = $livewire::getResource();

                if (! is_subclass_of($resource, ConfiguresTreeIndex::class)) {
                    return '';
                }

                if (! $resource::treeIndex()->usesFilamentTableToolbar()) {
                    return '';
                }

                if (! $resource::treeIndex()->isFilamentTableLanguageSwitcherEnabled()) {
                    return '';
                }

                if (! view()->exists('localization::lang-selector')) {
                    return '';
                }

                return Blade::render('@include("localization::lang-selector")');
            },
        );

        $this->publishes([
            __DIR__.'/../config/filament-tree-index.php' => config_path('filament-tree-index.php'),
        ], 'filament-tree-index-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-tree-index'),
        ], 'filament-tree-index-views');
    }
}
