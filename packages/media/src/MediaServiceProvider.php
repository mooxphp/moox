<?php

declare(strict_types=1);

namespace Moox\Media;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Moox\Media\Http\Livewire\MediaUploader;
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
            ->hasMigrations()
            ->hasCommands();
    }

    public function boot()
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'media');
        Livewire::component('media-picker-modal', MediaPickerModal::class);
        Livewire::component('media-uploader', MediaUploader::class);

        FilamentAsset::register([
            Js::make('filepond-js', asset('vendor/livewire-filepond/filepond.js')),
            // Css::make('filepond-css', asset('vendor/livewire-filepond/filepond.css')),
        ]);
    }
}
