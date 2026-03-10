<?php

declare(strict_types=1);

namespace Moox\Media;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Moox\Core\MooxServiceProvider;
use Moox\Media\Console\Commands\InstallCommand;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Moox\Media\Installers\MediaInstaller;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaTranslation;
use Moox\Media\Policies\MediaPolicy;
use Moox\Media\Resources\MediaCollectionResource\Pages\ListMediaCollections;
use Moox\Media\Resources\MediaResource\Pages\ListMedia;
use Moox\Media\Traits\HasMediaUsable;
use Spatie\LaravelPackageTools\Package;

class MediaServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
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

    /**
     * Custom-Installer für das Media-Package, vom Moox-Installer ausgewertet.
     *
     * @return array<\Moox\Core\Installer\Contracts\AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new MediaInstaller,
        ];
    }

    /**
     * Custom-Assets, damit der Typ "media-setup" im Installer auswählbar ist.
     */
    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'media-setup',
                'data' => [
                    'spatie-medialibrary-config',
                ],
            ],
        ];
    }

    public function bootingPackage(): void
    {
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

        // Listen for changes in media_translations table
        // These observers are necessary because Translatable may save translations
        // without triggering the Media model's saved event
        MediaTranslation::saved(function (MediaTranslation $translation) {
            $media = Media::find($translation->media_id);
            if ($media) {
                HasMediaUsable::syncMediaMetadata($media);
            }
        });

        MediaTranslation::updated(function (MediaTranslation $translation) {
            $media = Media::find($translation->media_id);
            if ($media) {
                HasMediaUsable::syncMediaMetadata($media);
            }
        });
    }
}
