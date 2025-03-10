<?php

declare(strict_types=1);

namespace Moox\Tag;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Tag\Commands\InstallCommand;
use Moox\Tag\Resources\TagResource\Pages\ListTags;
use Spatie\LaravelPackageTools\Package;
use Moox\Localization\Models\Locale;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TagServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tag')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_tags_table', 'create_taggables_table', 'create_tag_translations'])
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_TOGGLE_COLUMN_TRIGGER_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListTags::class
        );
    }
}
