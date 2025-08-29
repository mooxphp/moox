<?php

declare(strict_types=1);

namespace Moox\Media;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Moox\Media\Console\Commands\InstallCommand;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Moox\Media\Models\Media;
use Moox\Media\Policies\MediaPolicy;
use Moox\Media\Resources\MediaCollectionResource\Pages\ListMediaCollections;
use Moox\Media\Resources\MediaResource\Pages\ListMedia;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MediaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('media')
            ->hasConfigFile()
            ->hasViews('media-picker')
            ->hasTranslations()
            ->hasMigrations('create_media_table', 'create_media_translations_table', 'create_media_collections_table', 'create_media_usables_table')
            ->hasCommands(InstallCommand::class)
            ->hasAssets();
    }

    public function boot()
    {
        parent::boot();

        Gate::policy(Media::class, MediaPolicy::class);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'media');
        Livewire::component('media-picker-modal', MediaPickerModal::class);

        $this->publishes([
            __DIR__.'/../resources/dist/icons' => public_path('vendor/media/icons'),
        ], 'media-icons');

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListMedia::class
        );

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListMediaCollections::class
        );
    }
}
