<?php

declare(strict_types=1);

namespace Moox\Media;


use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Moox\Media\Http\Livewire\MediaUploader;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

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

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'media');
        Livewire::component('media-picker-modal', MediaPickerModal::class);
        Livewire::component('media-uploader', MediaUploader::class);

            FilamentAsset::register([
                Js::make('filepond-js', asset('vendor/livewire-filepond/filepond.js')),
                Css::make('filepond-css', asset('vendor/livewire-filepond/filepond.css')),
            ]);



    }
}
