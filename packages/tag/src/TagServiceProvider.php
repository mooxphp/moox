<?php

declare(strict_types=1);

namespace Moox\Tag;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Tag\Commands\InstallCommand;
use Moox\Tag\Resources\TagResource\Pages\ListTags;
use Spatie\LaravelPackageTools\Package;
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
            fn (): string => Blade::render('
                <select onchange="window.location.href = window.location.pathname + \'?lang=\' + this.value" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500">
                    <option value="">Select Language</option>
                    <option value="en">English</option>
                    <option value="fr">French</option>
                    <option value="es">Spanish</option>
                    <option value="de">German</option>
                    <option value="it">Italian</option>
                </select>
            '),
            scopes: ListTags::class
        );
    }
}
