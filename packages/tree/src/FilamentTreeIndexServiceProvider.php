<?php

declare(strict_types=1);

namespace Moox\Tree;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Moox\Tree\Livewire\ResourceTreeIndex;

class FilamentTreeIndexServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-tree-index.php', 'filament-tree-index');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-tree-index');

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn (): HtmlString => new HtmlString(view('filament-tree-index::scripts.alpine-tree-store')->render()),
        );

        Livewire::component(
            config('filament-tree-index.livewire.alias', 'filament-tree-index'),
            ResourceTreeIndex::class,
        );

        $this->publishes([
            __DIR__.'/../config/filament-tree-index.php' => config_path('filament-tree-index.php'),
        ], 'filament-tree-index-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-tree-index'),
        ], 'filament-tree-index-views');
    }
}
