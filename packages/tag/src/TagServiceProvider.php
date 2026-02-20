<?php

declare(strict_types=1);

namespace Moox\Tag;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\Tag\Commands\InstallCommand;
use Moox\Tag\Resources\TagResource\Pages\ListTags;
use Spatie\LaravelPackageTools\Package;

class TagServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('tag')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(['create_tags_table', 'create_taggables_table', 'create_tag_translations'])
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListTags::class
        );
    }

    public function mooxInfo(): array
    {
        $info = parent::mooxInfo();
        $info['migration_depends_on'] = ['moox/localization'];

        return $info;
    }
}
