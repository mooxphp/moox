<?php

declare(strict_types=1);

namespace Moox\Media;

use Livewire\Livewire;
use Moox\Media\Models\Media;
use Illuminate\Support\Facades\Gate;
use Moox\Media\Policies\MediaPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Moox\Media\Models\MediaCollection;
use Spatie\LaravelPackageTools\Package;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Moox\Media\Console\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Moox\Media\Resources\MediaResource\Pages\ListMedia;
use Moox\Media\Resources\MediaCollectionResource\Pages\ListMediaCollections;

class MediaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('media')
            ->hasConfigFile()
            ->hasViews('media-picker')
            ->hasTranslations()
            ->hasMigrations('create_media_collections_table', 'create_media_collection_translations', 'create_media_table', 'create_media_translations_table', 'create_media_usables_table')
            ->hasCommands(InstallCommand::class)
            ->hasAssets();
    }

    public function boot()
    {
        parent::boot();

        Gate::policy(Media::class, MediaPolicy::class);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'media');
        Livewire::component('media-picker-modal', MediaPickerModal::class);

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

        $this->app->booted(function () {
            if (app()->runningInConsole()) {
                return;
            }

            try {
                if (Schema::hasTable('media_collections')) {
                    MediaCollection::ensureUncategorizedExists();
                }
            } catch (\Exception $e) {
                // Silently ignore - table might not exist yet
            }
        });
    }
}
