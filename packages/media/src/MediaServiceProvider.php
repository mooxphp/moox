<?php

declare(strict_types=1);

namespace Moox\Media;

use Livewire\Livewire;
use Moox\Media\Models\Media;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Illuminate\Support\Facades\Gate;
use Moox\Media\Policies\MediaPolicy;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Filament\Support\Facades\FilamentAsset;
use Moox\Media\Http\Livewire\MediaUploader;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Moox\Media\Resources\MediaResource\Pages\ListMedia;

class MediaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('media')
            ->hasConfigFile()
            ->hasViews('media-picker')
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands()
            ->hasAssets();
    }

    public function boot()
    {
        parent::boot();

        Gate::policy(Media::class, MediaPolicy::class);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'media');
        Livewire::component('media-picker-modal', MediaPickerModal::class);
        Livewire::component('media-uploader', MediaUploader::class);

        $this->publishes([
            __DIR__ . '/../resources/dist/icons' => public_path('vendor/media/icons'),
        ], 'media-icons');

        FilamentAsset::register([
            Js::make('filepond-js', asset('vendor/livewire-filepond/filepond.js')),
            // Css::make('filepond-css', asset('vendor/livewire-filepond/filepond.css')),
        ]);

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_TOGGLE_COLUMN_TRIGGER_BEFORE,
            fn(): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListMedia::class
        );
    }
}
